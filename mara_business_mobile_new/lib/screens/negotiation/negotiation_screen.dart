import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/models/api_response.dart';
import '../../core/models/negotiation.dart';
import '../../services/api_service.dart';
import '../../utils/app_logger.dart';

/// Une négociation de prix, vue du client.
///
/// L'écran ne décide rien. Le bandeau montre ce que le serveur a renvoyé —
/// `has_live_offer`, `offer_expired` — plutôt que de comparer une date
/// d'expiration à l'horloge du téléphone, qui peut avancer. Et quand le serveur
/// refuse (prix expiré, stock parti pendant la discussion), c'est son message qui
/// s'affiche : lui seul sait pourquoi.
class NegotiationScreen extends StatefulWidget {
  final int orderId;

  const NegotiationScreen({super.key, required this.orderId});

  @override
  State<NegotiationScreen> createState() => _NegotiationScreenState();
}

class _NegotiationScreenState extends State<NegotiationScreen> {
  final TextEditingController _input = TextEditingController();
  final ScrollController _scroll = ScrollController();

  Negotiation? _negotiation;
  List<NegotiationMessage> _messages = [];
  bool _isLoading = true;
  bool _isSending = false;
  bool _isActing = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await context.read<ApiService>().getNegotiation(widget.orderId);
      if (!mounted) return;

      if (response.success && response.data is Map) {
        final data = response.data as Map;
        final raw = data['negotiation'];
        final thread = data['messages'];

        setState(() {
          _negotiation = raw is Map
              ? Negotiation.fromJson(Map<String, dynamic>.from(raw))
              : null;
          _messages = (thread as List? ?? [])
              .whereType<Map>()
              .map((m) => NegotiationMessage.fromJson(Map<String, dynamic>.from(m)))
              .toList();
          _isLoading = false;
        });
        _jumpToLatest();
      } else {
        setState(() {
          _error = response.message ?? 'Impossible de charger la négociation';
          _isLoading = false;
        });
      }
    } catch (e) {
      logDebug('🔴 Negotiation load failed: $e');
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _send() async {
    final text = _input.text.trim();
    if (text.isEmpty || _isSending) return;

    setState(() => _isSending = true);

    try {
      final response =
          await context.read<ApiService>().sendNegotiationMessage(widget.orderId, text);
      if (!mounted) return;

      if (response.success) {
        // Vidé seulement une fois le message accepté : effacer avant, c'est
        // perdre ce que quelqu'un vient d'écrire quand la requête échoue.
        _input.clear();

        final thread = response.data is Map ? (response.data as Map)['messages'] : null;
        if (thread is List) {
          setState(() {
            _messages = thread
                .whereType<Map>()
                .map((m) => NegotiationMessage.fromJson(Map<String, dynamic>.from(m)))
                .toList();
          });
          _jumpToLatest();
        } else {
          await _load();
        }
      } else {
        _complain(response.message ?? 'Message non envoyé');
      }
    } catch (e) {
      logDebug('🔴 Negotiation send failed: $e');
      _complain('Erreur: $e');
    } finally {
      if (mounted) setState(() => _isSending = false);
    }
  }

  Future<void> _accept() async {
    final confirmed = await _confirm(
      title: 'Accepter ce prix ?',
      body: 'La commande deviendra payable au prix proposé.',
      action: 'Accepter',
    );
    if (confirmed != true) return;

    await _act(
      () => context.read<ApiService>().acceptNegotiatedPrice(widget.orderId),
      done: 'Prix accepté. Vous pouvez régler la commande.',
    );
  }

  Future<void> _refuse() async {
    final confirmed = await _confirm(
      title: 'Refuser ce prix ?',
      body: 'La discussion reste ouverte, la boutique pourra en proposer un autre.',
      action: 'Refuser',
    );
    if (confirmed != true) return;

    await _act(
      () => context.read<ApiService>().refuseNegotiatedPrice(widget.orderId),
      done: 'Prix refusé. La discussion continue.',
    );
  }

  Future<void> _cancel() async {
    final confirmed = await _confirm(
      title: 'Annuler la négociation ?',
      body: 'La commande sera annulée. Cette action est définitive.',
      action: 'Annuler la commande',
      destructive: true,
    );
    if (confirmed != true) return;

    await _act(
      () => context.read<ApiService>().cancelNegotiation(widget.orderId),
      done: 'Négociation annulée.',
    );
  }

  /// Les trois sorties partagent la même mécanique : appeler, montrer le message
  /// du serveur s'il refuse, recharger s'il accepte.
  Future<void> _act(Future<ApiResponse> Function() call, {required String done}) async {
    setState(() => _isActing = true);

    try {
      final response = await call();
      if (!mounted) return;

      if (response.success) {
        await _load();
        if (mounted) _tell(done);
      } else {
        // 409 : prix expiré, stock parti pendant la discussion. Le serveur dit
        // pourquoi ; l'inventer ici mentirait au client une fois sur deux.
        _complain(response.message ?? "Action refusée");
        await _load();
      }
    } catch (e) {
      logDebug('🔴 Negotiation action failed: $e');
      _complain('Erreur: $e');
    } finally {
      if (mounted) setState(() => _isActing = false);
    }
  }

  Future<bool?> _confirm({
    required String title,
    required String body,
    required String action,
    bool destructive = false,
  }) {
    return showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: Text(body),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Retour'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(context).pop(true),
            style: ElevatedButton.styleFrom(
              backgroundColor: destructive ? Colors.red : const Color(0xFFD4AF37),
              foregroundColor: Colors.white,
            ),
            child: Text(action),
          ),
        ],
      ),
    );
  }

  void _tell(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.green[700]),
    );
  }

  void _complain(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  void _jumpToLatest() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.jumpTo(_scroll.position.maxScrollExtent);
      }
    });
  }

  String _money(double amount) {
    final symbol = _negotiation?.currencySymbol ?? _negotiation?.currency ?? '';
    return '${amount.toStringAsFixed(2)} $symbol'.trim();
  }

  @override
  Widget build(BuildContext context) {
    final negotiation = _negotiation;

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
        title: Text(
          negotiation?.vendorName ?? 'Négociation',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
        actions: [
          if (negotiation != null && negotiation.isOpen)
            IconButton(
              tooltip: 'Annuler la négociation',
              icon: const Icon(Icons.close, color: Colors.red),
              onPressed: _isActing ? null : _cancel,
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                if (negotiation != null) _priceBanner(negotiation),
                Expanded(child: _conversation()),
                // Une négociation close n'a plus de composeur : le fil reste
                // consultable, mais il n'y a plus personne à qui écrire.
                if (negotiation != null && negotiation.isOpen) _composer(),
              ],
            ),
    );
  }

  Widget _priceBanner(Negotiation negotiation) {
    final proposed = negotiation.proposedTotal;
    final original = negotiation.originalTotal;

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(12, 12, 12, 0),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: negotiation.awaitingBuyer
              ? const Color(0xFFD4AF37)
              : Colors.grey[200]!,
          width: negotiation.awaitingBuyer ? 1.5 : 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  'Commande ${negotiation.orderNumber}',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
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
          const SizedBox(height: 12),

          if (original != null)
            _line(
              'Prix de départ',
              _money(original),
              // Barré seulement quand un autre prix l'a effectivement remplacé.
              struck: proposed != null || negotiation.isAgreed,
            ),
          if (negotiation.targetPrice != null)
            _line('Votre proposition', _money(negotiation.targetPrice!)),
          if (proposed != null)
            _line('Prix de la boutique', _money(proposed), strong: true),
          if (negotiation.isAgreed)
            _line('Prix convenu', _money(negotiation.grandTotal), strong: true),
          if (negotiation.discount != null && negotiation.isAgreed)
            _line('Remise obtenue', '−${_money(negotiation.discount!)}'),

          if (negotiation.expiresAt != null && negotiation.hasLiveOffer) ...[
            const SizedBox(height: 6),
            Text(
              'Valable jusqu\'au ${_date(negotiation.expiresAt!)}',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],

          if (negotiation.offerExpired) ...[
            const SizedBox(height: 6),
            Text(
              'Ce prix a expiré. Demandez-en un nouveau dans la discussion.',
              style: TextStyle(fontSize: 12, color: Colors.orange[800]),
            ),
          ],

          if (negotiation.awaitingVendor && !negotiation.offerExpired) ...[
            const SizedBox(height: 6),
            Text(
              'La boutique a été prévenue. Sa réponse arrivera ici.',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],

          if (negotiation.awaitingBuyer) ...[
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _isActing ? null : _accept,
                    icon: const Icon(Icons.check, size: 18),
                    label: const Text('Accepter'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD4AF37),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _isActing ? null : _refuse,
                    icon: const Icon(Icons.close, size: 18),
                    label: const Text('Refuser'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.grey[800],
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
          ],

          if (negotiation.isAgreed) ...[
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => context.push('/orders/${negotiation.orderId}'),
                icon: const Icon(Icons.payments_outlined, size: 18),
                label: const Text('Régler la commande'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.green[700],
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _line(String label, String value, {bool strong = false, bool struck = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontSize: 13, color: Colors.grey[600])),
          Text(
            value,
            style: TextStyle(
              fontSize: strong ? 15 : 13,
              fontWeight: strong ? FontWeight.bold : FontWeight.w500,
              color: strong ? const Color(0xFFD4AF37) : Colors.grey[900],
              decoration: struck ? TextDecoration.lineThrough : null,
            ),
          ),
        ],
      ),
    );
  }

  String _date(DateTime value) {
    final local = value.toLocal();
    final day = local.day.toString().padLeft(2, '0');
    final month = local.month.toString().padLeft(2, '0');
    return '$day/$month/${local.year}';
  }

  Widget _conversation() {
    if (_error != null && _messages.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.cloud_off, size: 56, color: Colors.grey),
              const SizedBox(height: 12),
              Text(
                'Impossible de charger la négociation',
                style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey[800]),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 6),
              Text(
                _error!,
                style: TextStyle(color: Colors.grey[600], fontSize: 13),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _load,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                ),
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    if (_messages.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Text(
            'La discussion commencera ici.',
            style: TextStyle(color: Colors.grey[600]),
            textAlign: TextAlign.center,
          ),
        ),
      );
    }

    return ListView.builder(
      controller: _scroll,
      padding: const EdgeInsets.all(16),
      itemCount: _messages.length,
      itemBuilder: (context, index) => _bubble(_messages[index]),
    );
  }

  Widget _bubble(NegotiationMessage message) {
    final mine = message.fromBuyer;

    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: mine ? const Color(0xFFD4AF37) : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(mine ? 16 : 4),
            bottomRight: Radius.circular(mine ? 4 : 16),
          ),
          border: mine ? null : Border.all(color: Colors.grey[200]!),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              message.message,
              style: TextStyle(
                color: mine ? Colors.white : Colors.grey[900],
                fontSize: 14,
              ),
            ),
            if (message.sentAt != null) ...[
              const SizedBox(height: 4),
              Text(
                _date(message.sentAt!),
                style: TextStyle(
                  fontSize: 10,
                  color: mine ? Colors.white70 : Colors.grey[500],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _composer() {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey[200]!)),
      ),
      child: SafeArea(
        top: false,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              child: TextField(
                controller: _input,
                minLines: 1,
                maxLines: 4,
                // L'API refuse au-delà de 2000 caractères ; s'arrêter ici évite
                // d'écrire longuement pour se faire renvoyer.
                maxLength: 2000,
                textInputAction: TextInputAction.newline,
                decoration: InputDecoration(
                  hintText: 'Votre message…',
                  counterText: '',
                  filled: true,
                  fillColor: Colors.grey[100],
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            Material(
              color: const Color(0xFFD4AF37),
              shape: const CircleBorder(),
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: _isSending ? null : _send,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: _isSending
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation(Colors.white),
                          ),
                        )
                      : const Icon(Icons.send, color: Colors.white, size: 20),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
