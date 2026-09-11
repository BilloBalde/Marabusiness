// lib/screens/order_detail/payment_modal.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart'; // Add this for context.read
import 'package:url_launcher/url_launcher.dart';
import '../../core/models/order.dart';
import '../../utils/currency_formatter.dart';
import '../../services/api_service.dart'; // Add this for ApiService

class PaymentModal extends StatefulWidget {
  final Order order;
  final VoidCallback onPaymentSuccess;

  const PaymentModal({
    super.key,
    required this.order,
    required this.onPaymentSuccess,
  });

  @override
  State<PaymentModal> createState() => _PaymentModalState();
}

class _PaymentModalState extends State<PaymentModal> {
  String _selectedMethod = '';
  bool _isProcessing = false;

  @override
  Widget build(BuildContext context) {
    final order = widget.order;
    final currency = order.currency;
    final remainingAmount = order.totalRemaining;
    final isGNF = currency.toUpperCase() == 'GNF';

    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      minChildSize: 0.5,
      maxChildSize: 0.8,
      builder: (context, scrollController) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            children: [
              // Handle
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              
              // Header
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.yellow[100],
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.lock, color: Color(0xFFD4AF37)),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Paiement',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            'Commande #${order.orderNumber}',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
              ),

              const Divider(height: 1),

              // Content
              Expanded(
                child: SingleChildScrollView(
                  controller: scrollController,
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Payment Methods
                      const Text(
                        'Méthode de paiement',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 12),
                      
                      // Payment Options Grid (ONLY COD and LengoPay active)
                      Row(
                        children: [
                          // COD - Always active
                          Expanded(
                            child: _buildPaymentOption(
                              value: 'cod',
                              label: 'Cash Livraison',
                              icon: Icons.money,
                              enabled: true,
                            ),
                          ),
                          const SizedBox(width: 12),
                          // LengoPay - Active only for GNF
                          Expanded(
                            child: _buildPaymentOption(
                              value: 'lengopay',
                              label: 'LengoPay',
                              icon: Icons.account_balance,
                              enabled: isGNF,
                            ),
                          ),
                        ],
                      ),

                      // Show message if GNF required for LengoPay
                      if (_selectedMethod == 'lengopay' && !isGNF)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(
                            'LengoPay est disponible uniquement pour les paiements en GNF',
                            style: TextStyle(
                              color: Colors.orange[700],
                              fontSize: 12,
                            ),
                          ),
                        ),

                      if (_selectedMethod.isNotEmpty)
                        const SizedBox(height: 16),

                      // Error message for payment method
                      if (_selectedMethod.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(
                            'Veuillez sélectionner un mode de paiement',
                            style: TextStyle(
                              color: Colors.red[700],
                              fontSize: 12,
                            ),
                          ),
                        ),

                      // COD is selected - No additional fields needed
                      if (_selectedMethod == 'cod')
                        Container(
                          margin: const EdgeInsets.only(top: 16),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.green[50],
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.green[200]!),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.check_circle, color: Colors.green[700]),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Text(
                                      'Paiement à la livraison',
                                      style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 14,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      'Vous paierez ${CurrencyFormatter.format(remainingAmount, currency)} à la réception de votre commande.',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey[600],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),

                      // LengoPay Message
                      if (_selectedMethod == 'lengopay' && isGNF)
                        _buildGatewayMessage(
                          'Vous serez redirigé vers LengoPay pour payer :',
                          remainingAmount,
                          currency,
                          Colors.purple,
                        ),
                    ],
                  ),
                ),
              ),

              // Submit Button
              Padding(
                padding: const EdgeInsets.all(16),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _isProcessing ? null : _processPayment,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD4AF37),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    child: _isProcessing
                        ? const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              ),
                              SizedBox(width: 8),
                              Text('Traitement...'),
                            ],
                          )
                        : Text(
                            _selectedMethod == 'cod' 
                                ? 'Confirmer la commande' 
                                : 'Payer maintenant',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildPaymentOption({
    required String value,
    required String label,
    required IconData icon,
    required bool enabled,
  }) {
    final isSelected = _selectedMethod == value;
    
    return GestureDetector(
      onTap: enabled ? () {
        setState(() {
          _selectedMethod = value;
        });
      } : null,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
        decoration: BoxDecoration(
          color: enabled
              ? (isSelected ? Colors.yellow[50] : Colors.white)
              : Colors.grey[100],
          border: Border.all(
            color: isSelected && enabled
                ? const Color(0xFFD4AF37)
                : Colors.grey[300]!,
            width: isSelected && enabled ? 2 : 1,
          ),
          borderRadius: BorderRadius.circular(12),
          boxShadow: isSelected && enabled
              ? [
                  BoxShadow(
                    color: const Color(0xFFD4AF37).withValues(alpha: 0.2),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  )
                ]
              : null,
        ),
        child: Column(
          children: [
            Icon(
              icon,
              color: enabled
                  ? (isSelected ? const Color(0xFFD4AF37) : Colors.grey[700])
                  : Colors.grey[400],
              size: 28,
            ),
            const SizedBox(height: 8),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                color: enabled ? Colors.black87 : Colors.grey[500],
              ),
              textAlign: TextAlign.center,
            ),
            if (!enabled && value == 'lengopay')
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  'GNF only',
                  style: TextStyle(
                    fontSize: 9,
                    color: Colors.grey[500],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildGatewayMessage(String message, double amount, String currency, Color color) {
    return Container(
      margin: const EdgeInsets.only(top: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              Icons.lock_outline,
              color: color,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  message,
                  style: TextStyle(
                    color: color,
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  CurrencyFormatter.format(amount, currency),
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: color,
                    fontSize: 16,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _processPayment() async {
    // Validate
    if (_selectedMethod.isEmpty) {
      _showError('Veuillez sélectionner un mode de paiement');
      return;
    }

    setState(() => _isProcessing = true);

    try {
      final apiService = context.read<ApiService>();
      
      if (_selectedMethod == 'cod') {
        final response = await apiService.createPaymentSession(
          widget.order.id,
          'cod',
        );
        if (!mounted) return;

        if (response.success) {
          Navigator.pop(context);
          widget.onPaymentSuccess();
          // Not "confirmée" — the buyer has declared the cash payment, the vendor
          // still has to confirm the courier collected it. Saying it was confirmed
          // is what made buyers think a delivery was settled when it was not.
          _showSuccess('Paiement à la livraison enregistré. En attente de validation du vendeur.');
        } else {
          // This branch did not exist: the server refusing (a payment already
          // declared, an expired session) left the button spinning back to idle
          // with no message at all, so the buyer simply tapped again.
          _showError(response.message ?? 'Erreur de paiement');
        }
      }
      else if (_selectedMethod == 'lengopay') {
        // Create LengoPay session
        final response = await apiService.createPaymentSession(
          widget.order.id,
          'lengopay',
        );
        
        if (response.success && mounted) {
          final paymentUrl = response.data['payment_url'];
          
          // Close modal
          Navigator.pop(context);
          
          // Open in browser
          final Uri url = Uri.parse(paymentUrl);
          if (await canLaunchUrl(url)) {
            await launchUrl(url, mode: LaunchMode.externalApplication);
          } else {
            _showError('Impossible d\'ouvrir la page de paiement');
          }
        } else {
          _showError(response.message ?? 'Erreur de paiement');
        }
      }
    } catch (e) {
      _showError('Erreur: $e');
    } finally {
      if (mounted) setState(() => _isProcessing = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
      ),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
      ),
    );
  }
}