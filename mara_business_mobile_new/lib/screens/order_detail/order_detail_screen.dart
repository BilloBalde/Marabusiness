// lib/screens/order_detail/order_detail_screen.dart

import '../../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../core/providers/order_provider.dart';
import '../../core/models/order.dart';
import '../../widgets/common/loading_widget.dart';
import '../../utils/currency_formatter.dart';
import 'package:url_launcher/url_launcher.dart';
import 'payment_modal.dart';
import 'package:cached_network_image/cached_network_image.dart';

class OrderDetailScreen extends StatefulWidget {
  final int orderId;
  const OrderDetailScreen({super.key, required this.orderId});

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  bool _isLoading = true;
  String? _error;
  Order? _order;
  bool _fromSuccessPage = false;
  
  // Cancel modal state
  bool _showCancelModal = false;
  final TextEditingController _cancelReasonController = TextEditingController();
  bool _isCancelling = false;

  /// One of five near-identical helpers, each handling a case the others got
  /// wrong. ImageUrl owns the rule now. An empty path yields the placeholder
  /// rather than '', which rendered as a broken image.
  String _getFullImageUrl(String? path) => ImageUrl.resolve(path);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final args = GoRouterState.of(context).extra as Map<String, dynamic>?;
      _fromSuccessPage = args?['fromSuccessPage'] == true;
      _loadOrderDetails();
    });
  }

  @override
  void dispose() {
    _cancelReasonController.dispose();
    super.dispose();
  }

  Future<void> _loadOrderDetails() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final orderProvider = context.read<OrderProvider>();
      final success = await orderProvider.loadOrderDetails(widget.orderId);
      
      if (success && mounted) {
        setState(() {
          _order = orderProvider.currentOrder;
          _isLoading = false;
        });
      } else if (mounted) {
        setState(() {
          _error = orderProvider.error ?? 'Commande non trouvée';
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isLoading = false;
        });
      }
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

  void _handleBack() {
    context.read<OrderProvider>().refreshOrders();
    
    if (_fromSuccessPage) {
      context.go('/profile');
    } else {
      if (context.canPop()) {
        Navigator.pop(context);
      } else {
        context.go('/');
      }
    }
  }

  void _openCancelModal() {
    _cancelReasonController.clear();
    setState(() {
      _showCancelModal = true;
    });
  }

  void _closeCancelModal() {
    setState(() {
      _showCancelModal = false;
    });
  }

  Future<void> _cancelOrder() async {
    final reason = _cancelReasonController.text.trim();
    if (reason.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez indiquer la raison de l\'annulation'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() {
      _isCancelling = true;
    });

    try {
      final orderProvider = context.read<OrderProvider>();
      final success = await orderProvider.cancelOrder(_order!.id, reason: reason);
      
      if (success && mounted) {
        _closeCancelModal();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Commande annulée avec succès'),
            backgroundColor: Colors.green,
          ),
        );
        // Reload order details
        await _loadOrderDetails();
        // Refresh orders list
        await orderProvider.refreshOrders();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(orderProvider.error ?? 'Erreur lors de l\'annulation'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur: ${e.toString()}'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isCancelling = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      child: Scaffold(
        backgroundColor: Colors.grey[50],
        appBar: AppBar(
          title: const Text('Détails de la commande'),
          backgroundColor: Colors.white,
          foregroundColor: Colors.black,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: _handleBack,
          ),
        ),
        body: Stack(
          children: [
            _isLoading
                ? const LoadingWidget(message: 'Chargement...')
                : _error != null
                    ? _buildErrorView()
                    : _order == null
                        ? _buildNotFoundView()
                        : _buildOrderDetails(_order!),
            
            // Cancel Modal
            if (_showCancelModal)
              _buildCancelModal(),
          ],
        ),
      ),
    );
  }

  Widget _buildCancelModal() {
    return GestureDetector(
      onTap: _closeCancelModal,
      child: Container(
        color: Colors.black54,
        child: Center(
          child: GestureDetector(
            onTap: () {},
            child: Container(
              margin: const EdgeInsets.all(24),
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Icon
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.red.shade50,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      Icons.warning_amber_rounded,
                      color: Colors.red.shade700,
                      size: 32,
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  // Title
                  Text(
                    'Annuler la commande #${_order?.orderNumber ?? ''}',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 12),
                  
                  // Description
                  const Text(
                    'Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 20),
                  
                  // Reason Input
                  TextField(
                    controller: _cancelReasonController,
                    maxLines: 3,
                    decoration: InputDecoration(
                      hintText: 'Raison de l\'annulation *',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      filled: true,
                      fillColor: Colors.grey[50],
                      contentPadding: const EdgeInsets.all(12),
                    ),
                  ),
                  const SizedBox(height: 20),
                  
                  // Important Info Box
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.yellow.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.yellow.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.info_outline, color: Colors.yellow.shade800, size: 20),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Informations importantes',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Colors.yellow.shade800,
                                  fontSize: 12,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '• Les articles seront remis en stock\n'
                                '• Si un paiement a été effectué, il sera remboursé\n'
                                '• Le remboursement peut prendre 3-5 jours ouvrés\n'
                                '• Cette action ne peut pas être annulée',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.yellow.shade800,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  
                  // Buttons
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _closeCancelModal,
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text('Annuler'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: ElevatedButton(
                          onPressed: _isCancelling ? null : _cancelOrder,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.red,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _isCancelling
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Text('Confirmer'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 64, color: Colors.red),
          const SizedBox(height: 16),
          Text(
            _error!,
            style: const TextStyle(color: Colors.red),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: _loadOrderDetails,
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildNotFoundView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 64, color: Colors.red),
          const SizedBox(height: 16),
          const Text(
            'Commande non trouvée',
            style: TextStyle(color: Colors.red),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () => context.go('/profile'),
            child: const Text('Aller au profil'),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey[700],
                fontWeight: FontWeight.w500,
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w400,
              ),
              softWrap: true,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildOrderDetails(Order order) {
    final currency = order.currency;
    final isCancellable = order.status == 'new'; // Only new orders can be cancelled
    final isCancelled = order.status == 'cancelled';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Order Header with Cancel Button
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withValues(alpha: 0.1),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        'Commande #${order.orderNumber}',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: order.paymentStatusColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        order.paymentStatusLabel,
                        style: TextStyle(
                          color: order.paymentStatusColor,
                          fontWeight: FontWeight.w500,
                          fontSize: 12,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 16,
                  runSpacing: 8,
                  children: [
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.calendar_today, size: 14, color: Colors.grey),
                        const SizedBox(width: 4),
                        Text(
                          '${order.createdAt.day}/${order.createdAt.month}/${order.createdAt.year}',
                          style: const TextStyle(color: Colors.grey),
                        ),
                      ],
                    ),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.store, size: 14, color: Colors.grey),
                        const SizedBox(width: 4),
                        ConstrainedBox(
                          constraints: BoxConstraints(
                            maxWidth: MediaQuery.of(context).size.width - 160, // leave room for date and status
                          ),
                          child: Text(
                            order.vendorName ?? 'Vendeur',
                            style: const TextStyle(color: Colors.grey),
                            overflow: TextOverflow.ellipsis,
                            softWrap: true,
                          ),
                        ),
                      ],
                    ),
                    // Status Badge
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: order.statusColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        order.statusLabel,
                        style: TextStyle(
                          color: order.statusColor,
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
                
                // Cancel Button - Only for new orders
                if (isCancellable  && !isCancelled)
                  Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        OutlinedButton.icon(
                          onPressed: _openCancelModal,
                          icon: const Icon(Icons.cancel_outlined, size: 16),
                          label: const Text('Annuler la commande'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.red,
                            side: const BorderSide(color: Colors.red),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(20),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Items (unchanged)
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withValues(alpha: 0.1),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Articles',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 16),
                ...order.items.map((item) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: SizedBox(
                          width: 60,
                          height: 60,
                          child: item.productImage != null
                              ? CachedNetworkImage(
                                  imageUrl: _getFullImageUrl(item.productImage),
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
                                    child: const Icon(Icons.image_not_supported, size: 30, color: Colors.grey),
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
                              item.productName,
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            if (item.variationText.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                item.variationText,
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.grey[600],
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                            const SizedBox(height: 4),
                            Text(
                              '${item.quantity} × ${CurrencyFormatter.format(item.unitAmount, currency)}',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        CurrencyFormatter.format(item.totalAmount, currency),
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Color(0xFFD4AF37),
                        ),
                      ),
                    ],
                  ),
                )),
                const Divider(height: 24),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Sous-total',
                      style: TextStyle(color: Colors.grey),
                    ),
                    Text(
                      CurrencyFormatter.format(
                        order.grandTotal - order.shippingAmount,
                        currency,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Livraison',
                      style: TextStyle(color: Colors.grey),
                    ),
                    Text(
                      CurrencyFormatter.format(order.shippingAmount, currency),
                    ),
                  ],
                ),
                const Divider(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Total',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Text(
                      CurrencyFormatter.format(order.grandTotal, currency),
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFD4AF37),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Shipping Address (unchanged)
          if (order.shippingAddress != null)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withValues(alpha: 0.1),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Adresse de livraison',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    order.shippingAddress!.fullName,
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                  Text(order.shippingAddress!.streetAddress),
                  Text(
                    '${order.shippingAddress!.city}, ${order.shippingAddress!.state} ${order.shippingAddress!.zipCode}',
                  ),
                  Text(order.shippingAddress!.country),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.phone, size: 14, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text(order.shippingAddress!.phone),
                    ],
                  ),
                ],
              ),
            ),

          // Tracking Info (unchanged)
          if (order.latestShipment != null)
            Container(
              padding: const EdgeInsets.all(16),
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
                        child: const Icon(Icons.local_shipping, color: Colors.blue),
                      ),
                      const SizedBox(width: 12),
                      const Text(
                        'Suivi de livraison',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.blue,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _buildInfoRow(
                    'Transporteur:',
                    _getCarrierLabel(order.latestShipment!.carrier),
                  ),
                  if (order.latestShipment!.trackingNumber != null)
                    _buildInfoRow('N° suivi:', order.latestShipment!.trackingNumber!),
                  _buildInfoRow(
                    'Statut:',
                    order.latestShipment!.statusLabel,
                  ),
                  if (order.latestShipment!.currentLocation != null)
                    _buildInfoRow('Position:', order.latestShipment!.currentLocation!),
                  if (order.latestShipment!.estimatedDeliveryAt != null)
                    _buildInfoRow(
                      'Livraison estimée:',
                      '${order.latestShipment!.estimatedDeliveryAt!.day}/${order.latestShipment!.estimatedDeliveryAt!.month}/${order.latestShipment!.estimatedDeliveryAt!.year}'
                    ),
                  if (order.latestShipment!.trackingUrl != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 12),
                      child: ElevatedButton.icon(
                        onPressed: () async {
                          final url = Uri.parse(order.latestShipment!.trackingUrl!);
                          if (await canLaunchUrl(url)) {
                            await launchUrl(url, mode: LaunchMode.externalApplication);
                          }
                        },
                        icon: const Icon(Icons.open_in_browser, size: 16),
                        label: const Text('Suivre en ligne'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.blue,
                          foregroundColor: Colors.white,
                          minimumSize: const Size(double.infinity, 40),
                        ),
                      ),
                    ),
                ],
              ),
            ),

          // A payment the buyer has declared and the vendor has not confirmed
          // leaves the order at payment_status 'pending' — unconfirmed money
          // deliberately does not count towards the balance. That is correct
          // accounting but it made the screen below offer "payer" to someone who
          // had already handed cash to the courier, and tapping it declared the
          // same money twice. The server refuses the second declaration now (409);
          // this says why before they tap.
          if (order.hasPaymentAwaitingConfirmation && !isCancelled)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.orange[50],
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.orange[200]!),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.hourglass_top, color: Colors.orange[800]),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Paiement en attente de validation',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.orange[900],
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Vous avez déclaré ${CurrencyFormatter.format(order.declaredAwaitingConfirmation, order.currency)}. '
                          'Le vendeur doit confirmer la réception avant que la commande soit soldée.',
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.orange[900],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

          // Une commande en négociation renvoie vers la discussion, pas vers le
          // paiement : il n'y a pas encore de prix à régler.
          if (order.isNegotiating)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFD4AF37)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Négociation en cours',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Cette commande ne peut pas être réglée tant que le prix '
                    "n'est pas convenu avec la boutique.",
                    style: TextStyle(fontSize: 13, color: Colors.grey[700]),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => context.push('/negotiations/${order.id}'),
                      icon: const Icon(Icons.forum_outlined, size: 18),
                      label: const Text('Ouvrir la discussion'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFD4AF37),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                      ),
                    ),
                  ),
                ],
              ),
            ),

          // Payment Button for unpaid orders — hidden while a declaration is
          // still awaiting the vendor, so it cannot be submitted twice.
          if (!order.isNegotiating &&
              (order.paymentStatus == 'pending' || order.paymentStatus == 'partial') &&
              !order.hasPaymentAwaitingConfirmation &&
              !isCancelled)
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withValues(alpha: 0.1),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Paiement',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.yellow[50],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.yellow[300]!),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.info_outline, color: Colors.amber),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Montant restant: ${CurrencyFormatter.format(order.totalRemaining, currency)}',
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              const Text(
                                'Cliquez ci-dessous pour effectuer le paiement',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: () {
                            _showPaymentModal(order);
                          },
                          icon: const Icon(Icons.payment),
                          label: Text(
                            const ['lengopay', 'stripe'].contains(order.paymentMethod)
                                ? 'Payer par Carte' 
                                : 'Payer maintenant',
                          ),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 12),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  void _showPaymentModal(Order order) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => PaymentModal(
        order: order,
        onPaymentSuccess: () {
          _loadOrderDetails();
          context.read<OrderProvider>().refreshOrders();
        },
      ),
    );
  }
}