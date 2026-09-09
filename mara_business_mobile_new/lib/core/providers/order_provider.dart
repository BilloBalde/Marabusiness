// lib/core/providers/order_provider.dart

import 'dart:async';

import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/order.dart';
import '../models/api_response.dart';

class OrderProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  List<Order> _orders = [];
  Order? _currentOrder;
  // Starts true: nothing has been loaded yet, so screens must show a loader rather
  // than the empty state on their very first frame.
  bool _isLoading = true;
  bool _isLoadingOrders = false;

  /// The request currently in flight, so a concurrent caller can await it instead of
  /// being dropped.
  Completer<void>? _ordersRequest;
  String? _error;
  int _currentPage = 1;
  bool _hasMorePages = true;
  int _lastPage = 1;
  String? _searchQuery;
  String _statusFilter = 'all';
  final int _perPage = 15;

  OrderProvider(this._apiService);

  // Getters
  List<Order> get orders => _orders;
  Order? get currentOrder => _currentOrder;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get hasMorePages => _hasMorePages;
  int get currentPage => _currentPage;
  String? get searchQuery => _searchQuery;
  String get statusFilter => _statusFilter;
  int get perPage => _perPage;

  // Stats getters
  int get totalOrders => _orders.length;
  int get pendingOrders => _orders.where((o) => o.status == 'pending' || o.status == 'new').length;
  int get processingOrders => _orders.where((o) => o.status == 'processing').length;
  int get completedOrders => _orders.where((o) => o.status == 'completed').length;
  int get cancelledOrders => _orders.where((o) => o.status == 'cancelled').length;
  
  double get totalSpent {
    return _orders.fold(0.0, (sum, order) => sum + order.grandTotalUsd);
  }

  // Get status counts from server
  Future<Map<String, int>> getStatusCounts() async {
    try {
      final response = await _apiService.getOrders(
        page: 1,
        perPage: 1,
        status: 'all',
      );
      
      if (response.success && response.data is Map) {
        final data = response.data;
        if (data.containsKey('meta')) {
          final meta = data['meta'];
          return {
            'all': meta['total'] ?? 0,
            'new': meta['counts']['new'] ?? 0,
            'processing': meta['counts']['processing'] ?? 0,
            'shipped': meta['counts']['shipped'] ?? 0,
            'delivered': meta['counts']['delivered'] ?? 0,
            'cancelled': meta['counts']['cancelled'] ?? 0,
          };
        }
      }
    } catch (e) {
      print('Error getting status counts: $e');
    }
    return {
      'all': 0,
      'new': 0,
      'processing': 0,
      'shipped': 0,
      'delivered': 0,
      'cancelled': 0,
    };
  }

  // Load first 15 orders for profile screen
  Future<void> loadLatestOrders({int limit = 15}) async {
    _isLoadingOrders = true;
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.getOrders(
        page: 1,
        perPage: limit,
        search: null,
        status: null,
      );
      
      if (response.success && response.data is Map) {
        final data = response.data;
        if (data.containsKey('data')) {
          final ordersData = data['data'];
          if (ordersData is List) {
            _orders = ordersData.map((json) => Order.fromJson(json)).toList();
          } else if (ordersData is Map && ordersData.containsKey('data')) {
            _orders = (ordersData['data'] as List)
                .map((json) => Order.fromJson(json))
                .toList();
          }
        }
        print('✅ Loaded ${_orders.length} latest orders');
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Error loading latest orders: $e');
    }

    _isLoadingOrders = false;
    _isLoading = false;
    notifyListeners();
  }

  // Load orders with pagination and filters
  /* Future<void> loadOrders({
    int page = 1,
    int? perPage,
    String? search,
    String? status,
    bool refresh = false,
  }) async {
    if (_isLoadingOrders) return;
    
    if (refresh) {
      _currentPage = 1;
      _hasMorePages = true;
      _orders = [];
      _searchQuery = search;
      _statusFilter = status ?? 'all';
    } else if (search != null) {
      _searchQuery = search;
      _currentPage = 1;
      _orders = [];
    } else if (status != null && status != _statusFilter) {
      _statusFilter = status;
      _currentPage = 1;
      _orders = [];
    }

    if (!_hasMorePages && !refresh && search == null && status == null) return;

    _isLoadingOrders = true;
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _apiService.getOrders(
        page: _currentPage,
        perPage: perPage ?? _perPage,
        search: _searchQuery,
        status: _statusFilter != 'all' ? _statusFilter : null,
      );
      
      if (response.success && response.data is Map) {
        final data = response.data;
        
        if (data.containsKey('data')) {
          final ordersData = data['data'];
          List<Order> newOrders = [];
          
          if (ordersData is List) {
            newOrders = ordersData.map((json) => Order.fromJson(json)).toList();
          } else if (ordersData is Map && ordersData.containsKey('data')) {
            newOrders = (ordersData['data'] as List)
                .map((json) => Order.fromJson(json))
                .toList();
            
            // Get pagination info
            if (ordersData.containsKey('current_page')) {
              _currentPage = ordersData['current_page'];
            }
            if (ordersData.containsKey('last_page')) {
              _lastPage = ordersData['last_page'];
              _hasMorePages = _currentPage < _lastPage;
            }
          }
          
          if (refresh || _currentPage == 1) {
            _orders = newOrders;
          } else {
            _orders.addAll(newOrders);
          }
        }
        
        print('✅ Loaded ${_orders.length} orders - Page $_currentPage of $_lastPage');
      } else {
        _error = response.message ?? 'Failed to load orders';
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Error loading orders: $e');
    }

    _isLoadingOrders = false;
    _isLoading = false;
    notifyListeners();
  }
 */
  // Load single order details
  Future<bool> loadOrderDetails(int orderId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.getOrderDetails(orderId);
      
      if (response.success) {
        if (response.data is Map<String, dynamic>) {
          final jsonResponse = response.data;
          
          if (jsonResponse.containsKey('data')) {
            _currentOrder = Order.fromJson(jsonResponse['data']);
          } else {
            _currentOrder = Order.fromJson(jsonResponse);
          }
        }
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to load order details';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Refresh orders
  Future<void> refreshOrders() async {
    await loadOrders(refresh: true);
  }

  // Search orders
  // lib/core/providers/order_provider.dart

// Search orders - FIXED
Future<void> searchOrders(String query) async {
  _searchQuery = query.isNotEmpty ? query : null;
  _currentPage = 1;
  _hasMorePages = true;
  _orders = [];
  _error = null;
  notifyListeners();
  
  // Load orders with the search query
  await loadOrders(refresh: true, search: _searchQuery);
}

// Load orders with pagination and filters - FIXED
Future<void> loadOrders({
  int page = 1,
  int? perPage,
  String? search,
  String? status,
  bool refresh = false,
}) async {
  final inFlight = _ordersRequest;
  if (inFlight != null) {
    await inFlight.future;

    // Nothing new was asked for, so the request that just finished answers this call.
    if (!refresh && search == null && status == null) return;
  }

  // Handle refresh or new search
  if (refresh) {
    _currentPage = 1;
    _hasMorePages = true;
    _orders = [];
    
    // Update search query if provided
    if (search != null) {
      _searchQuery = search;
    }
    // Update status filter if provided
    if (status != null) {
      _statusFilter = status;
    }
  } else if (search != null && search != _searchQuery) {
    // New search query
    _searchQuery = search;
    _currentPage = 1;
    _orders = [];
  } else if (status != null && status != _statusFilter) {
    // New status filter
    _statusFilter = status;
    _currentPage = 1;
    _orders = [];
  }

  if (!_hasMorePages && !refresh && search == null && status == null) return;

  final request = Completer<void>();
  _ordersRequest = request;
  _isLoadingOrders = true;
  _isLoading = true;
  notifyListeners();

  try {
    // Always use the current _searchQuery and _statusFilter for the API call
    final apiSearch = _searchQuery != null && _searchQuery!.isNotEmpty ? _searchQuery : null;
    final apiStatus = _statusFilter != 'all' ? _statusFilter : null;
    
    print('🔍 API Request - Page: $_currentPage, Search: $apiSearch, Status: $apiStatus');
    
    final response = await _apiService.getOrders(
      page: _currentPage,
      perPage: perPage ?? _perPage,
      search: apiSearch,
      status: apiStatus,
    );
    
    if (response.success && response.data is Map) {
      final data = response.data;
      
      if (data.containsKey('data')) {
        final ordersData = data['data'];
        List<Order> newOrders = [];
        
        if (ordersData is List) {
          newOrders = ordersData.map((json) => Order.fromJson(json)).toList();
        } else if (ordersData is Map && ordersData.containsKey('data')) {
          newOrders = (ordersData['data'] as List)
              .map((json) => Order.fromJson(json))
              .toList();
          
          // Get pagination info
          if (ordersData.containsKey('current_page')) {
            _currentPage = ordersData['current_page'];
          }
          if (ordersData.containsKey('last_page')) {
            _lastPage = ordersData['last_page'];
            _hasMorePages = _currentPage < _lastPage;
          }
        }
        
        if (refresh || _currentPage == 1) {
          _orders = newOrders;
        } else {
          _orders.addAll(newOrders);
        }
        
        print('✅ Loaded ${_orders.length} orders - Page $_currentPage of $_lastPage');
        if (_searchQuery != null && _searchQuery!.isNotEmpty) {
          print('🔍 Search results for "$_searchQuery": ${_orders.length} orders found');
        }
      }
    } else {
      _error = response.message ?? 'Failed to load orders';
      print('❌ Error loading orders: $_error');
    }
  } catch (e) {
    _error = e.toString();
    print('❌ Exception loading orders: $e');
  } finally {
    _ordersRequest = null;
    if (!request.isCompleted) request.complete();
    _isLoadingOrders = false;
    _isLoading = false;
    notifyListeners();
  }
}

  // Filter by status
  Future<void> filterByStatus(String status) async {
  _statusFilter = status;
  _currentPage = 1;
  _hasMorePages = true;
  _orders = [];
  _error = null;
  notifyListeners();
  
  await loadOrders(refresh: true, status: _statusFilter);
}

  // Clear search
  void clearSearch() {
    if (_searchQuery != null) {
      _searchQuery = null;
      loadOrders(refresh: true);
    }
  }

  // Load more orders (pagination)
  Future<void> loadMoreOrders() async {
    if (_hasMorePages && !_isLoading) {
      _currentPage++;
      await loadOrders();
    }
  }

  // Cancel order
  Future<bool> cancelOrder(int orderId, {String? reason}) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.cancelOrder(orderId, reason: reason);
      
      if (response.success) {
        // Update the order in the list
        final index = _orders.indexWhere((o) => o.id == orderId);
        if (index != -1 && response.data is Map) {
          final updatedOrder = Order.fromJson(response.data['data'] ?? response.data);
          _orders[index] = updatedOrder;
        }
        // Update current order if it's the same
        if (_currentOrder?.id == orderId) {
          _currentOrder = Order.fromJson(response.data['data'] ?? response.data);
        }
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to cancel order';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Track order
  Future<Map<String, dynamic>?> trackOrder(int orderId) async {
    try {
      final response = await _apiService.trackOrder(orderId);
      if (response.success) {
        return response.data['data'] ?? response.data;
      }
    } catch (e) {
      debugPrint('Error tracking order: $e');
    }
    return null;
  }

  // Download invoice
  Future<String?> downloadInvoice(int orderId) async {
    try {
      final response = await _apiService.downloadInvoice(orderId);
      if (response.success) {
        return response.data['data']?['invoice_url'] ?? response.data['invoice_url'];
      }
    } catch (e) {
      debugPrint('Error downloading invoice: $e');
    }
    return null;
  }

  // Clear current order
  void clearCurrentOrder() {
    _currentOrder = null;
    notifyListeners();
  }

  // Reset provider
  void reset() {
    _orders = [];
    _currentOrder = null;
    _isLoading = false;
    _error = null;
    _currentPage = 1;
    _hasMorePages = true;
    _lastPage = 1;
    _searchQuery = null;
    _statusFilter = 'all';
    notifyListeners();
  }
}