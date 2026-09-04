// lib/core/models/cart.dart - UPDATED with CBM calculation

import 'package:flutter/material.dart';

class CartItem {
  final String cartKey;
  final int vendorProductId;
  final int productId;
  final int vendorId;
  final String vendorName;
  final String productName;
  final String image;
  final int quantity;
  final double basePrice;
  final String currency;
  final double rateToUsd;
  final int? variationId;
  final Map<String, dynamic> selectedVariations;
  final String variationNote;
  final bool wholesaleApplied;
  final List<dynamic> wholesaleTiers;
  final double unitAmount;
  final double totalAmount;
  // Add these fields for shipping calculations
  final double? weight;      // Weight per unit in kg
  final double? length;      // Length in cm
  final double? width;       // Width in cm
  final double? height;      // Height in cm
  final double? _cbm;        // Private field for cached CBM calculation

  CartItem({
    required this.cartKey,
    required this.vendorProductId,
    required this.productId,
    required this.vendorId,
    required this.vendorName,
    required this.productName,
    required this.image,
    required this.quantity,
    required this.basePrice,
    required this.currency,
    required this.rateToUsd,
    this.variationId,
    this.weight,
    this.length,
    this.width,
    this.height,
    double? cbm,
    required this.selectedVariations,
    required this.variationNote,
    required this.wholesaleApplied,
    required this.wholesaleTiers,
    required this.unitAmount,
    required this.totalAmount,
  }) : _cbm = cbm;

  /// Calculate CBM (Cubic Meters) for a single unit
  /// Formula: (length × width × height) / 1,000,000 (since dimensions are in cm)
  double get cbm {
    // If cached value exists, return it
    if (_cbm != null) return _cbm!;
    
    // Calculate from dimensions if available
    if (length != null && width != null && height != null) {
      return (length! * width! * height!) / 1000000;
    }
    
    // Default fallback: estimate based on weight (rough approximation)
    // This is a fallback - ideally dimensions should be provided
    if (weight != null && weight! > 0) {
      // Rough estimate: 1kg ≈ 0.005 CBM (average density of 200 kg/m³)
      return weight! * 0.005;
    }
    
    return 0.0;
  }

  /// Get total CBM for this item (unit CBM × quantity)
  double get totalCbm => cbm * quantity;

  /// Get total weight for this item (unit weight × quantity)
  double get totalWeight => (weight ?? 0) * quantity;

  /// Get item dimensions as a formatted string
  String get dimensions {
    if (length != null && width != null && height != null) {
      return '${length!.toStringAsFixed(0)}×${width!.toStringAsFixed(0)}×${height!.toStringAsFixed(0)} cm';
    }
    return 'N/A';
  }

  /// Get CBM as formatted string
  String get formattedCbm {
    if (cbm > 0) {
      return '${cbm.toStringAsFixed(4)} m³';
    }
    return 'N/A';
  }

  factory CartItem.fromJson(Map<String, dynamic> json) {
    print('📦 CartItem.fromJson received keys: ${json.keys}');
    
    // Safe extraction of values with proper type checking
    String safeString(dynamic value, String defaultValue) {
      if (value == null) return defaultValue;
      return value.toString();
    }
    
    int safeInt(dynamic value, int defaultValue) {
      if (value == null) return defaultValue;
      if (value is int) return value;
      if (value is double) return value.toInt();
      if (value is String) return int.tryParse(value) ?? defaultValue;
      return defaultValue;
    }
    
    double safeDouble(dynamic value, double defaultValue) {
      if (value == null) return defaultValue;
      if (value is double) return value;
      if (value is int) return value.toDouble();
      if (value is String) return double.tryParse(value) ?? defaultValue;
      return defaultValue;
    }
    
    Map<String, dynamic> safeMap(dynamic value) {
      if (value == null) return {};
      if (value is Map) {
        return Map<String, dynamic>.from(value);
      }
      return {};
    }
    
    List<dynamic> safeList(dynamic value) {
      if (value == null) return [];
      if (value is List) return value;
      return [];
    }
    
    // Extract dimensions
    double? weight = json['weight'] != null ? safeDouble(json['weight'], 0) : null;
    double? length = json['length'] != null ? safeDouble(json['length'], 0) : null;
    double? width = json['width'] != null ? safeDouble(json['width'], 0) : null;
    double? height = json['height'] != null ? safeDouble(json['height'], 0) : null;
    double? cbm = json['cbm'] != null ? safeDouble(json['cbm'], 0) : null;
    
    return CartItem(
      cartKey: safeString(json['cart_key'], ''),
      vendorProductId: safeInt(json['vendor_product_id'], 0),
      productId: safeInt(json['product_id'], 0),
      vendorId: safeInt(json['vendor_id'], 0),
      vendorName: safeString(json['vendor_name'], 'Vendeur inconnu'),
      productName: safeString(json['product_name'], 'Product'),
      image: safeString(json['image'], ''),
      quantity: safeInt(json['quantity'], 1),
      basePrice: safeDouble(json['base_price'], 0),
      currency: safeString(json['currency'], 'USD'),
      rateToUsd: safeDouble(json['rate_to_usd'], 1),
      variationId: json['variation_id'] != null ? safeInt(json['variation_id'], 0) : null,
      selectedVariations: safeMap(json['selected_variations']),
      variationNote: safeString(json['variation_note'], ''),
      wholesaleApplied: json['wholesale_applied'] == true,
      wholesaleTiers: safeList(json['wholesale_tiers']),
      unitAmount: safeDouble(json['unit_amount'], 0),
      weight: weight,
      length: length,
      width: width,
      height: height,
      cbm: cbm,
      totalAmount: safeDouble(json['total_amount'], 0),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'cart_key': cartKey,
      'vendor_product_id': vendorProductId,
      'product_id': productId,
      'vendor_id': vendorId,
      'vendor_name': vendorName,
      'product_name': productName,
      'image': image,
      'quantity': quantity,
      'base_price': basePrice,
      'currency': currency,
      'rate_to_usd': rateToUsd,
      if (variationId != null) 'variation_id': variationId,
      if (selectedVariations.isNotEmpty) 'selected_variations': selectedVariations,
      'variation_note': variationNote,
      'wholesale_applied': wholesaleApplied,
      'wholesale_tiers': wholesaleTiers,
      'unit_amount': unitAmount,
      if (weight != null) 'weight': weight,
      if (length != null) 'length': length,
      if (width != null) 'width': width,
      if (height != null) 'height': height,
      'cbm': cbm, // Include calculated CBM
      'total_amount': totalAmount,
    };
  }
}

class VendorCart {
  final int vendorId;
  final String vendorName;
  final String? vendorLogo;
  final List<CartItem> items;
  final double subtotal;
  final double subtotalUsd;
  final String currency;

  VendorCart({
    required this.vendorId,
    required this.vendorName,
    this.vendorLogo,
    required this.items,
    required this.subtotal,
    required this.subtotalUsd,
    required this.currency,
  });

  /// Get total weight for all items in this vendor cart
  double get totalWeight => items.fold(0, (sum, item) => sum + item.totalWeight);
  
  /// Get total CBM for all items in this vendor cart
  double get totalCbm => items.fold(0, (sum, item) => sum + item.totalCbm);
  
  /// Get total number of items (quantity sum)
  int get totalItemsCount => items.fold(0, (sum, item) => sum + item.quantity);
  
  /// Get formatted total weight
  String get formattedTotalWeight => '${totalWeight.toStringAsFixed(2)} kg';
  
  /// Get formatted total CBM
  String get formattedTotalCbm => '${totalCbm.toStringAsFixed(4)} m³';

  factory VendorCart.fromJson(Map<String, dynamic> json) {
    print('📦 VendorCart.fromJson received with keys: ${json.keys}');
    
    // Parse items - they come as a List
    List<CartItem> items = [];
    String vendorName = json['vendor_name'] ?? 'Vendeur inconnu';
    String vendorCurrency = 'USD'; // Default
    
    if (json['items'] != null && json['items'] is List) {
      final itemsList = json['items'] as List;
      print('📦 Parsing ${itemsList.length} items for vendor ${json['vendor_id']}');
      
      for (var i = 0; i < itemsList.length; i++) {
        final itemJson = itemsList[i];
        print('📦 Item $i type: ${itemJson.runtimeType}');
        
        if (itemJson is Map<String, dynamic>) {
          try {
            final cartItem = CartItem.fromJson(itemJson);
            items.add(cartItem);
            // Set vendor currency from the first item
            if (i == 0) {
              vendorCurrency = cartItem.currency;
            }
            print('📦 Successfully parsed item ${i+1}: ${cartItem.productName}');
          } catch (e) {
            print('❌ Error parsing item $i: $e');
            print('❌ Item data: $itemJson');
          }
        } else {
          print('❌ Item $i is not a Map: ${itemJson.runtimeType}');
        }
      }
    }

    // If we have items but none were parsed successfully
    if (json['items'] != null && (json['items'] as List).isNotEmpty && items.isEmpty) {
      print('⚠️ No items parsed successfully for vendor ${json['vendor_id']}');
    }

    final vendor = VendorCart(
      vendorId: json['vendor_id'] ?? 0,
      vendorName: vendorName,
      vendorLogo: json['vendor_logo'],
      items: items,
      subtotal: (json['subtotal'] ?? 0).toDouble(),
      subtotalUsd: (json['subtotal_usd'] ?? 0).toDouble(),
      currency: vendorCurrency,
    );
    
    print('📦 Created vendor: ${vendor.vendorName} with ${vendor.items.length} items');
    return vendor;
  }

  factory VendorCart.empty() {
    return VendorCart(
      vendorId: 0,
      vendorName: '',
      items: [],
      subtotal: 0,
      subtotalUsd: 0,
      currency: 'USD',
    );
  }
}