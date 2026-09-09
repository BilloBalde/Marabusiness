// lib/core/providers/product_provider.dart

import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/home_models.dart'; // For Product model (list view)
import '../../core/models/products_page_models.dart'; // Add this import
import '../models/product_detail_models.dart'; // For ProductDetail model (detail view)

class ProductProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  // For product lists (search, category, vendor)
  List<Product> _products = [];
  final List<dynamic> _allProducts = [];
  bool _isLoadingList = false;
  String? _listError;
  int _currentPage = 1;
  int _lastPage = 1;
  bool _hasMorePages = true;
  
  // For product detail
  ProductDetail? _productDetail;
  bool _isLoadingDetail = false;
  String? _detailError;
  
  // For reviews
  List<dynamic> _reviews = [];
  bool _isLoadingReviews = false;
  bool _hasMoreReviews = true;
  int _currentReviewPage = 1;

  ProductProvider(this._apiService);

  // ==================== Getters ====================
  
  // List getters
  //List<Product> get products => _products;
  List<dynamic> get products => _allProducts;
  bool get isLoadingList => _isLoadingList;
  String? get listError => _listError;
  bool get hasMorePages => _hasMorePages;
  int get currentPage => _currentPage;
  
  // Detail getters
  ProductDetail? get productDetail => _productDetail;
  bool get isLoadingDetail => _isLoadingDetail;
  String? get detailError => _detailError;
  
  // Reviews getters
  List<dynamic> get reviews => _reviews;
  bool get isLoadingReviews => _isLoadingReviews;
  bool get hasMoreReviews => _hasMoreReviews;

  // ==================== Product List Methods ====================

  // Load products with pagination
  Future<void> loadProducts({
    int page = 1,
    int perPage = 15,
    String? search,
    int? categoryId,
    int? vendorId,
    String? sortBy,
    String? sortOrder,
    bool refresh = false,
  }) async {
    if (refresh) {
      _currentPage = 1;
      _hasMorePages = true;
      _products = [];
    }

    if (!_hasMorePages && !refresh) return;

    _isLoadingList = refresh ? true : (_products.isEmpty ? true : false);
    _listError = null;
    notifyListeners();

    try {
      final response = await _apiService.getProducts(
        page: page,
        perPage: perPage,
        search: search,
        categoryId: categoryId,
        vendorId: vendorId,
        sortBy: sortBy,
        sortOrder: sortOrder,
      );
      
      if (response.success) {
        final data = response.data as Map<String, dynamic>;
        
        // Handle different response structures
        List<dynamic> productsJson = [];
        
        if (data.containsKey('data')) {
          if (data['data'] is List) {
            productsJson = data['data'];
          } else if (data['data'] is Map && data['data'].containsKey('data')) {
            productsJson = data['data']['data'] ?? [];
          }
        } else if (data.containsKey('items')) {
          productsJson = data['items'] ?? [];
        } else if (data.containsKey('products')) {
          productsJson = data['products'] ?? [];
        }
        
        final List<Product> newProducts = productsJson
            .map((json) => Product.fromJson(json))
            .toList();
        
        if (refresh || page == 1) {
          _products = newProducts;
        } else {
          _products.addAll(newProducts);
        }

        // Get pagination info
        _currentPage = data['current_page'] ?? data['page'] ?? 1;
        _lastPage = data['last_page'] ?? data['total_pages'] ?? 1;
        _hasMorePages = _currentPage < _lastPage;
        
        if (_hasMorePages) _currentPage++;
      } else {
        _listError = response.message ?? 'Failed to load products';
      }
    } catch (e) {
      _listError = e.toString();
      logDebug('🔴 Error loading products: $e');
    }

    _isLoadingList = false;
    notifyListeners();
  }

  // Load more products (pagination)
  Future<void> loadMoreProducts() async {
    if (_hasMorePages && !_isLoadingList) {
      await loadProducts(page: _currentPage);
    }
  }

  // Refresh products
  Future<void> refreshProducts() async {
    await loadProducts(refresh: true);
  }

  // Search products
  Future<void> searchProducts(String query) async {
    await loadProducts(search: query, refresh: true);
  }

  // Filter by category
  Future<void> filterByCategory(int categoryId) async {
    await loadProducts(categoryId: categoryId, refresh: true);
  }

  // Filter by vendor
  Future<void> filterByVendor(int vendorId) async {
    await loadProducts(vendorId: vendorId, refresh: true);
  }

  // Sort products
  Future<void> sortProducts(String sortBy, {String sortOrder = 'desc'}) async {
    await loadProducts(sortBy: sortBy, sortOrder: sortOrder, refresh: true);
  }

  // In product_provider.dart, add a method to load products by category/brand

Future<void> loadProductsByCategory({
  int? categoryId,
  int? brandId,
  int page = 1,
  bool refresh = false,
}) async {
  if (refresh) {
    _currentPage = 1;
    _hasMorePages = true;
    _products = [];
  }

  if (!_hasMorePages && !refresh) return;

  _isLoadingList = true;
  _listError = null;
  notifyListeners();

  try {
    // Use the products-page endpoint which already has the right format
    final response = await _apiService.getProductsPage(
      page: page,
      perPage: 20,
      categories: categoryId != null ? [categoryId] : null,
      brands: brandId != null ? [brandId] : null,
    );
    
    if (response.success && response.data != null) {
      final data = response.data as Map<String, dynamic>;
      
      // The response should have data.products
      List<dynamic> productsJson = [];
      if (data.containsKey('data') && data['data'] is Map) {
        productsJson = data['data']['products'] ?? [];
      }
      
      // Convert to ProductsPageProduct (not Product)
      final List<ProductsPageProduct> newProducts = productsJson
          .map((json) => ProductsPageProduct.fromJson(json as Map<String, dynamic>))
          .toList();
      
      if (refresh || page == 1) {
        _products = []; // Clear but we need to store as ProductsPageProduct
        // You might need to create a separate list for ProductsPageProduct
        // or use a common interface
      } else {
        // Add to list
      }

      // Update pagination
      _hasMorePages = _currentPage < (data['data']['pagination']['last_page'] ?? 1);
      if (_hasMorePages) _currentPage++;
    }
  } catch (e) {
    _listError = e.toString();
  }

  _isLoadingList = false;
  notifyListeners();
}
  // ==================== Product Detail Methods ====================

  // Load single product details
  // In product_provider.dart, update the loadProductDetail method:
  Future<void> loadProductDetail(String slug, int vendorProductId) async {
    _isLoadingDetail = true;
    _detailError = null;
    _productDetail = null;
    notifyListeners();

    try {
      final response = await _apiService.getProduct(slug, vendorProductId);
      
      // DEBUG: Print the raw response
      logDebug('🔵 RAW API RESPONSE TYPE: ${response.data.runtimeType}');
      logDebug('🔵 RAW API RESPONSE: ${response.data}');
      
      if (response.success && response.data != null) {
        // Check if response.data is a List or Map
        if (response.data is List) {
          logDebug('🔵 Response is a LIST with ${response.data.length} items');
          // Handle list response - maybe take first item?
          if ((response.data as List).isNotEmpty) {
            final data = (response.data as List).first as Map<String, dynamic>;
            _productDetail = ProductDetail.fromJson(data);
          } else {
            _detailError = 'Empty product list returned';
          }
        } else if (response.data is Map) {
          logDebug('🔵 Response is a MAP');
          final data = response.data as Map<String, dynamic>;
          _productDetail = ProductDetail.fromJson(data);
        } else {
          _detailError = 'Unexpected response type: ${response.data.runtimeType}';
        }
        
        if (_productDetail != null) {
          logDebug('✅ Product detail loaded: ${_productDetail?.name}');
          logDebug('✅ Currency: ${_productDetail?.currency}');
          logDebug('✅ Price: ${_productDetail?.displayPrice}');
        }
      } else {
        _detailError = response.message ?? 'Failed to load product';
      }
    } catch (e, stackTrace) {
      _detailError = e.toString();
      logDebug('🔴 Error loading product detail: $e');
      logDebug('🔴 Stack trace: $stackTrace');
    }

    _isLoadingDetail = false;
    notifyListeners();
  }
  
  // Load more reviews
  Future<void> loadMoreReviews(int vendorProductId) async {
    if (!_hasMoreReviews || _isLoadingReviews || _productDetail == null) return;

    _isLoadingReviews = true;
    notifyListeners();

    try {
      // You'll need to add this endpoint to your ApiService
      final response = await _apiService.getProductReviews(
        vendorProductId, 
        page: _currentReviewPage
      );
      
      if (response.success && response.data != null) {
        final data = response.data as Map<String, dynamic>;
        final newReviews = data['data'] ?? [];
        
        _reviews.addAll(newReviews);
        _hasMoreReviews = _currentReviewPage < (data['last_page'] ?? 1);
        if (_hasMoreReviews) _currentReviewPage++;
      }
    } catch (e) {
      logDebug('🔴 Error loading more reviews: $e');
    }

    _isLoadingReviews = false;
    notifyListeners();
  }

  // ==================== Review Methods ====================

  // Submit a review
  Future<bool> submitReview({
    required int vendorProductId,
    required int rating,
    required String comment,
  }) async {
    _isLoadingDetail = true;
    notifyListeners();

    try {
      final response = await _apiService.submitProductReview(
        vendorProductId,
        rating,
        comment,
      );
      
      if (response.success) {
        // Refresh product detail to get updated reviews
        if (_productDetail != null) {
          await loadProductDetail(_productDetail!.slug, vendorProductId);
        }
        return true;
      } else {
        _detailError = response.message ?? 'Failed to submit review';
        return false;
      }
    } catch (e) {
      _detailError = e.toString();
      return false;
    } finally {
      _isLoadingDetail = false;
      notifyListeners();
    }
  }

  // Update a review
  Future<bool> updateReview({
    required int reviewId,
    required int rating,
    required String comment,
    required int vendorProductId,
  }) async {
    _isLoadingDetail = true;
    notifyListeners();

    try {
      final response = await _apiService.updateProductReview(
        reviewId,
        rating,
        comment,
      );
      
      if (response.success) {
        // Refresh product detail to get updated reviews
        if (_productDetail != null) {
          await loadProductDetail(_productDetail!.slug, vendorProductId);
        }
        return true;
      } else {
        _detailError = response.message ?? 'Failed to update review';
        return false;
      }
    } catch (e) {
      _detailError = e.toString();
      return false;
    } finally {
      _isLoadingDetail = false;
      notifyListeners();
    }
  }

  // Delete a review
  Future<bool> deleteReview({
    required int reviewId,
    required int vendorProductId,
  }) async {
    _isLoadingDetail = true;
    notifyListeners();

    try {
      final response = await _apiService.deleteProductReview(reviewId);
      
      if (response.success) {
        // Refresh product detail to get updated reviews
        if (_productDetail != null) {
          await loadProductDetail(_productDetail!.slug, vendorProductId);
        }
        return true;
      } else {
        _detailError = response.message ?? 'Failed to delete review';
        return false;
      }
    } catch (e) {
      _detailError = e.toString();
      return false;
    } finally {
      _isLoadingDetail = false;
      notifyListeners();
    }
  }

  // ==================== Variation Methods ====================

  // Select a variation (for UI state management)
  void selectVariation(int variationId) {
    if (_productDetail == null) return;
    
    // This is just for UI state - you might want to store the selected variation
    // in a separate variable if needed
    notifyListeners();
  }

  // Get wholesale price for quantity
  double? getWholesalePriceForQuantity(int quantity, {int? variationId}) {
    if (_productDetail == null) return null;
    
    if (variationId != null && _productDetail!.wholesaleTiersByVariation.containsKey(variationId)) {
      final tiers = _productDetail!.wholesaleTiersByVariation[variationId]!;
      for (var tier in tiers) {
        if (quantity >= tier.minQty && (tier.maxQty == null || quantity <= tier.maxQty!)) {
          return tier.price;
        }
      }
    } else {
      for (var tier in _productDetail!.wholesaleTiers) {
        if (quantity >= tier.minQty && (tier.maxQty == null || quantity <= tier.maxQty!)) {
          return tier.price;
        }
      }
    }
    
    return null;
  }

  // Get display price for quantity
  double getPriceForQuantity(int quantity, {int? variationId}) {
    final wholesalePrice = getWholesalePriceForQuantity(quantity, variationId: variationId);
    if (wholesalePrice != null) return wholesalePrice;
    
    if (_productDetail == null) return 0;
    
    if (variationId != null) {
      final variation = _productDetail!.variations.firstWhere(
        (v) => v.id == variationId,
        orElse: () => _productDetail!.variations.first,
      );
      return variation.displayPrice;
    }
    
    return _productDetail!.displayPrice;
  }

  // ==================== Utility Methods ====================

  // Clear current product detail
  void clearProductDetail() {
    _productDetail = null;
    _reviews = [];
    _detailError = null;
    notifyListeners();
  }

  // Reset provider
  void reset() {
    _products = [];
    _productDetail = null;
    _isLoadingList = false;
    _isLoadingDetail = false;
    _listError = null;
    _detailError = null;
    _currentPage = 1;
    _lastPage = 1;
    _hasMorePages = true;
    _reviews = [];
    _hasMoreReviews = true;
    _currentReviewPage = 1;
    notifyListeners();
  }

  // Get variation by ID
  Variation? getVariationById(int variationId) {
    if (_productDetail == null) return null;
    try {
      return _productDetail!.variations.firstWhere((v) => v.id == variationId);
    } catch (e) {
      return null;
    }
  }

  // Check if product is in stock
  bool isInStock({int? variationId}) {
    if (_productDetail == null) return false;
    
    if (variationId != null) {
      final variation = getVariationById(variationId);
      return variation?.stock != null && variation!.stock > 0;
    }
    
    return _productDetail!.stock > 0;
  }
}