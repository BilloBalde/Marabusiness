import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/models/negotiation.dart';
import '../../services/api_service.dart';
import '../../utils/app_logger.dart';

/// Les négociations de prix du client — le pendant mobile de /my-rfqs.
///
/// Les prix à trancher passent devant : une négociation où la boutique attend
/// n'appelle aucune action, celle où un prix est sur la table en appelle une.
class NegotiationListScreen extends StatefulWidget {
  const NegotiationListScreen({super.key});

  @override
  State<NegotiationListScreen> createState() => _NegotiationListScreenState();
}

class _NegotiationListScreenState extends State<NegotiationListScreen> {
  List<Negotiation> _negotiations = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await context.read<ApiService>().getNegotiations();
      if (!mounted) return;

      if (response.success && response.data is Map) {
        final raw = (response.data as Map)['negotiations'];

        final all = (raw as List? ?? [])
            .whereType<Map>()
            .map((n) => Negotiation.fromJson(Map<String, dynamic>.from(n)))
            .toList();

        // Ce qui attend le client d'abord ; le serveur trie déjà par date, ce
        // tri-ci ne fait que remonter l'urgent sans perdre cet ordre.
        all.sort((a, b) {
          if (a.awaitingBuyer == b.awaitingBuyer) return 0;
          return a.awaitingBuyer ? -1 : 1;
        });

        setState(() {
          _negotiations = all;
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.message ?? 'Impossible de charger vos négociations';
          _isLoading = false;
        });
      }
    } catch (e) {
      logDebug('🔴 Negotiations load failed: $e');
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
        title: const Text(
          'Mes négociations',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        color: const Color(0xFFD4AF37),
        child: _body(),
      ),
    );
  }

  Widget _body() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null && _negotiations.isEmpty) {
      return ListView(
        children: [
          const SizedBox(height: 120),
          const Icon(Icons.cloud_off, size: 56, color: Colors.grey),
          const SizedBox(height: 12),
          Center(
            child: Text(
              'Impossible de charger vos négociations',
              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey[800]),
            ),
          ),
          const SizedBox(height: 6),
          Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 32),
              child: Text(
                _error!,
                style: TextStyle(color: Colors.grey[600], fontSize: 13),
                textAlign: TextAlign.center,
              ),
            ),
          ),
          const SizedBox(height: 20),
          Center(
            child: ElevatedButton(
              onPressed: _load,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFD4AF37),
                foregroundColor: Colors.white,
              ),
              child: const Text('Réessayer'),
            ),
          ),
        ],
      );
    }

    if (_negotiations.isEmpty) {
      return ListView(
        children: [
          const SizedBox(height: 120),
          Icon(Icons.forum_outlined, size: 56, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Center(
            child: Text(
              'Aucune négociation',
              style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey[800]),
            ),
          ),
          const SizedBox(height: 8),
          Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 40),
              child: Text(
                'Depuis votre panier, au moment de commander, vous pouvez '
                'proposer un prix à une boutique avant de payer.',
                style: TextStyle(color: Colors.grey[600], fontSize: 13),
                textAlign: TextAlign.center,
              ),
            ),
          ),
          const SizedBox(height: 24),
          Center(
            child: ElevatedButton.icon(
              onPressed: () => context.go('/cart'),
              icon: const Icon(Icons.shopping_cart_outlined, size: 18),
              label: const Text('Voir mon panier'),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFD4AF37),
                foregroundColor: Colors.white,
              ),
            ),
          ),
        ],
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: _negotiations.length,
      itemBuilder: (context, index) => _card(_negotiations[index]),
    );
  }

  Widget _card(Negotiation negotiation) {
    final symbol = negotiation.currencySymbol ?? negotiation.currency ?? '';
    final headline = negotiation.proposedTotal ?? negotiation.grandTotal;

    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: BorderSide(
          color: negotiation.awaitingBuyer
              ? const Color(0xFFD4AF37)
              : Colors.grey[200]!,
          width: negotiation.awaitingBuyer ? 1.5 : 1,
        ),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () async {
          await context.push('/negotiations/${negotiation.orderId}');
          // Le prix a pu être accepté ou refusé sur l'autre écran.
          if (mounted) _load();
        },
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      negotiation.vendorName,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: negotiation.awaitingBuyer
                          ? const Color(0xFFD4AF37).withValues(alpha: 0.15)
                          : Colors.grey[100],
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      negotiation.statusLabel,
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: negotiation.awaitingBuyer
                            ? const Color(0xFF8A6D1F)
                            : Colors.grey[700],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                'Commande ${negotiation.orderNumber} · '
                '${negotiation.itemsCount} article(s)',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
              const SizedBox(height: 10),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    negotiation.proposedTotal != null
                        ? 'Prix proposé'
                        : (negotiation.isAgreed ? 'Prix convenu' : 'Prix actuel'),
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
                  Text(
                    '${headline.toStringAsFixed(2)} $symbol'.trim(),
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFD4AF37),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
