import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/vendor_models.dart';
// Add this for min() function

class VendorDetailProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  ExtendedVendor? _vendor;
  List<VendorProduct> _products = [];
  List<VendorReview> _reviews = [];
  
  bool _isLoading = true;
  bool _isLoadingMoreProducts = false;
  bool _isLoadingMoreReviews = false;
  String? _error;
  
  // Pagination
  int _productsCurrentPage = 1;
  int _productsLastPage = 1;
  int _reviewsCurrentPage = 1;
  int _reviewsLastPage = 1;
  
  // Follow state
  bool _isFollowing = false;
  
  // User review
  VendorReview? _userReview;
  
  // Filters
  String _sortBy = 'popular';
  int? _selectedCategoryId;
  double _minPrice = 0;
  double _maxPrice = 1000000;

  // Add a global key reference (you'll need to pass this from main)
  GlobalKey<NavigatorState>? _navigatorKey;
  
  VendorDetailProvider(this._apiService, [this._navigatorKey]);

  // Setter for navigator key
  set navigatorKey(GlobalKey<NavigatorState> key) {
    _navigatorKey = key;
  }

  // Getters
  ExtendedVendor? get vendor => _vendor;
  List<VendorProduct> get products => _products;
  List<VendorReview> get reviews => _reviews;
  bool get isLoading => _isLoading;
  bool get isLoadingMoreProducts => _isLoadingMoreProducts;
  bool get isLoadingMoreReviews => _isLoadingMoreReviews;
  String? get error => _error;
  bool get isFollowing => _isFollowing;
  VendorReview? get userReview => _userReview;
  
  bool get hasMoreProducts => _productsCurrentPage < _productsLastPage;
  bool get hasMoreReviews => _reviewsCurrentPage < _reviewsLastPage;

  // Load vendor data
  // In vendor_detail_provider.dart, update the loadVendorData method
  Future<void> loadVendorData(String slug, {
    String sortBy = 'popular',
    int? categoryId,
    double minPrice = 0,
    double maxPrice = 1000000,
    bool forceRefresh = false, // Add this parameter
  }) async {
    _isLoading = true;
    _error = null;
    
    bool shouldNotify = true;

    try {
      _sortBy = sortBy;
      _selectedCategoryId = categoryId;
      _minPrice = minPrice;
      _maxPrice = maxPrice;

      logDebug('🔵 Loading vendor data for slug: $slug');
      
      final response = await _apiService.getVendorDetail(slug, {
        'sort_by': sortBy,
        if (categoryId != null) 'category_id': categoryId,
        if (minPrice > 0) 'min_price': minPrice,
        if (maxPrice < 1000000) 'max_price': maxPrice,
      });

      logDebug('🔵 Response status: ${response.success}');
      logDebug('🔵 Response data: ${response.data}');

      if (response.success && response.data != null) {
        final data = response.data['data'] ?? response.data;
        logDebug('🔵 Data after extraction: $data');
        
        _vendor = ExtendedVendor.fromJson(data['vendor'] ?? data);
        
        // Handle products
        if (data['products'] != null) {
          logDebug('🔵 Products structure: ${data['products']}');
          
          if (data['products'] is Map && data['products']['data'] != null) {
            final productsData = data['products']['data'];
            if (productsData is List) {
              _products = productsData.map((e) {
                logDebug('🔵 Product JSON: $e');
                return VendorProduct.fromJson(e);
              }).toList();
            }
          } else if (data['products'] is List) {
            _products = (data['products'] as List).map((e) {
              logDebug('🔵 Product JSON: $e');
              return VendorProduct.fromJson(e);
            }).toList();
          }
          
          logDebug('🔵 Products loaded: ${_products.length}');
        }

        // Handle reviews
        if (data['reviews'] != null) {
          logDebug('🔵 REVIEWS DATA FOUND: ${data['reviews']}');
          
          if (data['reviews'] is Map && data['reviews']['data'] != null) {
            logDebug('🔵 REVIEWS IS PAGINATED WITH ${(data['reviews']['data'] as List).length} items');
            final reviewsData = data['reviews']['data'];
            if (reviewsData is List) {
              _reviews = reviewsData.map((e) {
                logDebug('🔵 PARSING REVIEW: $e');
                return VendorReview.fromJson(e);
              }).toList();
            }
          } else if (data['reviews'] is List) {
            logDebug('🔵 REVIEWS IS LIST WITH ${(data['reviews'] as List).length} items');
            _reviews = (data['reviews'] as List).map((e) {
              logDebug('🔵 PARSING REVIEW: $e');
              return VendorReview.fromJson(e);
            }).toList();
          }
          logDebug('✅ FINAL REVIEWS COUNT: ${_reviews.length}');
        }
        
        // CRITICAL: Save follow status
        _isFollowing = data['is_following'] ?? false;
        logDebug('🔵 FOLLOW STATUS FROM API: $_isFollowing');
        
        // Handle user review
        if (data['user_review'] != null) {
          try {
            _userReview = VendorReview.fromJson(data['user_review']);
            logDebug('✅ User review parsed: ${_userReview?.id}');
          } catch (e) {
            logDebug('❌ Error parsing user review: $e');
            _userReview = null;
          }
        } else {
          logDebug('🔵 No user review in response');
          _userReview = null;
        }
        
        // Pagination info
        if (data['products'] is Map) {
          _productsCurrentPage = data['products']['current_page'] ?? 1;
          _productsLastPage = data['products']['last_page'] ?? 1;
        }
        
        if (data['reviews'] is Map) {
          _reviewsCurrentPage = data['reviews']['current_page'] ?? 1;
          _reviewsLastPage = data['reviews']['last_page'] ?? 1;
        }

        logDebug('✅ Vendor data loaded successfully');
      } else {
        _error = response.message ?? 'Failed to load vendor data';
        logDebug('❌ Error: $_error');
      }
    } catch (e, stackTrace) {
      _error = e.toString();
      logDebug('❌ Error loading vendor data: $e');
      logDebug('❌ Stack trace: $stackTrace');
    }

    _isLoading = false;
    
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (shouldNotify) {
        notifyListeners();
      }
    });
  }
    
  // Load more products
  Future<void> loadMoreProducts() async {
    if (!hasMoreProducts || _isLoadingMoreProducts || _vendor == null) return;

    _isLoadingMoreProducts = true;
    notifyListeners();

    try {
      final response = await _apiService.getVendorProducts(
        _vendor!.id,
        {
          'page': _productsCurrentPage + 1,
          'sort_by': _sortBy,
          if (_selectedCategoryId != null) 'category_id': _selectedCategoryId,
          if (_minPrice > 0) 'min_price': _minPrice,
          if (_maxPrice < 1000000) 'max_price': _maxPrice,
        },
      );

      if (response.success && response.data != null) {
        final data = response.data;
        final newProducts = (data['data']?['data'] as List? ?? [])
            .map((e) => VendorProduct.fromJson(e))
            .toList();
        
        _products.addAll(newProducts);
        _productsCurrentPage = data['data']?['current_page'] ?? _productsCurrentPage + 1;
        _productsLastPage = data['data']?['last_page'] ?? _productsLastPage;
      }
    } catch (e) {
      logDebug('Error loading more products: $e');
    }

    _isLoadingMoreProducts = false;
    notifyListeners();
  }

  // Load more reviews
  Future<void> loadMoreReviews() async {
    if (!hasMoreReviews || _isLoadingMoreReviews || _vendor == null) return;

    _isLoadingMoreReviews = true;
    notifyListeners();

    try {
      final response = await _apiService.getVendorReviews(
        _vendor!.id,
        {'page': _reviewsCurrentPage + 1},
      );

      if (response.success && response.data != null) {
        final data = response.data;
        final newReviews = (data['data']?['data'] as List? ?? [])
            .map((e) => VendorReview.fromJson(e))
            .toList();
        
        _reviews.addAll(newReviews);
        _reviewsCurrentPage = data['data']?['current_page'] ?? _reviewsCurrentPage + 1;
        _reviewsLastPage = data['data']?['last_page'] ?? _reviewsLastPage;
      }
    } catch (e) {
      logDebug('Error loading more reviews: $e');
    }

    _isLoadingMoreReviews = false;
    notifyListeners();
  }

  // Toggle follow
  // In vendor_detail_provider.dart, update toggleFollow method
  Future<void> toggleFollow() async {
  if (_vendor == null) return;

  logDebug('🔐 TOGGLE FOLLOW DEBUG');
  logDebug('🔐 Vendor ID: ${_vendor!.id}');
  logDebug('🔐 Current follow status: $_isFollowing');

  try {
    logDebug('🔐 Making API call to toggle follow...');
    final response = await _apiService.toggleFollowVendor(_vendor!.id);
    
    logDebug('🔐 Response success: ${response.success}');
    logDebug('🔐 Response message: ${response.message}');
    logDebug('🔐 Response data: ${response.data}');
    
    if (response.success) {
      // Toggle the follow status
      _isFollowing = !_isFollowing;
      
      // Update follower count if returned
      if (response.data != null && response.data['followers_count'] != null) {
        // Update vendor with new follower count from API
        _vendor = ExtendedVendor(
          id: _vendor!.id,
          storeName: _vendor!.storeName,
          slug: _vendor!.slug,
          logo: _vendor!.logo,
          banner: _vendor!.banner,
          description: _vendor!.description,
          currency: _vendor!.currency,
          currencyRate: _vendor!.currencyRate,
          rating: _vendor!.rating,
          reviewsCount: _vendor!.reviewsCount,
          followersCount: response.data['followers_count'], // Update with new count
          productsCount: _vendor!.productsCount,
          isVerified: _vendor!.isVerified,
          isFeatured: _vendor!.isFeatured,
          createdAt: _vendor!.createdAt,
        );
      } else {
        // If no count returned, just update the count manually with null safety
        final currentCount = _vendor!.followersCount ?? 0; // Provide default value of 0 if null
        final newCount = _isFollowing 
            ? currentCount + 1 
            : (currentCount - 1).clamp(0, 999999); // Ensure it doesn't go negative
        
        _vendor = ExtendedVendor(
          id: _vendor!.id,
          storeName: _vendor!.storeName,
          slug: _vendor!.slug,
          logo: _vendor!.logo,
          banner: _vendor!.banner,
          description: _vendor!.description,
          currency: _vendor!.currency,
          currencyRate: _vendor!.currencyRate,
          rating: _vendor!.rating,
          reviewsCount: _vendor!.reviewsCount,
          followersCount: newCount,
          productsCount: _vendor!.productsCount,
          isVerified: _vendor!.isVerified,
          isFeatured: _vendor!.isFeatured,
          createdAt: _vendor!.createdAt,
        );
      }
      
      logDebug('🔐 New follow status: $_isFollowing');
      logDebug('🔐 New followers count: ${_vendor?.followersCount}');
      
      notifyListeners();
    } else if (response.message?.contains('Unauthenticated') ?? false) {
      logDebug('🔐 Server says unauthenticated - token might be invalid/expired');
    }
  } catch (e) {
    logDebug('🔐 Error toggling follow: $e');
  }
}
  // Submit review
  Future<bool> submitReview(int rating, String comment) async {
    if (_vendor == null) return false;

    try {
      final response = await _apiService.submitVendorReview(
        _vendor!.id,
        rating,
        comment,
      );

      if (response.success) {
        // Refresh reviews
        await loadVendorData(_vendor!.slug);
        return true;
      } else if (response.message?.contains('Unauthenticated') ?? false) {
        logDebug('User not authenticated for review submission');
        return false;
      }
      return false;
    } catch (e) {
      logDebug('Error submitting review: $e');
      return false;
    }
  }

  // Delete review
  Future<bool> deleteReview() async {
    if (_vendor == null || _userReview == null) return false;

    try {
      final response = await _apiService.deleteVendorReview(_vendor!.id);
      
      if (response.success) {
        _userReview = null;
        // Refresh reviews
        await loadVendorData(_vendor!.slug);
        return true;
      } else if (response.message?.contains('Unauthenticated') ?? false) {
        logDebug('User not authenticated for review deletion');
        return false;
      }
      return false;
    } catch (e) {
      logDebug('Error deleting review: $e');
      return false;
    }
  }

  // Apply filters
  Future<void> applyFilters({
    String? sortBy,
    int? categoryId,
    double? minPrice,
    double? maxPrice,
  }) async {
    if (_vendor == null) return;

    _sortBy = sortBy ?? _sortBy;
    _selectedCategoryId = categoryId;
    _minPrice = minPrice ?? 0;
    _maxPrice = maxPrice ?? 1000000;

    await loadVendorData(
      _vendor!.slug,
      sortBy: _sortBy,
      categoryId: _selectedCategoryId,
      minPrice: _minPrice,
      maxPrice: _maxPrice,
    );
  }
}