// lib/core/providers/checkout_provider.dart

import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/cart.dart';
import '../models/checkout_models.dart';
import '../models/order.dart';
import 'auth_provider.dart';
import 'cart_provider.dart';

class CheckoutProvider extends ChangeNotifier {
  final ApiService _apiService;
  final AuthProvider _authProvider;
  final CartProvider _cartProvider;

  // Address
  List<Address> _savedAddresses = [];
  Address? _selectedAddress;
  ShippingAddress? _shippingAddress;
  bool _saveAddress = true;

  // Location
  double? _latitude;
  double? _longitude;
  bool _isGettingLocation = false;

  // Shipping
  bool _hasShippingCalculated = false;
  ShippingResult? _shippingResult;
  String _selectedCarrier = 'local';
  bool _isCalculatingShipping = false;
  String? _shippingError;

  // Payment
  String _paymentMethod = 'cod';

  // Order summary
  CheckoutSummary? _summary;
  bool _hasMultipleVendors = false;

  // Order placement
  bool _isPlacingOrder = false;
  String? _orderError;
  OrderResponse? _lastOrderResponse;

  // Selected IDs
  List<int> _selectedIds = [];

  List<Map<String, dynamic>> _placedOrders = [];

  List<Map<String, dynamic>> get placedOrders => _placedOrders;

  CheckoutProvider({
    required ApiService apiService,
    required AuthProvider authProvider,
    required CartProvider cartProvider,
  })  : _apiService = apiService,
        _authProvider = authProvider,
        _cartProvider = cartProvider {
    _authProvider.addListener(_onAuthChanged);
  }

  void _onAuthChanged() {
    if (_authProvider.isAuthenticated) {
      loadSavedAddresses();
    } else {
      _savedAddresses = [];
      _selectedAddress = null;
      _shippingAddress = null;
      notifyListeners();
    }
  }

  // Getters
  List<Address> get savedAddresses => _savedAddresses;
  Address? get selectedAddress => _selectedAddress;
  ShippingAddress? get shippingAddress => _shippingAddress;
  bool get saveAddress => _saveAddress;

  double? get latitude => _latitude;
  double? get longitude => _longitude;
  bool get isGettingLocation => _isGettingLocation;

  bool get hasShippingCalculated => _hasShippingCalculated;
  ShippingResult? get shippingResult => _shippingResult;
  String get selectedCarrier => _selectedCarrier;
  bool get isCalculatingShipping => _isCalculatingShipping;
  String? get shippingError => _shippingError;

  String get paymentMethod => _paymentMethod;

  CheckoutSummary? get summary => _summary;
  bool get hasMultipleVendors => _hasMultipleVendors;
  String get selectedCurrency => _summary?.vendors.first.currency ?? 'USD';

  bool get isPlacingOrder => _isPlacingOrder;
  String? get orderError => _orderError;
  OrderResponse? get lastOrderResponse => _lastOrderResponse;

  // Check if user is logged in
  bool get isAuthenticated => _authProvider.isAuthenticated;

  // Get selected items from cart
  List<int> get selectedIds => _selectedIds;

  void setSelectedIds(List<int> ids) {
  _selectedIds = ids;
  _checkMultipleVendors();
  
  // Ne générer le summary que si on a déjà des données de livraison
  if (_hasShippingCalculated && _shippingResult != null) {
    _generateSummary();
  } else {
    // Sinon, générer un summary sans livraison
    _generateSummaryWithoutShipping();
  }
  notifyListeners();
}

// Ajouter cette méthode pour générer un summary sans livraison
void _generateSummaryWithoutShipping() {
  final selectedItems = _cartProvider.items
      .where((item) => _selectedIds.contains(item.vendorProductId))
      .toList();

  if (selectedItems.isEmpty) {
    _summary = null;
    return;
  }

  final Map<int, List<CartItem>> grouped = {};
  for (var item in selectedItems) {
    grouped.putIfAbsent(item.vendorId, () => []).add(item);
  }

  final vendors = <VendorOrderSummary>[];
  double subtotalUsd = 0;

  for (var entry in grouped.entries) {
    final vendorId = entry.key;
    final items = entry.value;
    final vendorName = items.first.vendorName ?? 'Vendor $vendorId';
    final currency = items.first.currency ?? 'USD';
    final rateToUsd = items.first.rateToUsd ?? 1;

    double vendorSubtotal = 0;
    double vendorWeight = 0;
    double vendorCbm = 0;
    int vendorItemsCount = 0;
    final vendorItems = <CartItem>[];

    for (var item in items) {
      final totalAmount = item.unitAmount * item.quantity;
      vendorSubtotal += totalAmount;
      vendorWeight += item.totalWeight;
      vendorCbm += item.totalCbm;
      vendorItemsCount += item.quantity;
      vendorItems.add(item);
    }

    final vendorSubtotalUsd = vendorSubtotal * rateToUsd;
    subtotalUsd += vendorSubtotalUsd;

    vendors.add(VendorOrderSummary(
      vendorId: vendorId,
      vendorName: vendorName,
      currency: currency,
      items: vendorItems,
      subtotal: vendorSubtotal,
      subtotalUsd: vendorSubtotalUsd,
      shipping: 0,
      shippingUsd: 0,
      total: vendorSubtotal,
      totalUsd: vendorSubtotalUsd,
      rateToUsd: rateToUsd,
      zone: 'Unknown',
      weight: vendorWeight,
      cbm: vendorCbm,
      itemsCount: vendorItemsCount,
      deliveryDays: 3,
    ));
  }

  _summary = CheckoutSummary(
    vendors: vendors,
    subtotalUsd: subtotalUsd,
    totalShippingUsd: 0,
    grandTotalUsd: subtotalUsd,
    selectedCount: selectedItems.length,
  );
}

  Future<void> loadSavedAddresses() async {
    if (!isAuthenticated) {
      _savedAddresses = [];
      return;
    }

    try {
      final response = await _apiService.getAddresses();
      
      if (response.success && response.data != null) {
        final data = response.data;
        
        List<dynamic> addressesData = [];
        if (data is List) {
          addressesData = data;
        } else if (data is Map && data.containsKey('data')) {
          addressesData = data['data'] as List? ?? [];
        } else if (data is Map && data.containsKey('addresses')) {
          addressesData = data['addresses'] as List? ?? [];
        }
        
        _savedAddresses = addressesData
            .map((addr) => Address.fromJson(addr as Map<String, dynamic>))
            .toList();
            
        if (_savedAddresses.isNotEmpty && _selectedAddress == null) {
          final defaultAddress = _savedAddresses.firstWhere(
            (addr) => addr.isDefault,
            orElse: () => _savedAddresses.first,
          );
          
          _selectedAddress = defaultAddress;
          _shippingAddress = ShippingAddress.fromAddress(defaultAddress);
        }
        
        logDebug('✅ Loaded ${_savedAddresses.length} saved addresses');
      }
    } catch (e) {
      logDebug('❌ Error loading saved addresses: $e');
    }
    
    notifyListeners();
  }

  void _checkMultipleVendors() {
    final vendors = <int>{};
    for (var item in _cartProvider.items) {
      if (_selectedIds.contains(item.vendorProductId)) {
        vendors.add(item.vendorId);
      }
    }
    
    _hasMultipleVendors = vendors.length > 1;
    
    if (_hasMultipleVendors && _paymentMethod != 'cod') {
      _paymentMethod = 'cod';
    }
    
    notifyListeners();
  }

  // Address methods
  void selectAddress(Address address) {
    _selectedAddress = address;
    _shippingAddress = ShippingAddress.fromAddress(address);
    notifyListeners();
  }

  void clearSelectedAddress() {
    _selectedAddress = null;
    _shippingAddress = null;
    notifyListeners();
  }

  void updateShippingAddress(ShippingAddress address) {
    _shippingAddress = address;
    notifyListeners();
  }

  void setSaveAddress(bool value) {
    _saveAddress = value;
    notifyListeners();
  }

  // Location methods
  Future<void> getLocation() async {
    _isGettingLocation = true;
    notifyListeners();

    await Future.delayed(const Duration(seconds: 2));
    
    _latitude = 9.945587;
    _longitude = -9.696677;
    _isGettingLocation = false;
    
    if (_shippingAddress != null) {
      await calculateShipping();
    }
    
    notifyListeners();
  }

  void setLocation(double lat, double lng) {
    _latitude = lat;
    _longitude = lng;
    notifyListeners();
  }

  // Shipping methods
  Future<void> calculateShipping() async {
  if (_shippingAddress == null) {
    _shippingError = 'Veuillez d\'abord entrer l\'adresse de livraison';
    notifyListeners();
    return;
  }

  _isCalculatingShipping = true;
  _shippingError = null;
  notifyListeners();

  try {
    final selectedItems = _cartProvider.items
        .where((item) => _selectedIds.contains(item.vendorProductId))
        .toList();

    if (selectedItems.isEmpty) {
      _shippingError = 'Pas de produits sélectionnés';
      _isCalculatingShipping = false;
      notifyListeners();
      return;
    }

    final addressData = _shippingAddress!.toJson();
    if (_latitude != null && _longitude != null) {
      addressData['latitude'] = _latitude;
      addressData['longitude'] = _longitude;
    }

    final Map<String, dynamic> requestData = {
      'selected_ids': _selectedIds,
      'address': addressData,
      'shipping_carrier': _selectedCarrier,
    };

    final response = await _apiService.calculateShipping(requestData);

    if (response.success && response.data != null) {
      final data = response.data;
      
      if (data.containsKey('carriers')) {
        _shippingResult = ShippingResult.fromJson(data);
        _hasShippingCalculated = true;
        
        // 🔥 CORRECTION: Définir _selectedCarrier si ce n'est pas déjà fait
        if (_selectedCarrier.isEmpty || !_shippingResult!.carriers.containsKey(_selectedCarrier)) {
          // Prendre le premier transporteur disponible
          _selectedCarrier = _shippingResult!.carriers.keys.first;
          logDebug('✅ Initialized selected carrier to: $_selectedCarrier');
        }
        
        _generateSummary();
      } else {
        _shippingError = 'Impossible de Calculer Le Transport';
      }
    } else {
      _shippingError = response.message ?? 'Impossible de Calculer Le Transport';
    }
  } catch (e) {
    _shippingError = e.toString();
  }

  _isCalculatingShipping = false;
  notifyListeners();
}
  void selectCarrier(String carrierKey) {
  logDebug('🔄 selectCarrier called with: $carrierKey');
  _selectedCarrier = carrierKey;
  
  // 🔥 Mettre à jour le summary immédiatement
  _generateSummary();
  notifyListeners();
  
  // Recalculer les frais de livraison avec le nouveau transporteur
  if (_shippingAddress != null) {
    _calculateShippingWithSelectedCarrier();
  }
}

  Future<void> _calculateShippingWithSelectedCarrier() async {
  if (_shippingAddress == null) return;

  _isCalculatingShipping = true;
  notifyListeners();

  try {
    final addressData = _shippingAddress!.toJson();
    if (_latitude != null && _longitude != null) {
      addressData['latitude'] = _latitude;
      addressData['longitude'] = _longitude;
    }

    final Map<String, dynamic> requestData = {
      'selected_ids': _selectedIds,
      'address': addressData,
      'shipping_carrier': _selectedCarrier,
    };

    final response = await _apiService.calculateShipping(requestData);

    if (response.success && response.data != null) {
      final data = response.data;
      
      if (data.containsKey('carriers')) {
        _shippingResult = ShippingResult.fromJson(data);
        _hasShippingCalculated = true;
        
        // 🔥 Vérifier que _selectedCarrier est valide
        if (!_shippingResult!.carriers.containsKey(_selectedCarrier) && _shippingResult!.carriers.isNotEmpty) {
          _selectedCarrier = _shippingResult!.carriers.keys.first;
          logDebug('🔄 Carrier changed to: $_selectedCarrier');
        }
        
        _generateSummary();
      } else {
        _shippingError = 'Invalid shipping response';
      }
    } else {
      _shippingError = response.message ?? 'Failed to calculate shipping';
    }
  } catch (e) {
    _shippingError = e.toString();
  }

  _isCalculatingShipping = false;
  notifyListeners();
}
  // Payment methods
  void setPaymentMethod(String method) {
    if (_hasMultipleVendors && method != 'cod') {
      return;
    }
    
    _paymentMethod = method;
    notifyListeners();
  }

  // Order placement
  Future<bool> placeOrder() async {
    if (_shippingAddress == null) {
      _orderError = 'Please enter shipping address';
      notifyListeners();
      return false;
    }

    if (!_hasShippingCalculated || _shippingResult == null) {
      _orderError = 'Please calculate shipping first';
      notifyListeners();
      return false;
    }

    _isPlacingOrder = true;
    _orderError = null;
    notifyListeners();

    try {
      final addressData = _shippingAddress!.toJson();
      if (_latitude != null && _longitude != null) {
        addressData['latitude'] = _latitude;
        addressData['longitude'] = _longitude;
      }

      final Map<String, dynamic> requestData = {
        'selected_ids': _selectedIds,
        'address': addressData,
        'payment_method': _paymentMethod,
        'shipping_carrier': _selectedCarrier,
        'save_address': _saveAddress && isAuthenticated,
      };

      final response = await _apiService.placeOrder(requestData);

      if (response.success && response.data != null) {
        _lastOrderResponse = OrderResponse.fromJson(response.data);

        if (response.data['orders'] != null) {
          _placedOrders = List<Map<String, dynamic>>.from(response.data['orders']);
        }

        _isPlacingOrder = false;

        // 🔥 CRITICAL: Reload cart after successful order
        await _cartProvider.loadCart();

        notifyListeners();
        logDebug('✅ Redirect URL: ${_lastOrderResponse?.redirectUrl}');
        return true;
      } else {
        _orderError = response.message ?? 'Failed to place order';
        _isPlacingOrder = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _orderError = e.toString();
      _isPlacingOrder = false;
      notifyListeners();
      return false;
    }
  }

  // Generate summary from cart items and shipping result
// Generate summary from cart items and shipping result
void _generateSummary() {
  final selectedItems = _cartProvider.items
      .where((item) => _selectedIds.contains(item.vendorProductId))
      .toList();

  logDebug('=== _generateSummary called ===');
  logDebug('Has shipping result: ${_shippingResult != null}');
  logDebug('_selectedCarrier: $_selectedCarrier');
  if (_shippingResult != null) {
    logDebug('Available carriers: ${_shippingResult!.carriers.keys}');
    logDebug('Has selected carrier in result: ${_shippingResult!.carriers.containsKey(_selectedCarrier)}');
  }
  logDebug('Selected items count: ${selectedItems.length}');

  if (selectedItems.isEmpty) {
    _summary = null;
    notifyListeners();
    return;
  }

  // Group by vendor
  final Map<int, List<CartItem>> grouped = {};
  for (var item in selectedItems) {
    grouped.putIfAbsent(item.vendorId, () => []).add(item);
  }

  final vendors = <VendorOrderSummary>[];
  double subtotalUsd = 0;
  double totalShippingUsd = 0;

  // Get shipping breakdown from the SELECTED CARRIER using _selectedCarrier
  final shippingBreakdown = <int, double>{};
  final zoneBreakdown = <int, String>{};
  final deliveryDaysBreakdown = <int, int>{};
  final weightBreakdown = <int, double>{};
  final cbmBreakdown = <int, double>{};
  final itemsCountBreakdown = <int, int>{};
  
  // 🔥 Utiliser _selectedCarrier pour obtenir le transporteur
  if (_shippingResult != null && _selectedCarrier.isNotEmpty && _shippingResult!.carriers.containsKey(_selectedCarrier)) {
    final carrier = _shippingResult!.carriers[_selectedCarrier]!;
    logDebug('✅ Using carrier: ${carrier.name} (${carrier.key})');
    for (var vendorEntry in carrier.vendors.entries) {
      final vendorId = vendorEntry.key;
      final vendorData = vendorEntry.value;
      shippingBreakdown[vendorId] = vendorData.cost;
      zoneBreakdown[vendorId] = vendorData.zone;
      deliveryDaysBreakdown[vendorId] = vendorData.deliveryDays;
      weightBreakdown[vendorId] = vendorData.totalWeight;
      cbmBreakdown[vendorId] = vendorData.totalCbm;
      itemsCountBreakdown[vendorId] = vendorData.totalItems;
      logDebug('📦 Vendor $vendorId: shipping cost = ${vendorData.cost} USD, zone = ${vendorData.zone}');
    }
  } else if (_shippingResult != null && _shippingResult!.carriers.isNotEmpty && _selectedCarrier.isEmpty) {
    // Si _selectedCarrier est vide mais qu'il y a des transporteurs, prendre le premier
    _selectedCarrier = _shippingResult!.carriers.keys.first;
    logDebug('🔄 Auto-selected carrier from empty: $_selectedCarrier');
    _generateSummary();
    return;
  } else {
    logDebug('⚠️ No shipping data available for carrier: $_selectedCarrier');
    if (_shippingResult != null && _shippingResult!.carriers.isNotEmpty) {
      logDebug('Available carriers: ${_shippingResult!.carriers.keys}');
      // Si le transporteur sélectionné n'est pas disponible, prendre le premier
      if (!_shippingResult!.carriers.containsKey(_selectedCarrier)) {
        _selectedCarrier = _shippingResult!.carriers.keys.first;
        logDebug('🔄 Auto-selected carrier: $_selectedCarrier');
        _generateSummary();
        return;
      }
    }
  }

  for (var entry in grouped.entries) {
    final vendorId = entry.key;
    final items = entry.value;
    final vendorName = items.first.vendorName ?? 'Vendor $vendorId';
    final currency = items.first.currency ?? 'USD';
    final rateToUsd = items.first.rateToUsd ?? 1;

    double vendorSubtotal = 0;
    double vendorWeight = 0;
    double vendorCbm = 0;
    int vendorItemsCount = 0;

    final vendorItems = <CartItem>[];

    for (var item in items) {
      final totalAmount = item.unitAmount * item.quantity;
      vendorSubtotal += totalAmount;
      vendorWeight += item.totalWeight;
      vendorCbm += item.totalCbm;
      vendorItemsCount += item.quantity;
      
      vendorItems.add(item);
    }

    final vendorSubtotalUsd = vendorSubtotal * rateToUsd;
    subtotalUsd += vendorSubtotalUsd;
    
    final shippingUsd = shippingBreakdown[vendorId] ?? 0;
    totalShippingUsd += shippingUsd;
    
    final shippingLocal = shippingUsd / rateToUsd;
    final vendorTotal = vendorSubtotal + shippingLocal;
    final vendorTotalUsd = vendorSubtotalUsd + shippingUsd;

    // Get zone and delivery days from shipping result
    final zone = zoneBreakdown[vendorId] ?? 'Unknown';
    final deliveryDays = deliveryDaysBreakdown[vendorId] ?? 3;
    final totalWeight = weightBreakdown[vendorId] ?? vendorWeight;
    final totalCbm = cbmBreakdown[vendorId] ?? vendorCbm;
    final totalItemsCount = itemsCountBreakdown[vendorId] ?? vendorItemsCount;

    vendors.add(VendorOrderSummary(
      vendorId: vendorId,
      vendorName: vendorName,
      currency: currency,
      items: vendorItems,
      subtotal: vendorSubtotal,
      subtotalUsd: vendorSubtotalUsd,
      shipping: shippingLocal,
      shippingUsd: shippingUsd,
      total: vendorTotal,
      totalUsd: vendorTotalUsd,
      rateToUsd: rateToUsd,
      zone: zone,
      weight: totalWeight,
      cbm: totalCbm,
      itemsCount: totalItemsCount,
      deliveryDays: deliveryDays,
    ));
    
    logDebug('💰 Vendor $vendorId: Subtotal = $vendorSubtotal $currency, Shipping = $shippingLocal $currency, Total = $vendorTotal $currency');
  }

  _summary = CheckoutSummary(
    vendors: vendors,
    subtotalUsd: subtotalUsd,
    totalShippingUsd: totalShippingUsd,
    grandTotalUsd: subtotalUsd + totalShippingUsd,
    selectedCount: selectedItems.length,
  );
  
  logDebug('📊 Total USD: Subtotal = $subtotalUsd, Shipping = $totalShippingUsd, Grand Total = ${subtotalUsd + totalShippingUsd}');
  
  // 🔥 IMPORTANT: Notifier les listeners après avoir mis à jour le summary
  notifyListeners();
}  
  // Reset checkout state
  void reset() {
    _selectedAddress = null;
    _shippingAddress = null;
    _latitude = null;
    _longitude = null;
    _hasShippingCalculated = false;
    _shippingResult = null;
    _selectedCarrier = 'local';
    _paymentMethod = 'cod';
    _summary = null;
    _orderError = null;
    _lastOrderResponse = null;
    notifyListeners();
  }
}