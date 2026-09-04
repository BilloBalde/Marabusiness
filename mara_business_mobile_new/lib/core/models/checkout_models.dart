// lib/core/models/checkout_models.dart

import 'order.dart';
import 'cart.dart'; 

class ShippingAddress {
  final String firstName;
  final String lastName;
  final String phone;
  final String streetAddress;
  final String city;
  final String state;
  final String zipCode;
  final String country;
  final double? latitude;
  final double? longitude;

  ShippingAddress({
    required this.firstName,
    required this.lastName,
    required this.phone,
    required this.streetAddress,
    required this.city,
    required this.state,
    required this.zipCode,
    required this.country,
    this.latitude,
    this.longitude,
  });

  Map<String, dynamic> toJson() {
    return {
      'first_name': firstName,
      'last_name': lastName,
      'phone': phone,
      'street_address': streetAddress,
      'city': city,
      'state': state,
      'zip_code': zipCode,
      'country': country,
      if (latitude != null) 'latitude': latitude,
      if (longitude != null) 'longitude': longitude,
    };
  }

  factory ShippingAddress.fromAddress(Address address) {
    return ShippingAddress(
      firstName: address.firstName,
      lastName: address.lastName,
      phone: address.phone,
      streetAddress: address.streetAddress,
      city: address.city,
      state: address.state,
      zipCode: address.zipCode,
      country: address.country,
    );
  }
}

class CarrierVendor {
  final int vendorId;
  final String zone;
  final double cost;
  final double totalWeight;
  final double totalCbm;
  final int totalItems;
  final int deliveryDays;

  CarrierVendor({
    required this.vendorId,
    required this.zone,
    required this.cost,
    required this.totalWeight,
    required this.totalCbm,
    required this.totalItems,
    required this.deliveryDays,
  });

  factory CarrierVendor.fromJson(Map<String, dynamic> json) {
    return CarrierVendor(
      vendorId: json['vendor_id'] ?? 0,
      zone: json['zone'] ?? 'Unknown',
      cost: (json['cost'] ?? 0).toDouble(),
      totalWeight: (json['total_weight'] ?? 0).toDouble(),
      totalCbm: (json['total_cbm'] ?? 0).toDouble(),
      totalItems: json['total_items'] ?? 0,
      deliveryDays: json['delivery_days'] ?? 3,
    );
  }
}

class Carrier {
  final String key;
  final String name;
  final String? description;
  final double totalCostUsd;
  final Map<int, CarrierVendor> vendors;
  final bool isAvailable;

  Carrier({
    required this.key,
    required this.name,
    this.description,
    required this.totalCostUsd,
    required this.vendors,
    this.isAvailable = true,
  });

  factory Carrier.fromJson(String key, Map<String, dynamic> json) {
    Map<int, CarrierVendor> vendors = {};
    
    if (json['vendors'] != null && json['vendors'] is Map) {
      (json['vendors'] as Map).forEach((vendorId, vendorJson) {
        vendors[int.parse(vendorId.toString())] = CarrierVendor.fromJson(vendorJson);
      });
    }

    return Carrier(
      key: key,
      name: json['name'] ?? key,
      description: json['description'],
      totalCostUsd: (json['total_cost_usd'] ?? 0).toDouble(),
      vendors: vendors,
      isAvailable: json['is_available'] ?? true,
    );
  }
}

class ShippingResult {
  final Map<String, Carrier> carriers;
  final String? selectedCarrier;

  ShippingResult({
    required this.carriers,
    this.selectedCarrier,
  });

  factory ShippingResult.fromJson(Map<String, dynamic> json) {
    Map<String, Carrier> carriers = {};
    
    if (json['carriers'] != null && json['carriers'] is Map) {
      (json['carriers'] as Map).forEach((key, value) {
        carriers[key.toString()] = Carrier.fromJson(key.toString(), value);
      });
    }

    return ShippingResult(
      carriers: carriers,
      selectedCarrier: json['selected_carrier'],
    );
  }

  Carrier? get selectedCarrierObject {
    if (selectedCarrier == null || !carriers.containsKey(selectedCarrier)) {
      return null;
    }
    return carriers[selectedCarrier];
  }
}

class VendorOrderSummary {
  final int vendorId;
  final String vendorName;
  final String currency;
  final List<CartItem> items;
  final double subtotal;
  final double subtotalUsd;
  final double shipping;
  final double shippingUsd;
  final double total;
  final double totalUsd;
  final double rateToUsd;
  final String zone;
  final double weight;
  final double cbm;
  final int itemsCount;
  final int deliveryDays;

  VendorOrderSummary({
    required this.vendorId,
    required this.vendorName,
    required this.currency,
    required this.items,
    required this.subtotal,
    required this.subtotalUsd,
    required this.shipping,
    required this.shippingUsd,
    required this.total,
    required this.totalUsd,
    required this.rateToUsd,
    required this.zone,
    required this.weight,
    required this.cbm,
    required this.itemsCount,
    required this.deliveryDays,
  });

  factory VendorOrderSummary.fromJson(Map<String, dynamic> json) {
    return VendorOrderSummary(
      vendorId: json['vendor_id'] ?? 0,
      vendorName: json['vendor_name'] ?? 'Vendor',
      currency: json['currency'] ?? 'USD',
      items: (json['items'] as List? ?? [])
          .map((item) => CartItem.fromJson(item))
          .toList(),
      subtotal: (json['subtotal'] ?? 0).toDouble(),
      subtotalUsd: (json['subtotal_usd'] ?? 0).toDouble(),
      shipping: (json['shipping'] ?? 0).toDouble(),
      shippingUsd: (json['shipping_usd'] ?? 0).toDouble(),
      total: (json['total'] ?? 0).toDouble(),
      totalUsd: (json['total_usd'] ?? 0).toDouble(),
      rateToUsd: (json['rate_to_usd'] ?? 1).toDouble(),
      zone: json['zone'] ?? 'Unknown',
      weight: (json['weight'] ?? 0).toDouble(),
      cbm: (json['cbm'] ?? 0).toDouble(),
      itemsCount: json['items_count'] ?? 0,
      deliveryDays: json['delivery_days'] ?? 3,
    );
  }
}

class CheckoutSummary {
  final List<VendorOrderSummary> vendors;
  final double subtotalUsd;
  final double totalShippingUsd;
  final double grandTotalUsd;
  final int selectedCount;

  CheckoutSummary({
    required this.vendors,
    required this.subtotalUsd,
    required this.totalShippingUsd,
    required this.grandTotalUsd,
    required this.selectedCount,
  });

  factory CheckoutSummary.fromJson(Map<String, dynamic> json) {
    List<VendorOrderSummary> vendors = [];
    
    if (json['vendors'] != null && json['vendors'] is List) {
      vendors = (json['vendors'] as List)
          .map((v) => VendorOrderSummary.fromJson(v))
          .toList();
    }

    return CheckoutSummary(
      vendors: vendors,
      subtotalUsd: (json['subtotal_usd'] ?? 0).toDouble(),
      totalShippingUsd: (json['total_shipping_usd'] ?? 0).toDouble(),
      grandTotalUsd: (json['grand_total_usd'] ?? 0).toDouble(),
      selectedCount: json['selected_count'] ?? 0,
    );
  }
}

class OrderResponse {
  final bool success;
  final String message;
  final List<Map<String, dynamic>> orders;
  final String? redirectUrl;

  OrderResponse({
    required this.success,
    required this.message,
    required this.orders,
    this.redirectUrl,
  });

  factory OrderResponse.fromJson(Map<String, dynamic> json) {
    return OrderResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      orders: (json['orders'] as List? ?? []).map((o) => Map<String, dynamic>.from(o)).toList(),
      redirectUrl: json['redirect_url'],
    );
  }
}