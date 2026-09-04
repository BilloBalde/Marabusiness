// lib/core/providers/products_page_provider.dart

import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/products_page_models.dart';

class ProductsPageProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  // Data
  List<ProductsPageProduct> _products = [];
  ProductsPagePagination? _pagination;
  ProductsPageFilters? _filters;
  
  // State
  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _error;
  
  // Filters
  List<int> _selectedCategories = [];
  List<int> _selectedBrands = [];
  bool _featured = false;
  bool _onSale = false;
  double _priceRange = 0;
  String _sort = 'latest';
  String _searchQuery = '';
  
  // Pagination
  int _currentPage = 1;
  int _lastPage = 1;
  bool _hasMorePages = true;

  ProductsPageProvider(this._apiService);

  // Getters
  List<ProductsPageProduct> get products => _products;
  ProductsPagePagination? get pagination => _pagination;
  ProductsPageFilters? get filters => _filters;
  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get error => _error;
  bool get hasMorePages => _hasMorePages;
  
  // Filter getters
  List<int> get selectedCategories => _selectedCategories;
  List<int> get selectedBrands => _selectedBrands;
  bool get featured => _featured;
  bool get onSale => _onSale;
  double get priceRange => _priceRange;
  String get sort => _sort;
  String get searchQuery => _searchQuery;

  // Load products
  Future<void> loadProducts({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _products = [];
      _lastPage = 1;
    }

    if (!refresh && _currentPage > _lastPage) {
      return;
    }

    _isLoading = refresh ? true : (_products.isEmpty ? true : false);
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.getProductsPage(
        page: _currentPage,
        perPage: 12,
        categories: _selectedCategories.isEmpty ? null : _selectedCategories,
        brands: _selectedBrands.isEmpty ? null : _selectedBrands,
        featured: _featured,
        onSale: _onSale,
        priceRange: _priceRange > 0 ? _priceRange : null,
        sort: _sort,
        search: _searchQuery.isEmpty ? null : _searchQuery,
      );

      if (response.success && response.data != null) {
        final productsResponse = ProductsPageResponse.fromJson(response.data);
        
        // Update last page from response
        _lastPage = productsResponse.data.pagination.lastPage;

        if (_currentPage == 1) {
          _products = productsResponse.data.products; // Access through data
        } else {
          // Check for duplicates before adding
          final newProducts = productsResponse.data.products;
          final existingIds = _products.map((p) => p.id).toSet();
          final uniqueNewProducts = newProducts.where((p) => !existingIds.contains(p.id)).toList();
          
          if (uniqueNewProducts.isNotEmpty) {
            _products.addAll(uniqueNewProducts);
          }
        }
        
        _pagination = productsResponse.data.pagination;
        _filters = productsResponse.data.filters;
        
        // Update hasMorePages based on last page
        _hasMorePages = _currentPage < _lastPage;
        
        // Only increment page if we actually got products and there are more pages
        if (productsResponse.data.products.isNotEmpty && _currentPage < _lastPage) {
          _currentPage++;
        }
        
        print('✅ Loaded ${productsResponse.data.products.length} products, page $_currentPage of $_lastPage');
        print('✅ Total products: ${_products.length}');
      } else {
        _error = response.message ?? 'Failed to load products';
      }
    } catch (e) {
      _error = e.toString();
      print('🔴 Error loading products: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  // Load more products (pagination)
  Future<void> loadMoreProducts() async {
    if (!_hasMorePages || _isLoadingMore || _isLoading) {
      print('🔵 Cannot load more: hasMorePages=$_hasMorePages, isLoadingMore=$_isLoadingMore, isLoading=$_isLoading');
      return;
    }
    
    _isLoadingMore = true;
    notifyListeners();
    
    await loadProducts();
    
    _isLoadingMore = false;
    notifyListeners();
  }

  // Apply filters
  void applyFilters({
    List<int>? categories,
    List<int>? brands,
    bool? featured,
    bool? onSale,
    double? priceRange,
    String? sort,
  }) {
    _selectedCategories = categories ?? _selectedCategories;
    _selectedBrands = brands ?? _selectedBrands;
    _featured = featured ?? _featured;
    _onSale = onSale ?? _onSale;
    _priceRange = priceRange ?? _priceRange;
    _sort = sort ?? _sort;
    
    // Reset pagination
    _currentPage = 1;
    _lastPage = 1;
    _products = [];
    
    loadProducts();
  }

  // Search products
  void search(String query) {
    _searchQuery = query;
    _currentPage = 1;
    _products = [];
    loadProducts();
  }

  // Clear all filters
  void clearFilters() {
    _selectedCategories = [];
    _selectedBrands = [];
    _featured = false;
    _onSale = false;
    _priceRange = 0;
    _sort = 'latest';
    _searchQuery = '';
    
    _currentPage = 1;
    _lastPage = 1;
    _products = [];
    loadProducts();
  }

  // Toggle category filter
  void toggleCategory(int categoryId) {
    if (_selectedCategories.contains(categoryId)) {
      _selectedCategories.remove(categoryId);
    } else {
      _selectedCategories.add(categoryId);
    }
    
    _currentPage = 1;
    _lastPage = 1;
    _products = [];
    loadProducts();
  }

  // Toggle brand filter
  void toggleBrand(int brandId) {
    if (_selectedBrands.contains(brandId)) {
      _selectedBrands.remove(brandId);
    } else {
      _selectedBrands.add(brandId);
    }
    
    _currentPage = 1;
    _lastPage = 1;
    _products = [];
    loadProducts();
  }

  void forceReset() {
    print('🔄 Force resetting ProductsPageProvider');
    _products = [];
    _selectedCategories = [];
    _selectedBrands = [];
    _featured = false;
    _onSale = false;
    _priceRange = 0;
    _sort = 'latest';
    _searchQuery = '';
    _currentPage = 1;
    _lastPage = 1;
    _hasMorePages = true;
    _error = null;
    _isLoading = false;
    _isLoadingMore = false;
    notifyListeners();
  }
/* 
  void resetForHome() {
  print('🔄 Resetting ProductsPageProvider for home navigation');
  _selectedCategories = [];
  _selectedBrands = [];
  _featured = false;
  _onSale = false;
  _priceRange = 0;
  _sort = 'latest';
  _searchQuery = '';
  _currentPage = 1;
  _lastPage = 1;
  _hasMorePages = true;
  _products = [];
  _error = null;
  notifyListeners();
} */

  // Refresh
  Future<void> refresh() async {
    _currentPage = 1;
    _lastPage = 1;
    await loadProducts(refresh: true);
  }
}