import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/home_models.dart';
import '../models/product_models.dart'; // Add this import

class VendorProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  List<Vendor> _vendors = [];
  List<Vendor> _filteredVendors = [];
  Vendor? _selectedVendor;
  
  bool _isLoading = false;
  bool _isLoadingProducts = false;
  String? _error;
  
  // Pagination
  int _currentPage = 1;
  int _lastPage = 1;
  bool _hasMorePages = false;
  
  // Filters
  String _searchQuery = '';
  int? _selectedCategoryId;
  double _maxPrice = 0;
  String _sortBy = 'latest'; // latest, price_low, price_high, popular
  
  List<VendorProduct> _products = [];
  final List<VendorProduct> _allProducts = [];

  VendorProvider(this._apiService);

  // Getters
  List<Vendor> get vendors => _searchQuery.isEmpty ? _vendors : _filteredVendors;
  List<Vendor> get allVendors => _vendors;
  Vendor? get selectedVendor => _selectedVendor;
  List<VendorProduct> get products => _products;
  bool get isLoading => _isLoading;
  bool get isLoadingProducts => _isLoadingProducts;
  String? get error => _error;
  bool get hasMorePages => _hasMorePages;
  int get currentPage => _currentPage;
  
  // Filter getters
  String get searchQuery => _searchQuery;
  int? get selectedCategoryId => _selectedCategoryId;
  double get maxPrice => _maxPrice;
  String get sortBy => _sortBy;

  // Load all vendors
  Future<void> loadVendors() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.getVendors();
      
      //logDebug('Vendors API Response: ${response.data}'); // Debug print
      
      if (response.success && response.data != null) {
        // Check the structure of the response
        dynamic responseData = response.data;
        List<dynamic> vendorsList = [];
        
        // Handle different response structures
        if (responseData is List) {
          // Direct list response
          vendorsList = responseData;
        } else if (responseData is Map<String, dynamic>) {
          // Check if data is wrapped in a 'data' field
          if (responseData.containsKey('data')) {
            if (responseData['data'] is List) {
              vendorsList = responseData['data'];
            } else if (responseData['data'] is Map<String, dynamic>) {
              // Handle paginated response
              var paginatedData = responseData['data'];
              if (paginatedData.containsKey('data')) {
                vendorsList = paginatedData['data'];
              }
            }
          } else {
            // Try to find any list in the response
            responseData.forEach((key, value) {
              if (value is List && vendorsList.isEmpty) {
                vendorsList = value;
              }
            });
          }
        }
        
        //logDebug('Vendors list length: ${vendorsList.length}');
        
        _vendors = vendorsList.map((e) {
          //logDebug('Processing vendor: $e');
          return Vendor.fromJson(e);
        }).toList();
        
        _filteredVendors = _vendors;
        //logDebug('Vendors loaded: ${_vendors.length}');
      } else {
        _error = response.message ?? 'Failed to load vendors';
      }
    } catch (e) {
      _error = e.toString();
      //logDebug('Error loading vendors: $e');
      //logDebug('Stack trace: ${StackTrace.current}');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> checkUserHasVendor() async {
    try {
      final response = await _apiService.getUserVendorStatus();
      return response.success && response.data?['has_vendor'] == true;
    } catch (e) {
      return false;
    }
  }

  Future<void> searchProducts(String query) async {
    if (query.length < 2) return;

    _isLoadingProducts = true;
    notifyListeners();

    try {
      Map<String, dynamic> params = {
        'search': query,
        'per_page': 20,
      };

      if (_selectedVendor != null) {
        final response = await _apiService.getVendorProducts(_selectedVendor!.id, params);
        // ... handle response similar to loadVendorProducts
      } else {
        final response = await _apiService.getProductsWithParams(params);
        // ... handle response similar to loadAllProducts
      }
    } catch (e) {
      logDebug('Error searching products: $e');
    }

    _isLoadingProducts = false;
    notifyListeners();
  }
  
  // Search vendors
  void searchVendors(String query) {
    _searchQuery = query.toLowerCase().trim();
    
    if (_searchQuery.isEmpty) {
      _filteredVendors = _vendors;
    } else {
      _filteredVendors = _vendors.where((vendor) =>
        vendor.storeName.toLowerCase().contains(_searchQuery)
      ).toList();
    }
    
    // Clear selected vendor when searching
    _selectedVendor = null;
    notifyListeners();
  }

  // Select a vendor
  void selectVendor(Vendor vendor) {
    _selectedVendor = vendor;
    _searchQuery = ''; // Clear search
    _filteredVendors = _vendors; // Reset filters
    _currentPage = 1; // Reset pagination
    loadVendorProducts(vendor.id); // Load this vendor's products
    notifyListeners();
  }

  // Clear selected vendor
  void clearSelectedVendor() {
    _selectedVendor = null;
    _currentPage = 1;
    loadAllProducts(); // Reload all products
    notifyListeners();
  }

  // Load all products (initial)
  // In vendor_provider.dart, modify the loadAllProducts method:

  // In vendor_provider.dart, update the loadAllProducts method:

Future<void> loadAllProducts({bool refresh = false}) async {
  if (refresh) {
    _currentPage = 1;
    _products = [];
  }

  _isLoadingProducts = true;
  notifyListeners();

  try {
    Map<String, dynamic> params = {
      'page': _currentPage,
      'per_page': 20,
      'sort_by': _sortBy == 'price_low' ? 'price_asc' : 
                (_sortBy == 'price_high' ? 'price_desc' : 'newest'),
    };

    if (_selectedCategoryId != null) {
      params['category_id'] = _selectedCategoryId;
    }

    if (_maxPrice > 0) {
      params['max_price'] = _maxPrice;
    }

    logDebug('🔵 Loading products with params: $params');
    final response = await _apiService.getProductsWithParams(params);
    
    logDebug('🔵 Response success: ${response.success}');
    logDebug('🔵 Response data type: ${response.data.runtimeType}');
    
    if (response.success && response.data != null) {
      VendorProductResponse productResponse;
      
      // Handle different response structures
      if (response.data is List) {
        logDebug('🔵 Response is a List, using fromJsonList');
        productResponse = VendorProductResponse.fromJsonList(response.data);
      } else if (response.data is Map<String, dynamic>) {
        logDebug('🔵 Response is a Map, using fromJson');
        productResponse = VendorProductResponse.fromJson(response.data);
      } else {
        logDebug('🔵 Unexpected response type');
        productResponse = VendorProductResponse(
          items: [],
          currentPage: 1,
          lastPage: 1,
          total: 0,
          perPage: 20,
        );
      }
      
      if (refresh || _currentPage == 1) {
        _products = productResponse.items;
      } else {
        _products.addAll(productResponse.items);
      }
      
      _currentPage = productResponse.currentPage + 1;
      _lastPage = productResponse.lastPage;
      _hasMorePages = _currentPage <= _lastPage;
      
      logDebug('✅ Loaded ${_products.length} products');
    } else {
      _error = response.message ?? 'Failed to load products';
      logDebug('❌ Error: $_error');
    }
  } catch (e) {
    _error = e.toString();
    logDebug('🔴 Error loading products: $e');
    logDebug('🔴 Stack trace: ${StackTrace.current}');
  }

  _isLoadingProducts = false;
  notifyListeners();
} 
  // Load products for a specific vendor
  Future<void> loadVendorProducts(int vendorId, {bool refresh = false}) async {
  if (refresh) {
    _currentPage = 1;
    _products = [];
  }

  _isLoadingProducts = true;
  notifyListeners();

  try {
    Map<String, dynamic> params = {
      'page': _currentPage,
      'per_page': 20,
      'sort_by': _sortBy == 'price_low' ? 'price_asc' : 
                (_sortBy == 'price_high' ? 'price_desc' : 'newest'),
    };

    if (_selectedCategoryId != null) {
      params['category_id'] = _selectedCategoryId;
    }

    if (_maxPrice > 0) {
      params['max_price'] = _maxPrice;
    }

    logDebug('🔵 Loading vendor products for vendor $vendorId with params: $params');
    final response = await _apiService.getVendorProducts(vendorId, params);
    
    logDebug('🔵 Response success: ${response.success}');
    logDebug('🔵 Response data type: ${response.data.runtimeType}');
    
    if (response.success && response.data != null) {
      VendorProductResponse productResponse;
      
      if (response.data is List) {
        productResponse = VendorProductResponse.fromJsonList(response.data);
      } else {
        productResponse = VendorProductResponse.fromJson(response.data);
      }
      
      if (refresh || _currentPage == 1) {
        _products = productResponse.items;
      } else {
        _products.addAll(productResponse.items);
      }
      
      _currentPage = productResponse.currentPage + 1;
      _lastPage = productResponse.lastPage;
      _hasMorePages = _currentPage <= _lastPage;
      
      logDebug('✅ Loaded ${_products.length} vendor products');
    } else {
      _error = response.message ?? 'Failed to load vendor products';
      logDebug('❌ Error: $_error');
    }
  } catch (e) {
    _error = e.toString();
    logDebug('🔴 Error loading vendor products: $e');
  }

  _isLoadingProducts = false;
  notifyListeners();
}
  // Apply filters
  void applyFilters({
    int? categoryId,
    double? maxPrice,
    String? sortBy,
  }) {
    _selectedCategoryId = categoryId;
    _maxPrice = maxPrice ?? 0;
    _sortBy = sortBy ?? 'latest';
    _currentPage = 1;
    
    if (_selectedVendor != null) {
      loadVendorProducts(_selectedVendor!.id);
    } else {
      loadAllProducts();
    }
  }

  // Clear filters
  void clearFilters() {
    _selectedCategoryId = null;
    _maxPrice = 0;
    _sortBy = 'latest';
    _currentPage = 1;
    
    if (_selectedVendor != null) {
      loadVendorProducts(_selectedVendor!.id);
    } else {
      loadAllProducts();
    }
  }

  // Load more products (pagination)
  Future<void> loadMoreProducts() async {
    if (!_hasMorePages || _isLoadingProducts) return;
    
    if (_selectedVendor != null) {
      await loadVendorProducts(_selectedVendor!.id);
    } else {
      await loadAllProducts();
    }
  }

  // Refresh
  Future<void> refresh() async {
    _currentPage = 1;
    await loadVendors();
    if (_selectedVendor != null) {
      await loadVendorProducts(_selectedVendor!.id);
    } else {
      await loadAllProducts();
    }
  }

  void reset() {
    _vendors = [];
    _filteredVendors = [];
    _selectedVendor = null;
    _currentPage = 1;
    _lastPage = 1;
    _hasMorePages = false;
    _searchQuery = '';
    notifyListeners();
  }
}