// lib/core/providers/cart_provider.dart - UPDATED

import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/cart.dart';

class CartProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  List<CartItem> _items = [];
  List<VendorCart> _vendors = [];
  int _totalItems = 0;
  double _subtotalUSD = 0;
  bool _isLoading = false;
  bool _isAddingToCart = false;
  String? _error;

  CartProvider(this._apiService);

  // Getters
  List<CartItem> get items => _items;
  List<VendorCart> get vendors => _vendors;
  int get totalItems => _totalItems;
  double get subtotalUSD => _subtotalUSD;
  bool get isLoading => _isLoading;
  bool get isAddingToCart => _isAddingToCart;
  String? get error => _error;
  bool get isEmpty => _items.isEmpty;

  // Load cart from API
  // In cart_provider.dart - Update loadCart method
  // In cart_provider.dart - Fix the loadCart method

Future<void> loadCart() async {
  _isLoading = true;
  _error = null;
  notifyListeners();

  try {
    logDebug('🔄 Loading cart from database...');
    final response = await _apiService.getCart();
    
    logDebug('📦 Cart response success: ${response.success}');
    
    if (response.success && response.data != null) {
      final data = response.data;
      logDebug('📦 Cart data type: ${data.runtimeType}');
      logDebug('📦 Cart data keys: ${data.keys}');
      
      // Clear previous data
      _items = [];
      _vendors = [];
      
      // Navigate to cart.vendors
      if (data.containsKey('cart') && data['cart'] is Map) {
        final cartData = data['cart'] as Map<String, dynamic>;
        
        if (cartData.containsKey('vendors') && cartData['vendors'] is List) {
          final vendorsData = cartData['vendors'] as List;
          logDebug('📦 Found ${vendorsData.length} vendors in cart');
          
          for (var vendorJson in vendorsData) {
            try {
              final vendor = VendorCart.fromJson(vendorJson as Map<String, dynamic>);
              _vendors.add(vendor);
              _items.addAll(vendor.items);
              logDebug('📦 Added vendor: ${vendor.vendorName} with ${vendor.items.length} items');
            } catch (e) {
              logDebug('❌ Error parsing vendor: $e');
            }
          }
        }
      }
      
      // Update totals
      _totalItems = _items.fold(0, (sum, item) => sum + item.quantity);
      _subtotalUSD = _vendors.fold(0, (sum, vendor) => sum + vendor.subtotalUsd);
      
      logDebug('✅ Cart loaded: ${_items.length} items, ${_vendors.length} vendors');
    } else {
      _error = response.message ?? 'Failed to load cart';
      logDebug('❌ Error loading cart: $_error');
    }
  } catch (e, stackTrace) {
    _error = e.toString();
    logDebug('❌ Exception loading cart: $e');
    logDebug('❌ Stack trace: $stackTrace');
  }

  _isLoading = false;
  notifyListeners();
}

  // Add item to cart
  Future<bool> addToCart(
    int vendorProductId,
    int quantity, {
    int? variationId,
    Map<String, dynamic>? selectedVariations,
    String? customNote,
  }) async {
    _isAddingToCart = true;
    _error = null;
    notifyListeners();

    try {
      logDebug('🔄 Adding to cart: vendorProductId=$vendorProductId, quantity=$quantity');
      
      final response = await _apiService.addToCart(
        vendorProductId,
        quantity,
        variationId: variationId,
        selectedVariations: selectedVariations,
        customNote: customNote,
      );
      
      logDebug('📦 Add to cart response: ${response.data}');
      
      if (response.success) {
        // Reload cart to get updated data
        await loadCart();
        return true;
      } else {
        _error = response.message;
        return false;
      }
    } catch (e) {
      _error = e.toString();
      logDebug('❌ Error adding to cart: $e');
      return false;
    } finally {
      _isAddingToCart = false;
      notifyListeners();
    }
  }

  // Remove item from cart
  Future<bool> removeItem(String cartKey) async {
    _error = null;
    
    try {
      logDebug('🔄 Removing item: $cartKey');
      final response = await _apiService.removeFromCart(cartKey);
      
      if (response.success) {
        await loadCart(); // Reload cart after removal
        return true;
      } else {
        _error = response.message;
        return false;
      }
    } catch (e) {
      _error = e.toString();
      return false;
    }
  }

  // Update item quantity
  Future<bool> updateQuantity(String cartKey, int quantity) async {
    _error = null;
    
    try {
      logDebug('🔄 Updating quantity: $cartKey -> $quantity');
      final response = await _apiService.updateCartItem(cartKey, quantity);
      
      if (response.success) {
        await loadCart(); // Reload cart after update
        return true;
      } else {
        _error = response.message;
        return false;
      }
    } catch (e) {
      _error = e.toString();
      return false;
    }
  }

  // Clear entire cart
  Future<bool> clearCart() async {
    _error = null;
    
    try {
      logDebug('🔄 Clearing cart');
      final response = await _apiService.clearCart();
      
      if (response.success) {
        await loadCart();
        return true;
      } else {
        _error = response.message;
        return false;
      }
    } catch (e) {
      _error = e.toString();
      return false;
    }
  }

  // Remove multiple items
  Future<void> removeItems(List<String> cartKeys) async {
    _error = null;
    
    try {
      for (var cartKey in cartKeys) {
        await _apiService.removeFromCart(cartKey);
      }
      await loadCart();
    } catch (e) {
      _error = e.toString();
    }
  }

  // Get total quantity for a specific vendor
  int getVendorItemCount(int vendorId) {
    final vendor = _vendors.firstWhere(
      (v) => v.vendorId == vendorId,
      orElse: () => VendorCart.empty(),
    );
    return vendor.items.fold(0, (sum, item) => sum + item.quantity);
  }

  // Get item count for a specific product
  int getProductQuantity(int vendorProductId) {
    return _items
        .where((item) => item.vendorProductId == vendorProductId)
        .fold(0, (sum, item) => sum + item.quantity);
  }

  // Check if product is in cart
  bool isInCart(int vendorProductId) {
    return _items.any((item) => item.vendorProductId == vendorProductId);
  }

  void resetCart() {
    _items = [];
    _vendors = [];
    _totalItems = 0;
    _subtotalUSD = 0;
    notifyListeners();
  }
}