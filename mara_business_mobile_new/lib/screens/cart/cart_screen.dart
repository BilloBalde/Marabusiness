// lib/screens/cart/cart_screen.dart - COMPLETE REDESIGN MATCHING LIVEWIRE

import '../../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/models/cart.dart';
import '../../core/providers/cart_provider.dart';
import '../../core/providers/auth_provider.dart';
import '../../widgets/empty_state.dart';
import '../../utils/currency_formatter.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final Set<String> _selectedItems = {};
  final Map<int, bool> _selectedVendors = {};
  bool _showDeleteSelectedModal = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadCart();
    });
  }

  Future<void> _loadCart() async {
    await context.read<CartProvider>().loadCart();
  }

  void _toggleVendor(int vendorId, List<CartItem> items) {
    setState(() {
      if (_selectedVendors.containsKey(vendorId)) {
        _selectedVendors.remove(vendorId);
        for (var item in items) {
          _selectedItems.remove(item.cartKey);
        }
      } else {
        _selectedVendors[vendorId] = true;
        for (var item in items) {
          _selectedItems.add(item.cartKey);
        }
      }
    });
  }

  void _toggleItem(String cartKey, int vendorId) {
    setState(() {
      if (_selectedItems.contains(cartKey)) {
        _selectedItems.remove(cartKey);
        _updateVendorCheckboxes(vendorId);
      } else {
        _selectedItems.add(cartKey);
        _updateVendorCheckboxes(vendorId);
      }
    });
  }

  void _updateVendorCheckboxes([int? changedVendorId]) {
    final cart = context.read<CartProvider>();
    
    // Clear all vendor selections first
    _selectedVendors.clear();
    
    // Group selected items by vendor
    final Map<int, List<String>> selectedByVendor = {};
    
    for (var vendor in cart.vendors) {
      for (var item in vendor.items) {
        if (_selectedItems.contains(item.cartKey)) {
          if (!selectedByVendor.containsKey(vendor.vendorId)) {
            selectedByVendor[vendor.vendorId] = [];
          }
          selectedByVendor[vendor.vendorId]!.add(item.cartKey);
        }
      }
    }
    
    // Check if all items in a vendor are selected
    for (var vendor in cart.vendors) {
      final vendorItems = vendor.items.map((item) => item.cartKey).toSet();
      final selectedVendorItems = selectedByVendor[vendor.vendorId] ?? [];
      
      if (selectedVendorItems.length == vendorItems.length && vendorItems.isNotEmpty) {
        _selectedVendors[vendor.vendorId] = true;
      }
    }
  }

  void _toggleSelectAll() {
    final cart = context.read<CartProvider>();
    setState(() {
      if (_selectedItems.length == cart.totalItems && cart.totalItems > 0) {
        _selectedItems.clear();
        _selectedVendors.clear();
      } else {
        for (var vendor in cart.vendors) {
          _selectedVendors[vendor.vendorId] = true;
          for (var item in vendor.items) {
            _selectedItems.add(item.cartKey);
          }
        }
      }
    });
  }

  double _calculateSelectedTotal() {
    final cart = context.read<CartProvider>();
    double total = 0;
    
    for (var vendor in cart.vendors) {
      for (var item in vendor.items) {
        if (_selectedItems.contains(item.cartKey)) {
          total += item.totalAmount * item.rateToUsd;
        }
      }
    }
    
    return total;
  }

  int _getSelectedCount() {
    return _selectedItems.length;
  }

  List<int> _getSelectedProductIds() {
    final cart = context.read<CartProvider>();
    List<int> ids = [];
    
    for (var vendor in cart.vendors) {
      for (var item in vendor.items) {
        if (_selectedItems.contains(item.cartKey)) {
          ids.add(item.vendorProductId);
        }
      }
    }
    
    return ids;
  }

  Future<void> _removeSelectedItems() async {
    if (_selectedItems.isEmpty) return;
    
    final cart = context.read<CartProvider>();
    await cart.removeItems(_selectedItems.toList());
    
    setState(() {
      _selectedItems.clear();
      _selectedVendors.clear();
      _showDeleteSelectedModal = false;
    });
  }

  String _getVariationText(CartItem item) {
    if (item.variationNote.isNotEmpty) {
      return item.variationNote;
    }
    
    // Handle selected variations if available
    if (item.selectedVariations.isNotEmpty) {
      final parts = <String>[];
      item.selectedVariations.forEach((key, value) {
        if (!['note', 'custom_note'].contains(key)) {
          parts.add('${key[0].toUpperCase() + key.substring(1)}: $value');
        }
      });
      if (parts.isNotEmpty) {
        return parts.join(', ');
      }
    }
    
    return '';
  }

  bool _hasVariation(CartItem item) {
    return item.variationNote.isNotEmpty || 
           (item.selectedVariations.isNotEmpty);
  }

  @override
  Widget build(BuildContext context) {
    final isAuthenticated = context.watch<AuthProvider>().isAuthenticated;

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Votre Panier',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        actions: [
          Consumer<CartProvider>(
            builder: (context, cart, _) {
              if (cart.isEmpty) return const SizedBox();
              
              return PopupMenuButton<String>(
                icon: const Icon(Icons.more_vert),
                itemBuilder: (context) => [
                  const PopupMenuItem(
                    value: 'clear',
                    child: Text('Vider le panier'),
                  ),
                ],
                onSelected: (value) {
                  if (value == 'clear') {
                    _showClearCartDialog();
                  }
                },
              );
            },
          ),
        ],
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, child) {
          if (cart.isLoading && cart.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (!isAuthenticated) {
            return EmptyState(
              icon: Icons.shopping_cart_outlined,
              message: 'Connectez-vous pour voir votre panier',
              buttonText: 'Se connecter',
              onButtonPressed: () => context.go('/login'),
            );
          }

          // CartProvider records an error on every failed load, and this screen
          // never read it — so a dropped connection or a server fault fell
          // through to the empty state and told the customer "votre panier est
          // vide". Their cart may be full; only the request failed. Worse, the
          // one way out offered was "Continuer Shopping", sending someone off to
          // rebuild a basket that was never lost. This must be checked before
          // isEmpty, since a failed load leaves the cart empty too.
          if (cart.error != null && cart.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.cloud_off, size: 64, color: Colors.grey),
                    const SizedBox(height: 16),
                    Text(
                      'Impossible de charger votre panier',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.grey[800],
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      cart.error!,
                      style: TextStyle(color: Colors.grey[600]),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () => cart.loadCart(),
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

          if (cart.isEmpty) {
            return EmptyState(
              icon: Icons.shopping_cart_outlined,
              message: 'Votre panier est vide',
              buttonText: 'Continuer Shopping →',
              onButtonPressed: () => context.go('/products'),
            );
          }

          return Stack(
            children: [
              Column(
                children: [
                  Expanded(
                    child: RefreshIndicator(
                      onRefresh: () => cart.loadCart(),
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: cart.vendors.length,
                        itemBuilder: (context, index) {
                          final vendor = cart.vendors[index];
                          final isVendorSelected = _selectedVendors.containsKey(vendor.vendorId);
                          
                          return Card(
                            margin: const EdgeInsets.only(bottom: 20),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Column(
                              children: [
                                // Vendor Header
                                Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: Colors.grey[50],
                                    borderRadius: const BorderRadius.vertical(
                                      top: Radius.circular(12),
                                    ),
                                  ),
                                  child: Row(
                                    children: [
                                      Checkbox(
                                        value: isVendorSelected,
                                        onChanged: (_) => _toggleVendor(vendor.vendorId, vendor.items),
                                        activeColor: const Color(0xFFD4AF37),
                                      ),
                                      const SizedBox(width: 8),
                                      CircleAvatar(
                                        radius: 16,
                                        backgroundColor: Colors.grey[200],
                                        backgroundImage: vendor.vendorLogo != null
                                            ? CachedNetworkImageProvider(
                                                ImageUrl.resolve(vendor.vendorLogo),
                                              )
                                            : null,
                                        child: vendor.vendorLogo == null
                                            ? const Icon(Icons.store, size: 16, color: Color(0xFFD4AF37))
                                            : null,
                                      ),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          vendor.vendorName,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16,
                                          ),
                                        ),
                                      ),
                                      Text(
                                        'Sélectionner',
                                        style: TextStyle(
                                          fontSize: 12,
                                          color: Colors.grey[600],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),

                                // Vendor Items
                                ...vendor.items.map((item) => _buildCartItem(item, vendor.vendorId)),

                                // Vendor Subtotal
                                Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    border: Border(
                                      top: BorderSide(color: Colors.grey[200]!),
                                    ),
                                  ),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        'Sous-total ${vendor.vendorName}:',
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w500,
                                          fontSize: 14,
                                        ),
                                      ),
                                      Column(
                                        crossAxisAlignment: CrossAxisAlignment.end,
                                        children: [
                                          Text(
                                            CurrencyFormatter.format(vendor.subtotal, vendor.currency),
                                            style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 16,
                                              color: Color(0xFFD4AF37),
                                            ),
                                          ),
                                          Text(
                                            '≈ ${CurrencyFormatter.format(vendor.subtotalUsd, 'USD')}',
                                            style: TextStyle(
                                              fontSize: 12,
                                              color: Colors.grey[600],
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
                        },
                      ),
                    ),
                  ),

                  // Bottom Summary Bar
                  if (!cart.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        boxShadow: [
                          BoxShadow(
                            color: Colors.grey.withValues(alpha: 0.3),
                            offset: const Offset(0, -2),
                            blurRadius: 4,
                          ),
                        ],
                      ),
                      child: SafeArea(
                        child: Column(
                          children: [
                            // Summary Row - Matching Livewire
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      'Articles sélectionnés: ${_getSelectedCount()}',
                                      style: TextStyle(
                                        fontSize: 14,
                                        color: Colors.grey[700],
                                      ),
                                    ),
                                    Text(
                                      'Vendeurs: ${_selectedVendors.length}',
                                      style: TextStyle(
                                        fontSize: 14,
                                        color: Colors.grey[700],
                                      ),
                                    ),
                                  ],
                                ),
                                Column(
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
                                      '\$${_calculateSelectedTotal().toStringAsFixed(2)}',
                                      style: const TextStyle(
                                        fontSize: 20,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFFD4AF37),
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                            const SizedBox(height: 16),

                            // Action Buttons - Matching Livewire
                            if (_selectedItems.isNotEmpty)
                              Column(
                                children: [
                                  Row(
                                    children: [
                                      Expanded(
                                        child: OutlinedButton(
                                          onPressed: () {
                                            setState(() {
                                              _showDeleteSelectedModal = true;
                                            });
                                          },
                                          style: OutlinedButton.styleFrom(
                                            padding: const EdgeInsets.symmetric(vertical: 14),
                                            side: const BorderSide(color: Colors.red),
                                            foregroundColor: Colors.red,
                                          ),
                                          child: const Text('Remove selected items'),
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: _buildCheckoutButton(),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 8),
                                  OutlinedButton(
                                    onPressed: _showClearCartDialog,
                                    style: OutlinedButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(vertical: 14),
                                      side: BorderSide(color: Colors.grey[300]!),
                                      foregroundColor: Colors.grey[700],
                                    ),
                                    child: const Text('Clear cart'),
                                  ),
                                ],
                              )
                            else
                              Column(
                                children: [
                                  _buildCheckoutButton(),
                                  const SizedBox(height: 8),
                                  OutlinedButton(
                                    onPressed: _showClearCartDialog,
                                    style: OutlinedButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(vertical: 14),
                                      side: BorderSide(color: Colors.grey[300]!),
                                      foregroundColor: Colors.grey[700],
                                    ),
                                    child: const Text('Clear cart'),
                                  ),
                                ],
                              ),

                            const SizedBox(height: 8),

                            // Additional Info - Matching Livewire
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: Colors.grey[50],
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.info_outline,
                                    size: 14,
                                    color: Colors.grey[600],
                                  ),
                                  const SizedBox(width: 4),
                                  Expanded(
                                    child: Text(
                                      'Les prix en gros sont automatiquement appliqués selon la quantité totale par produit',
                                      style: TextStyle(
                                        fontSize: 10,
                                        color: Colors.grey[600],
                                      ),
                                    ),
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

              // Delete Selected Modal
              if (_showDeleteSelectedModal)
                Positioned.fill(
                  child: Container(
                    color: Colors.black54,
                    child: Center(
                      child: Container(
                        margin: const EdgeInsets.all(16),
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: Colors.red[50],
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.delete_outline,
                                color: Colors.red,
                                size: 32,
                              ),
                            ),
                            const SizedBox(height: 16),
                            const Text(
                              'Remove selected items?',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 8),
                            const Text(
                              'This will delete all selected items from your cart.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: Colors.grey),
                            ),
                            const SizedBox(height: 20),
                            Row(
                              children: [
                                Expanded(
                                  child: TextButton(
                                    onPressed: () {
                                      setState(() {
                                        _showDeleteSelectedModal = false;
                                      });
                                    },
                                    child: const Text('Cancel'),
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: ElevatedButton(
                                    onPressed: _removeSelectedItems,
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.red,
                                      foregroundColor: Colors.white,
                                    ),
                                    child: const Text('Yes, delete'),
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
            ],
          );
        },
      ),
    );
  }

  Widget _buildCheckoutButton() {
    final isAuthenticated = context.watch<AuthProvider>().isAuthenticated;
    final selectedCount = _getSelectedCount();

    if (selectedCount == 0) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 14),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Colors.grey[300],
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          'Passer à la caisse',
          style: TextStyle(
            color: Colors.grey[600],
            fontWeight: FontWeight.bold,
          ),
        ),
      );
    }

    if (!isAuthenticated) {
      return Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.red[50],
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.red[200]!),
            ),
            child: Row(
              children: [
                Icon(Icons.error_outline, color: Colors.red[700], size: 16),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Connectez-vous pour passer commande',
                    style: TextStyle(
                      color: Colors.red[700],
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          ElevatedButton(
            onPressed: () {
              final selectedIds = _getSelectedProductIds();
              context.go('/login?redirect=/checkout?selected_ids=${selectedIds.join(',')}');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFD4AF37),
              foregroundColor: Colors.white,
              minimumSize: const Size(double.infinity, 48),
            ),
            child: const Text('Se connecter'),
          ),
        ],
      );
    }

    return Column(
      children: [
        ElevatedButton(
          onPressed: () {
            final selectedIds = _getSelectedProductIds();
            context.go('/checkout?selected_ids=${selectedIds.join(',')}');
          },
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFFD4AF37),
            foregroundColor: Colors.white,
            minimumSize: const Size(double.infinity, 48),
          ),
          child: const Text('Passer à la caisse'),
        ),
        const SizedBox(height: 4),
        Text(
          '$selectedCount article(s) sélectionné(s)',
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey[500],
          ),
        ),
      ],
    );
  }

  Future<void> _showClearCartDialog() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (BuildContext dialogContext) => AlertDialog(
        title: const Text('Vider le panier'),
        content: const Text('Êtes-vous sûr de vouloir vider votre panier?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false), // ✅ return false
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(true), // ✅ return true
            child: const Text('Vider'),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      await context.read<CartProvider>().clearCart();
      setState(() {
        _selectedItems.clear();
        _selectedVendors.clear();
      });
    }
  }

  Widget _buildCartItem(CartItem item, int vendorId) {
  final hasVariation = _hasVariation(item);
  final variationText = _getVariationText(item);
  final isWholesaleApplied = item.wholesaleApplied ?? false;
  
  // Créer le controller et le focus node
  final quantityController = TextEditingController(text: item.quantity.toString());
  final FocusNode quantityFocusNode = FocusNode();

  return StatefulBuilder(
    builder: (context, setState) {
      // Mettre à jour le controller quand la quantité de l'item change
      if (quantityController.text != item.quantity.toString()) {
        quantityController.text = item.quantity.toString();
      }

      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          border: Border(
            bottom: BorderSide(color: Colors.grey[100]!),
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Checkbox
            SizedBox(
              width: 24,
              height: 24,
              child: Checkbox(
                value: _selectedItems.contains(item.cartKey),
                onChanged: (_) => _toggleItem(item.cartKey, vendorId),
                activeColor: const Color(0xFFD4AF37),
                materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                visualDensity: VisualDensity.compact,
              ),
            ),
            const SizedBox(width: 8),

            // Product Image
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: CachedNetworkImage(
                imageUrl: ImageUrl.resolve(item.image),
                width: 70,
                height: 70,
                fit: BoxFit.cover,
                placeholder: (_, __) => Container(
                  color: Colors.grey[300],
                  child: const Center(
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                ),
                errorWidget: (_, __, ___) => Container(
                  color: Colors.grey[300],
                  child: const Icon(Icons.image, color: Colors.grey, size: 30),
                ),
              ),
            ),
            const SizedBox(width: 12),

            // Product Details
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Product Name
                  Text(
                    item.productName,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  
                  // Variation
                  if (hasVariation)
                    Container(
                      margin: const EdgeInsets.only(top: 4),
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: Colors.blue[50],
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        '📝 Variation: $variationText',
                        style: TextStyle(
                          fontSize: 10,
                          color: Colors.blue[800],
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),

                  // Wholesale Badge
                  if (isWholesaleApplied)
                    Container(
                      margin: const EdgeInsets.only(top: 4),
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.green[100],
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        '📦 Prix en gros',
                        style: TextStyle(
                          fontSize: 9,
                          color: Colors.green[800],
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),

                  // Unit Price
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      CurrencyFormatter.format(item.unitAmount, item.currency),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFD4AF37),
                        fontSize: 14,
                      ),
                    ),
                  ),

                  const SizedBox(height: 6),

                  // Quantity Controls with Editable TextField
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Decrease button
                      InkWell(
                        onTap: item.quantity > 1
                            ? () async {
                                final newQuantity = item.quantity - 1;
                                quantityController.text = newQuantity.toString();
                                await context.read<CartProvider>().updateQuantity(
                                      item.cartKey,
                                      newQuantity,
                                    );
                              }
                            : null,
                        child: Container(
                          width: 28,
                          height: 28,
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey[300]!),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Icon(
                            Icons.remove,
                            size: 14,
                            color: item.quantity > 1 ? Colors.black : Colors.grey[400],
                          ),
                        ),
                      ),
                      
                      // Editable Quantity TextField
                      SizedBox(
                        width: 50,
                        height: 28,
                        child: TextField(
                          controller: quantityController,
                          focusNode: quantityFocusNode,
                          textAlign: TextAlign.center,
                          keyboardType: TextInputType.number,
                          decoration: InputDecoration(
                            contentPadding: EdgeInsets.zero,
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(4),
                              borderSide: BorderSide(color: Colors.grey[300]!),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(4),
                              borderSide: BorderSide(color: Colors.grey[300]!),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(4),
                              borderSide: const BorderSide(color: Color(0xFFD4AF37)),
                            ),
                          ),
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w500,
                          ),
                          onSubmitted: (value) async {
                            await _updateQuantity(item, value, quantityController, quantityFocusNode);
                          },
                          onTapOutside: (event) {
                            quantityFocusNode.unfocus();
                            _updateQuantityOnBlur(item, quantityController);
                          },
                        ),
                      ),
                      
                      // Increase button
                      InkWell(
                        onTap: () async {
                          final newQuantity = item.quantity + 1;
                          quantityController.text = newQuantity.toString();
                          await context.read<CartProvider>().updateQuantity(
                                item.cartKey,
                                newQuantity,
                              );
                        },
                        child: Container(
                          width: 28,
                          height: 28,
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey[300]!),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: const Icon(Icons.add, size: 14),
                        ),
                      ),
                    ],
                  ),

                  // Total for this item
                  if (item.quantity > 1)
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'Total ',
                            style: TextStyle(
                              fontSize: 10,
                              color: Colors.grey[600],
                            ),
                          ),
                          Text(
                            CurrencyFormatter.format(item.totalAmount, item.currency),
                            style: TextStyle(
                              fontSize: 10,
                              color: Colors.grey[600],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),

                  // Wholesale info
                  if (isWholesaleApplied && item.quantity > 1)
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(
                        '${item.quantity} × ${CurrencyFormatter.format(item.unitAmount, item.currency)}',
                        style: TextStyle(
                          fontSize: 9,
                          color: Colors.green[700],
                        ),
                      ),
                    ),
                ],
              ),
            ),

            // Remove Button
            SizedBox(
              width: 28,
              height: 28,
              child: IconButton(
                onPressed: () async {
                  final confirmed = await showDialog<bool>(
                    context: context,
                    builder: (_) => AlertDialog(
                      title: const Text('Retirer du panier'),
                      content: const Text('Voulez-vous retirer cet article?'),
                      actions: [
                        TextButton(
                          onPressed: () => context.pop(),
                          child: const Text('Annuler'),
                        ),
                        TextButton(
                          onPressed: () => context.pop(),
                          child: const Text('Retirer'),
                        ),
                      ],
                    ),
                  );
                  
                  if (confirmed == true && mounted) {
                    await context.read<CartProvider>().removeItem(item.cartKey);
                    _selectedItems.remove(item.cartKey);
                    _updateVendorCheckboxes(vendorId);
                  }
                },
                icon: const Icon(Icons.close, size: 14),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              ),
            ),
          ],
        ),
      );
    },
  );
}

// Helper methods
Future<void> _updateQuantity(CartItem item, String value, TextEditingController controller, FocusNode focusNode) async {
  int newQuantity = int.tryParse(value) ?? 1;
  if (newQuantity < 1) newQuantity = 1;
  if (newQuantity > 999) newQuantity = 999;
  
  controller.text = newQuantity.toString();
  focusNode.unfocus();
  
  await context.read<CartProvider>().updateQuantity(
        item.cartKey,
        newQuantity,
      );
}

Future<void> _updateQuantityOnBlur(CartItem item, TextEditingController controller) async {
  int newQuantity = int.tryParse(controller.text) ?? 1;
  if (newQuantity < 1) newQuantity = 1;
  if (newQuantity > 999) newQuantity = 999;
  
  if (controller.text != newQuantity.toString()) {
    controller.text = newQuantity.toString();
  }
  
  if (newQuantity != item.quantity) {
    await context.read<CartProvider>().updateQuantity(
          item.cartKey,
          newQuantity,
        );
  }
}
}