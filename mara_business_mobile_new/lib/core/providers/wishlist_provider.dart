import '../../utils/app_logger.dart';
import 'dart:convert';
import 'package:crypto/crypto.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/storage_service.dart';

class WishlistProvider extends ChangeNotifier {
  final ApiService _apiService;
  final StorageService _storageService;
  
  List<Map<String, dynamic>> _items = [];
  bool _isLoading = false;
  String? _error;
  int _totalCount = 0;

  WishlistProvider(this._apiService, this._storageService) {
    _loadWishlistFromStorage();
  }

  // Getters
  List<Map<String, dynamic>> get items => _items;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get totalCount => _totalCount;

  // Load wishlist from storage
  Future<void> _loadWishlistFromStorage() async {
    final wishlistJson = _storageService.getString('wishlist');
    if (wishlistJson != null) {
      try {
        _items = List<Map<String, dynamic>>.from(json.decode(wishlistJson));
        _totalCount = _items.length;
      } catch (e) {
        logDebug('Error loading wishlist from storage: $e');
      }
    }
    notifyListeners();
  }

  // Save wishlist to storage
  Future<void> _saveWishlistToStorage() async {
    await _storageService.setString('wishlist', json.encode(_items));
  }

  // Generate wishlist key matching backend logic
  String _generateWishlistKey(int vendorProductId, int? variationId, Map<String, dynamic>? selectedVariations) {
    if (variationId != null) {
      return 'wish_${vendorProductId}_var_$variationId';
    }
    
    if (selectedVariations != null && selectedVariations.isNotEmpty) {
      final sortedMap = Map.fromEntries(selectedVariations.entries.toList()..sort((a, b) => a.key.compareTo(b.key)));
      return 'wish_${vendorProductId}_${md5.convert(utf8.encode(json.encode(sortedMap)))}';
    }
    
    return 'wish_${vendorProductId}_simple';
  }

  // Check if product is in wishlist
  bool isInWishlist(int? vendorProductId) {
    if (vendorProductId == null) return false;
    return _items.any((item) => item['vendor_product_id'] == vendorProductId);
  }

  // Get wishlist item by key
  Map<String, dynamic>? getWishlistItemByKey(String wishlistKey) {
    try {
      return _items.firstWhere((item) => item['wishlist_key'] == wishlistKey);
    } catch (e) {
      return null;
    }
  }

  // Add to wishlist - NO AUTH REQUIRED
  // In wishlist_provider.dart
Future<bool> addToWishlist(
  int vendorProductId, {
  int? variationId,
  Map<String, dynamic>? selectedVariations,
}) async {
  _isLoading = true;
  _error = null;
  notifyListeners();

  try {
    // Generate wishlist key for local check
    final wishlistKey = _generateWishlistKey(vendorProductId, variationId, selectedVariations);
    
    // Check if already exists locally
    if (_items.any((item) => item['wishlist_key'] == wishlistKey)) {
      _isLoading = false;
      return true;
    }

    // Call API
    final response = await _apiService.addToWishlist(
      vendorProductId,
      variationId: variationId,
      selectedAttributes: selectedVariations,
    );
    
    // DEBUG: Print the actual response from your API
    //logDebug('🔵 ADD TO WISHLIST RESPONSE:');
    //logDebug('🔵 Success: ${response.success}');
    //logDebug('🔵 Message: ${response.message}');
    //logDebug('🔵 Data: ${response.data}');
    //logDebug('🔵 Data type: ${response.data.runtimeType}');
    
    if (response.success) {
      // Don't rely on response data, just refresh the whole wishlist
      await refreshWishlist();
      
      _isLoading = false;
      notifyListeners();
      return true;
    } else {
      _error = response.message ?? 'Failed to add to wishlist';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  } catch (e) {
    logDebug('🔴 Error adding to wishlist: $e');
    _error = e.toString();
    _isLoading = false;
    notifyListeners();
    return false;
  }
}
  // Remove from wishlist by vendor product ID
  Future<bool> removeFromWishlist(int vendorProductId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      // Find the item to get its wishlist key
      final item = _items.firstWhere(
        (item) => item['vendor_product_id'] == vendorProductId,
        orElse: () => <String, dynamic>{},
      );
      
      if (item.isEmpty) {
        _isLoading = false;
        return false;
      }

      final wishlistKey = item['wishlist_key'];
      
      // Call API
      final response = await _apiService.removeFromWishlistByKey(wishlistKey);
      
      // Remove from local list
      _items.removeWhere((item) => item['vendor_product_id'] == vendorProductId);
      _totalCount = _items.length;
      await _saveWishlistToStorage();
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Remove by wishlist key
  Future<bool> removeFromWishlistByKey(String wishlistKey) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.removeFromWishlistByKey(wishlistKey);
      
      _items.removeWhere((item) => item['wishlist_key'] == wishlistKey);
      _totalCount = _items.length;
      await _saveWishlistToStorage();
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Clear wishlist
  Future<void> clearWishlist() async {
    _items = [];
    _totalCount = 0;
    await _saveWishlistToStorage();
    notifyListeners();
  }

  // Refresh wishlist from API - NO AUTH REQUIRED
  Future<void> refreshWishlist() async {
  _isLoading = true;
  notifyListeners();

  try {
    final response = await _apiService.getWishlist();
    
    //logDebug('🔵 WISHLIST RESPONSE: ${response.data}');
    
    if (response.success && response.data != null) {
      final responseData = response.data;
      List<dynamic> items = [];
      
      // Handle your API response structure
      if (responseData is Map<String, dynamic>) {
        if (responseData.containsKey('data')) {
          final data = responseData['data'];
          if (data is Map && data.containsKey('items')) {
            items = data['items'] as List? ?? [];
          }
        }
      }
      
      //logDebug('🔵 Items count: ${items.length}');
      
      _items = items.map((item) {
        if (item is! Map<String, dynamic>) {
          return <String, dynamic>{};
        }
        
        return {
          'wishlist_key': item['wishlist_key'] ?? '',
          'vendor_product_id': item['vendor_product_id'] ?? 0,
          'product_id': item['product_id'] ?? 0,
          'vendor_id': item['vendor_id'] ?? 0,
          'product_name': item['product_name'] ?? 'Product',
          'image': item['image'],
          'base_price': (item['base_price'] ?? 0).toDouble(),
          'currency': item['currency'] ?? 'USD',
          'rate_to_usd': item['rate_to_usd'] ?? 1,
          'variation_id': item['variation_id'],
          'selected_variations': item['selected_variations'] ?? {},
          'has_variations': item['has_variations'] ?? false,
          'stock': item['stock'] ?? 0,
        };
      }).where((item) => item['vendor_product_id'] != 0).toList();
      
      _totalCount = _items.length;
      await _saveWishlistToStorage();
    }
  } catch (e) {
    logDebug('🔴 Error refreshing wishlist: $e');
    _error = e.toString();
  }

  _isLoading = false;
  notifyListeners();
}
  // Get display price in vendor's currency
  String getDisplayPrice(Map<String, dynamic> item) {
    final price = item['base_price'] ?? 0;
    final currency = item['currency'] ?? 'USD';
    return '${_getCurrencySymbol(currency)} ${price.toStringAsFixed(2)}';
  }

  String _getCurrencySymbol(String currencyCode) {
    switch (currencyCode) {
      case 'USD': return '\$';
      case 'EUR': return '€';
      case 'GBP': return '£';
      case 'GNF': return 'FG';
      case 'CNY': return '¥';
      default: return '\$';
    }
  }

  // Toggle wishlist (add if not exists, remove if exists)
  Future<bool> toggleWishlist(
    int vendorProductId, {
    int? variationId,
    Map<String, dynamic>? selectedVariations,
  }) async {
    if (isInWishlist(vendorProductId)) {
      return await removeFromWishlist(vendorProductId);
    } else {
      return await addToWishlist(vendorProductId, variationId: variationId, selectedVariations: selectedVariations);
    }
  }
}