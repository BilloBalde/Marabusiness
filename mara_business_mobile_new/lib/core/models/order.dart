// lib/core/models/order.dart

import 'package:flutter/material.dart';

class Order {
  final int id;
  final String orderNumber;
  final DateTime createdAt;
  final String status;
  final String paymentStatus;
  final double grandTotal;
  final double totalPaid;
  final double totalRemaining;
  final double shippingAmount;
  final String currency;
  final String paymentMethod;
  final int? vendorId;
  final String? vendorName;
  final String? trackingNumber;
  final String? carrier;
  final DateTime? estimatedDelivery;
  final List<OrderItem> items;
  final Address? shippingAddress;
  final Payment? lastPayment;
  final Shipment? latestShipment; // Add this field
  final double? rateToUsd; 
  final DateTime? cancelledAt;
  final String? cancellationReason;
  // Additional fields needed for profile
  bool? _hasReview; // Cache for review status

  double get grandTotalUsd {
    if (rateToUsd != null && rateToUsd! > 0) {
      return grandTotal * rateToUsd!;
    }
    return grandTotal; // Fallback si le taux n'est pas disponible
  }

  Order({
    required this.id,
    required this.orderNumber,
    required this.createdAt,
    required this.status,
    required this.paymentStatus,
    required this.grandTotal,
    this.rateToUsd,
    this.cancelledAt,
    this.cancellationReason,
    required this.totalPaid,
    required this.totalRemaining,
    required this.shippingAmount,
    required this.currency,
    required this.paymentMethod,
    this.vendorId,
    this.vendorName,
    this.trackingNumber,
    this.carrier,
    this.estimatedDelivery,
    required this.items,
    this.shippingAddress,
    this.lastPayment,
    this.latestShipment, // Add this
  });

  // Helper to check if any item has a review
  bool get hasReview {
    if (_hasReview != null) return _hasReview!;
    _hasReview = items.any((item) => item.hasReview);
    return _hasReview!;
  }

  // Helper to parse boolean values that might come as ints
  static bool _parseBoolean(dynamic value) {
    if (value == null) return false;
    if (value is bool) return value;
    if (value is int) return value == 1;
    if (value is String) {
      return value.toLowerCase() == 'true' || value == '1';
    }
    return false;
  }

  factory Order.fromJson(Map<String, dynamic> json) {
    // Parse items first to check for reviews
    final items = (json['items'] as List? ?? [])
        .map((item) => OrderItem.fromJson(item))
        .toList();

    return Order(
      id: json['id'] ?? 0,
      orderNumber: json['order_number'] ?? '',
      createdAt: DateTime.parse(json['created_at'] ?? DateTime.now().toIso8601String()),
      status: json['status'] ?? 'pending',
      paymentStatus: json['payment_status'] ?? 'pending',
      grandTotal: (json['grand_total'] ?? 0).toDouble(),
      rateToUsd: (json['rate_to_usd'] as num?)?.toDouble(),
      totalPaid: (json['total_paid'] ?? 0).toDouble(),
      totalRemaining: (json['total_remaining'] ?? 0).toDouble(),
      shippingAmount: (json['shipping_amount'] ?? 0).toDouble(),
      paymentMethod: json['payment_method'] ?? '',
      currency: json['currency'] ?? 'USD',
      vendorId: json['vendor_id'],
      vendorName: json['vendor_name'] ?? json['vendor']?['store_name'],
      trackingNumber: json['tracking_number'],
      cancelledAt: json['cancelled_at'] != null 
          ? DateTime.parse(json['cancelled_at']) 
          : null,
      cancellationReason: json['cancellation_reason'],
      carrier: json['carrier'] ?? json['shipping_carrier'],
      estimatedDelivery: json['estimated_delivery'] != null 
          ? DateTime.parse(json['estimated_delivery']) 
          : null,
      items: items,
      shippingAddress: json['shipping_address'] != null
          ? Address.fromJson(json['shipping_address'])
          : (json['address'] != null ? Address.fromJson(json['address']) : null),
      lastPayment: json['last_payment'] != null
          ? Payment.fromJson(json['last_payment'])
          : (json['paiements'] != null && (json['paiements'] as List).isNotEmpty
              ? Payment.fromJson((json['paiements'] as List).first)
              : null),
      latestShipment: json['latest_shipment'] != null
          ? Shipment.fromJson(json['latest_shipment'])
          : null,
    );
  }

  // Helper getters
  bool get isPaid => paymentStatus == 'paid';
  bool get isPartialPaid => paymentStatus == 'partial';
  bool get isPending => paymentStatus == 'pending';
  bool get isCancelled => status == 'cancelled';
  bool get isCompleted => status == 'completed';
  bool get isProcessing => status == 'processing';
  bool get isShipped => trackingNumber != null && trackingNumber!.isNotEmpty;
  bool get isCancellable => status == 'new' || status == 'pending';
  
  String get statusLabel {
    switch (status) {
      case 'new':
        return 'Nouvelle';
      case 'pending':
        return 'En attente';
      case 'processing':
        return 'En traitement';
      case 'completed':
        return 'Terminée';
      case 'cancelled':
        return 'Annulée';
      default:
        return status;
    }
  }

  Color get statusColor {
    switch (status) {
      case 'new':
      case 'pending':
        return Colors.amber;
      case 'processing':
        return Colors.blue;
      case 'completed':
        return Colors.green;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String get paymentStatusLabel {
    switch (paymentStatus) {
      case 'paid':
        return 'Payée';
      case 'partial':
        return 'Partielle';
      case 'pending':
        return 'En attente';
      case 'refunded':
        return 'Remboursée';
      default:
        return paymentStatus;
    }
  }

  Color get paymentStatusColor {
    switch (paymentStatus) {
      case 'paid':
        return Colors.green;
      case 'partial':
        return Colors.amber;
      case 'pending':
        return Colors.grey;
      case 'refunded':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }
}

class OrderItem {
  final int id;
  final int productId;
  final String productName;
  final String? productImage;
  final double unitAmount;
  final int quantity;
  final double totalAmount;
  final Map<String, dynamic>? variation;
  bool? _hasReview; // Cache for review status

  OrderItem({
    required this.id,
    required this.productId,
    required this.productName,
    this.productImage,
    required this.unitAmount,
    required this.quantity,
    required this.totalAmount,
    this.variation,
    bool? hasReview,
  }) : _hasReview = hasReview;

  bool get hasReview => _hasReview ?? false;

  set hasReview(bool value) {
    _hasReview = value;
  }

  // Helper to parse boolean values that might come as ints
  static bool _parseBoolean(dynamic value) {
    if (value == null) return false;
    if (value is bool) return value;
    if (value is int) return value == 1;
    if (value is String) {
      return value.toLowerCase() == 'true' || value == '1';
    }
    return false;
  }

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      id: json['id'] ?? 0,
      productId: json['product_id'] ?? 0,
      productName: json['product_name'] ?? json['product']?['name'] ?? 'Product',
      productImage: json['product_image'] ?? json['product']?['image'],
      unitAmount: (json['unit_amount'] ?? 0).toDouble(),
      quantity: json['quantity'] ?? 1,
      totalAmount: (json['total_amount'] ?? 0).toDouble(),
      variation: json['variation'] ?? json['variation_json'],
      hasReview: _parseBoolean(json['has_review'] ?? json['user_has_reviewed']),
    );
  }

  String get variationText {
    if (variation == null) return '';
    
    if (variation!.containsKey('note') && variation!['note'] != null) {
      return variation!['note'];
    }
    
    return variation!.entries
        .where((e) => !['id', 'product_id', 'created_at', 'updated_at'].contains(e.key))
        .map((e) => '${e.key}: ${e.value}')
        .join(', ');
  }
}

class Address {
  final int? id;
  final String firstName;
  final String lastName;
  final String phone;
  final String streetAddress;
  final String city;
  final String state;
  final String zipCode;
  final String country;
  final bool isDefault;

  Address({
    this.id,
    required this.firstName,
    required this.lastName,
    required this.phone,
    required this.streetAddress,
    required this.city,
    required this.state,
    required this.zipCode,
    required this.country,
    this.isDefault = false,
  });

  // Helper to parse boolean values that might come as ints
  static bool _parseBoolean(dynamic value) {
    if (value == null) return false;
    if (value is bool) return value;
    if (value is int) return value == 1;
    if (value is String) {
      return value.toLowerCase() == 'true' || value == '1';
    }
    return false;
  }

  factory Address.fromJson(Map<String, dynamic> json) {
    return Address(
      id: json['id'],
      firstName: json['first_name'] ?? json['firstname'] ?? '',
      lastName: json['last_name'] ?? json['lastname'] ?? '',
      phone: json['phone'] ?? '',
      streetAddress: json['street_address'] ?? json['address'] ?? '',
      city: json['city'] ?? '',
      state: json['state'] ?? '',
      zipCode: json['zip_code'] ?? json['zip'] ?? '',
      country: json['country'] ?? 'Guinea',
      isDefault: _parseBoolean(json['is_default']),
    );
  }

  String get fullName => '$firstName $lastName';
  String get fullAddress => '$streetAddress, $city, $state $zipCode, $country';
}

class Payment {
  final int id;
  final double amount;
  final String method;
  final String status;
  final DateTime? paidAt;
  final String? transactionId;
  final String? receipt;

  Payment({
    required this.id,
    required this.amount,
    required this.method,
    required this.status,
    this.paidAt,
    this.transactionId,
    this.receipt,
  });

  factory Payment.fromJson(Map<String, dynamic> json) {
    return Payment(
      id: json['id'] ?? 0,
      amount: (json['amount'] ?? 0).toDouble(),
      method: json['method'] ?? json['payment_method'] ?? 'cash',
      status: json['status'] ?? json['payment_status'] ?? 'pending',
      paidAt: json['paid_at'] != null ? DateTime.parse(json['paid_at']) : null,
      transactionId: json['transaction_id'],
      receipt: json['receipt'],
    );
  }

  String get methodLabel {
    switch (method) {
      case 'stripe':
        return 'Carte Bancaire';
      case 'om':
        return 'Orange Money';
      case 'cod':
        return 'Paiement à la livraison';
      case 'cash':
        return 'Espèces';
      default:
        return method;
    }
  }
}

// Add this new Shipment class
class Shipment {
  final String carrier;
  final String? trackingNumber;
  final String status;
  final String? currentLocation;
  final DateTime? estimatedDeliveryAt;

  Shipment({
    required this.carrier,
    this.trackingNumber,
    required this.status,
    this.currentLocation,
    this.estimatedDeliveryAt,
  });

  factory Shipment.fromJson(Map<String, dynamic> json) {
    return Shipment(
      carrier: json['carrier'] ?? '',
      trackingNumber: json['tracking_number'],
      status: json['status'] ?? 'pending',
      currentLocation: json['current_location'],
      estimatedDeliveryAt: json['estimated_delivery_at'] != null
          ? DateTime.parse(json['estimated_delivery_at'])
          : null,
    );
  }

  String get statusLabel {
    switch (status) {
      case 'delivered':
        return 'Livré';
      case 'shipped':
        return 'Expédié';
      case 'in_transit':
        return 'En transit';
      case 'pending':
        return 'En attente';
      default:
        return status;
    }
  }

  Color get statusColor {
    switch (status) {
      case 'delivered':
        return Colors.green;
      case 'shipped':
      case 'in_transit':
        return Colors.blue;
      case 'pending':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }

  String get carrierLabel {
    const carriers = {
      'dhl': 'DHL Express',
      'ups': 'UPS',
      'fedex': 'FedEx',
      'chrono': 'Chronopost',
      'local': 'Livraison locale',
      'other': 'Autre transporteur',
    };
    return carriers[carrier] ?? carrier;
  }

  String? get trackingUrl {
    const urls = {
      'dhl': 'https://www.dhl.com/global-en/home/tracking.html?tracking-id=',
      'ups': 'https://www.ups.com/track?loc=en_US&tracknum=',
      'fedex': 'https://www.fedex.com/fedextrack/?tracknumbers=',
      'chrono': 'https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT=',
    };
    
    if (trackingNumber == null || trackingNumber!.isEmpty) return null;
    final baseUrl = urls[carrier];
    return baseUrl != null ? '$baseUrl$trackingNumber' : null;
  }
}