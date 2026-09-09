// lib/screens/payment/success_page.dart

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../core/models/api_response.dart';
import '../../utils/currency_formatter.dart';
import '../../core/constants/app_constants.dart';
import '../../widgets/common/loading_widget.dart';

class SuccessPage extends StatefulWidget {
  final String? orderId;
  final String? payId;

  const SuccessPage({
    super.key,
    this.orderId,
    this.payId,
  });

  @override
  State<SuccessPage> createState() => _SuccessPageState();
}

class _SuccessPageState extends State<SuccessPage> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _orders = [];
  double _totalAmount = 0;
  int _orderCount = 0;
  String? _sessionId;

  /// Was `final bool _stripePaymentCompleted = false;` — a final field, so it
  /// could never become true and the "payment confirmed" half of this screen
  /// (green panel, check icon, confirmation wording) was unreachable code: every
  /// buyer saw the blue "awaiting" variant, including after a payment that went
  /// through. Derived from the orders this screen has just loaded instead, so it
  /// reflects what the server actually says.
  bool get _paymentCompleted =>
      _orders.isNotEmpty &&
      _orders.every((order) => order['payment_status'] == 'paid');

  @override
  void initState() {
    super.initState();
    _sessionId = widget.orderId;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadOrders();
    });
  }

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final apiService = context.read<ApiService>();
      final response = await apiService.getSuccessPageOrders();

      if (response.success && mounted) {
        final data = response.data['data'] ?? {};
        setState(() {
          _orders = data['orders'] ?? [];
          _orderCount = data['count'] ?? 0;
          _totalAmount = (data['total_amount'] ?? 0).toDouble();
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.message ?? 'Failed to load orders';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  String _getPaymentMethodLabel(String method) {
    switch (method) {
      case 'cod':
        return 'Cash à la livraison';
      case 'om':
        return 'Orange Money';
      case 'lengopay':
      case 'stripe': // legacy value for the same gateway
        return 'Carte Bancaire';
      case 'cash':
        return 'Espèces';
      default:
        return method;
    }
  }

  String _getPaymentStatusLabel(String status) {
    switch (status) {
      case 'paid':
        return 'Payé';
      case 'partial':
        return 'Paiement partiel';
      default:
        return 'En attente';
    }
  }

  Color _getPaymentStatusColor(String status) {
    switch (status) {
      case 'paid':
        return Colors.green;
      case 'partial':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }

  String _getCarrierLabel(String carrier) {
    const carriers = {
      'local': 'Livraison locale (48h)',
      'chrono': 'Chronopost',
      'dhl': 'DHL Express',
      'ups': 'UPS',
      'fedex': 'FedEx',
      'other': 'Autre transporteur',
    };
    return carriers[carrier] ?? carrier;
  }

  String _getFullImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/')) {
      return '${AppConstants.baseUrl}$path';
    }
    return '${AppConstants.baseUrl}/uploads/$path';
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return 'N/A';
    try {
      final date = DateTime.parse(dateStr);
      return DateFormat('dd/MM/yyyy HH:mm').format(date);
    } catch (e) {
      return dateStr;
    }
  }

  String _getShipmentStatus(String status) {
    switch (status) {
      case 'delivered':
        return '✅ Livré';
      case 'shipped':
        return '🚚 Expédié';
      case 'in_transit':
        return '🚚 En transit';
      case 'pending':
        return '⏳ En attente';
      default:
        return status;
    }
  }

  String _formatVariations(Map<String, dynamic>? variations) {
    if (variations == null) return '';
    
    if (variations.containsKey('note')) {
      return '📝 ${variations['note']}';
    }
    
    return variations.entries
        .where((e) => !['id', 'product_id', 'created_at', 'updated_at'].contains(e.key))
        .map((e) => '${e.key}: ${e.value}')
        .join(', ');
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        backgroundColor: Colors.grey[50],
        body: const LoadingWidget(message: 'Chargement de votre commande...'),
      );
    }

    if (_error != null) {
      return Scaffold(
        backgroundColor: Colors.grey[50],
        appBar: AppBar(
          title: const Text('Erreur'),
          backgroundColor: Colors.white,
          foregroundColor: Colors.black,
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, size: 64, color: Colors.red),
                const SizedBox(height: 16),
                Text(
                  'Erreur de chargement',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey[800]),
                ),
                const SizedBox(height: 8),
                Text(
                  _error!,
                  style: TextStyle(color: Colors.grey[600]),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: _loadOrders,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFD4AF37),
                    foregroundColor: Colors.white,
                  ),
                  child: const Text('Réessayer'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      backgroundColor: Colors.grey[50],
      body: CustomScrollView(
        slivers: [
          // App Bar
          SliverAppBar(
            expandedHeight: 120,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      Color(0xFFD4AF37),
                      Color(0xFFc9a12f),
                    ],
                  ),
                ),
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: const BoxDecoration(
                          color: Colors.white,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.check_circle,
                          color: Colors.green,
                          size: 40,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            leading: IconButton(
              icon: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.9),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.arrow_back, color: Colors.black87),
              ),
              onPressed: () => context.go('/'),
            ),
            title: const Text(
              'Commande confirmée',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
              ),
            ),
            centerTitle: true,
            backgroundColor: const Color(0xFFD4AF37),
          ),

          // Stripe Payment Status
          if (_sessionId != null)
            SliverToBoxAdapter(
              child: Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: _paymentCompleted ? Colors.green[50] : Colors.blue[50],
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: _paymentCompleted ? Colors.green[200]! : Colors.blue[200]!,
                  ),
                ),
                child: Row(
                  children: [
                    Icon(
                      _paymentCompleted ? Icons.check_circle : Icons.info,
                      color: _paymentCompleted ? Colors.green : Colors.blue,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _paymentCompleted
                                ? '✅ Paiement Stripe confirmé !'
                                : '⏳ Vérification du paiement...',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: _paymentCompleted ? Colors.green[800] : Colors.blue[800],
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _paymentCompleted
                                ? 'Votre paiement a été traité avec succès.'
                                : 'Nous vérifions votre paiement Stripe.',
                            style: TextStyle(
                              fontSize: 12,
                              color: _paymentCompleted ? Colors.green[600] : Colors.blue[600],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

          // Header
          SliverToBoxAdapter(
            child: Container(
              margin: const EdgeInsets.all(16),
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.1),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  const Text(
                    '🎉 Merci, votre commande est confirmée !',
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Nous avons créé ${_orderCount > 1 ? '$_orderCount commandes séparées' : 'une commande'} pour ${_orderCount > 1 ? 'chaque vendeur' : 'le vendeur'}.',
                    style: TextStyle(
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),

          // Orders List
          if (_orders.isEmpty)
            SliverToBoxAdapter(
              child: Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(32),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: [
                    Icon(Icons.shopping_bag_outlined, size: 64, color: Colors.grey[400]),
                    const SizedBox(height: 16),
                    const Text(
                      'Aucune commande trouvée',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.grey,
                      ),
                    ),
                  ],
                ),
              ),
            ),

          ..._orders.map((order) {
            final vendor = order['vendor'] ?? {};
            final items = order['items'] as List<dynamic>? ?? [];
            final shipment = order['latest_shipment'];
            final currency = vendor['currency_code'] ?? order['currency'] ?? 'USD';
            return SliverToBoxAdapter(
              child: Container(
                margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.withOpacity(0.1),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Vendor Header with Payment Status
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '🏪 ${vendor['store_name'] ?? order['vendor_name'] ?? 'Vendeur'}',
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 4),
                              Wrap(
                                spacing: 8,
                                runSpacing: 4,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 8,
                                      vertical: 4,
                                    ),
                                    decoration: BoxDecoration(
                                      color: _getPaymentStatusColor(order['payment_status'] ?? 'pending')
                                          .withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Text(
                                      _getPaymentStatusLabel(order['payment_status'] ?? 'pending'),
                                      style: TextStyle(
                                        color: _getPaymentStatusColor(order['payment_status'] ?? 'pending'),
                                        fontSize: 12,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                  if (const ['lengopay', 'stripe'].contains(order['payment_method']))
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: Colors.blue.withOpacity(0.1),
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      child: const Text(
                                        '💳 Carte Bancaire',
                                        style: TextStyle(fontSize: 12),
                                      ),
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              CurrencyFormatter.format(
                                (order['grand_total'] ?? 0).toDouble(),
                                currency,
                              ),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFFD4AF37),
                              ),
                            ),
                            if (order['payment_status'] == 'partial' ||
                                order['payment_status'] == 'pending')
                              Text(
                                'Reste: ${CurrencyFormatter.format((order['total_remaining'] ?? 0).toDouble(), currency)}',
                                style: TextStyle(
                                  fontSize: 10,
                                  color: Colors.grey[600],
                                ),
                              ),
                          ],
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),

                    // Order Info Grid - CORRIGÉ pour éviter l'overflow
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.grey[50],
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: LayoutBuilder(
                        builder: (context, constraints) {
                          return Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              _buildInfoItem(
                                'Numéro',
                                order['order_number'] ?? 'N/A',
                                Icons.receipt,
                                constraints.maxWidth / 2 - 12,
                              ),
                              _buildInfoItem(
                                'Date',
                                _formatDate(order['created_at']),
                                Icons.calendar_today,
                                constraints.maxWidth / 2 - 12,
                              ),
                              _buildInfoItem(
                                'Paiement',
                                _getPaymentMethodLabel(order['payment_method'] ?? 'cod'),
                                Icons.payment,
                                constraints.maxWidth / 2 - 12,
                              ),
                              _buildInfoItem(
                                'Transporteur',
                                _getCarrierLabel(order['carrier'] ?? ''),
                                Icons.local_shipping,
                                constraints.maxWidth / 2 - 12,
                              ),
                            ],
                          );
                        },
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Items
                    const Text(
                      '📦 Articles commandés',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),

                    ...items.map((item) {
                      final product = item['product'] ?? {};
                      final images = product['images'] as List? ?? [];
                      
                      return Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          border: Border.all(color: Colors.grey[200]!),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Product Image
                            ClipRRect(
                              borderRadius: BorderRadius.circular(8),
                              child: SizedBox(
                                width: 60,
                                height: 60,
                                child: images.isNotEmpty
                                    ? CachedNetworkImage(
                                        imageUrl: _getFullImageUrl(images.first),
                                        fit: BoxFit.cover,
                                        placeholder: (context, url) => Container(
                                          color: Colors.grey[200],
                                          child: const Center(
                                            child: SizedBox(
                                              width: 20,
                                              height: 20,
                                              child: CircularProgressIndicator(strokeWidth: 2),
                                            ),
                                          ),
                                        ),
                                        errorWidget: (context, url, error) => Container(
                                          color: Colors.grey[200],
                                          child: const Icon(Icons.image, size: 30, color: Colors.grey),
                                        ),
                                      )
                                    : Container(
                                        color: Colors.grey[200],
                                        child: const Icon(Icons.image, size: 30, color: Colors.grey),
                                      ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    product['name'] ?? item['product_name'] ?? 'Produit',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w600,
                                      fontSize: 13,
                                    ),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 4),
                                  Wrap(
                                    spacing: 12,
                                    runSpacing: 4,
                                    children: [
                                      Text(
                                        'Qté: ${item['quantity']}',
                                        style: TextStyle(
                                          fontSize: 11,
                                          color: Colors.grey[600],
                                        ),
                                      ),
                                      Text(
                                        CurrencyFormatter.format(
                                          (item['unit_amount'] ?? 0).toDouble(),
                                          currency,
                                        ),
                                        style: TextStyle(
                                          fontSize: 11,
                                          color: Colors.grey[600],
                                        ),
                                      ),
                                    ],
                                  ),
                                  if (item['variation_json'] != null)
                                    Padding(
                                      padding: const EdgeInsets.only(top: 4),
                                      child: Text(
                                        _formatVariations(item['variation_json']),
                                        style: TextStyle(
                                          fontSize: 10,
                                          color: Colors.blue[700],
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              CurrencyFormatter.format(
                                (item['total_amount'] ?? 0).toDouble(),
                                currency,
                              ),
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                                color: Color(0xFFD4AF37),
                              ),
                            ),
                          ],
                        ),
                      );
                    }),

                    // Tracking if available
                    if (shipment != null) ...[
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.blue[50],
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.blue[200]!),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: Colors.blue[100],
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(
                                    Icons.local_shipping,
                                    color: Colors.blue,
                                    size: 20,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                const Text(
                                  'Suivi d\'expédition',
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            _buildTrackingInfo(
                              'Transporteur',
                              _getCarrierLabel(shipment['carrier'] ?? ''),
                            ),
                            if (shipment['tracking_number'] != null)
                              _buildTrackingInfo(
                                'Numéro de suivi',
                                shipment['tracking_number'],
                              ),
                            if (shipment['status'] != null)
                              _buildTrackingInfo(
                                'Statut',
                                _getShipmentStatus(shipment['status']),
                              ),
                            if (shipment['current_location'] != null)
                              _buildTrackingInfo(
                                'Dernière position',
                                shipment['current_location'],
                              ),
                            if (shipment['estimated_delivery_at'] != null)
                              _buildTrackingInfo(
                                'Livraison estimée',
                                _formatDate(shipment['estimated_delivery_at']),
                              ),
                          ],
                        ),
                      ),
                    ],

                    const SizedBox(height: 16),

                    // Order Actions
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () {
                              context.go(
                                '/orders/${order['id']}',
                                extra: {'fromSuccessPage': true});
                            },
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFFD4AF37),
                              side: const BorderSide(color: Color(0xFFD4AF37)),
                              padding: const EdgeInsets.symmetric(vertical: 12),
                            ),
                            child: const Text('Voir les détails'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        if (order['payment_status'] == 'pending' &&
                            !const ['lengopay', 'stripe'].contains(order['payment_method']))
                          Expanded(
                            child: ElevatedButton(
                              onPressed: () {
                                context.go('/orders/${order['id']}');
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.green,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                              ),
                              child: const Text('Payer maintenant'),
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            );
          }),

          // Summary & Actions
          if (_orders.isNotEmpty)
            SliverToBoxAdapter(
              child: Container(
                margin: const EdgeInsets.all(16),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.withOpacity(0.1),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => context.go('/products'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.grey[700],
                          side: BorderSide(color: Colors.grey[300]!),
                          padding: const EdgeInsets.symmetric(vertical: 12),
                        ),
                        child: const Text('Retour à la Boutique'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text(
                            'Total USD',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey,
                            ),
                          ),
                          Text(
                            '\$${_totalAmount.toStringAsFixed(2)}',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFFD4AF37),
                            ),
                          ),
                          Builder(
                            builder: (context) {
                              final totalItems = _orders.fold<int>(
                                0, 
                                (sum, order) => sum + ((order['items'] as List?)?.length ?? 0)
                              );
                              return Text(
                                '$_orderCount commande(s) | $totalItems article(s)',
                                style: TextStyle(
                                  fontSize: 10,
                                  color: Colors.grey[500],
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildInfoItem(String label, String value, IconData icon, double maxWidth) {
    return SizedBox(
      width: maxWidth,
      child: Row(
        children: [
          Icon(icon, size: 12, color: Colors.grey[600]),
          const SizedBox(width: 4),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 8,
                    color: Colors.grey[500],
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.w500,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTrackingInfo(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 11,
                color: Colors.grey[600],
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ),
          const SizedBox(width: 4),
          const Text(':'),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
              softWrap: true,
            ),
          ),
        ],
      ),
    );
  }
}