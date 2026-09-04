import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/home_models.dart';
import '../models/brand_models.dart';

class SearchResult {
  final List<Vendor> vendors;
  final List<Category> categories;
  final List<Product> products;
  final List<Brand> brands;

  SearchResult({
    required this.vendors,
    required this.categories,
    required this.products,
    required this.brands,
  });

  bool get isEmpty => vendors.isEmpty && categories.isEmpty && products.isEmpty && brands.isEmpty;
  bool get isNotEmpty => !isEmpty;
}

class UnifiedSearchProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  SearchResult _results = SearchResult(
    vendors: [],
    categories: [],
    products: [],
    brands: [],
  );
  
  bool _isLoading = false;
  String? _error;
  String _lastQuery = '';

  UnifiedSearchProvider(this._apiService);

  SearchResult get results => _results;
  bool get isLoading => _isLoading;
  String? get error => _error;
  String get lastQuery => _lastQuery;

  Future<void> search(String query) async {
    if (query.length < 2) {
      _results = SearchResult(
        vendors: [],
        categories: [],
        products: [],
        brands: [],
      );
      _lastQuery = query;
      notifyListeners();
      return;
    }

    _isLoading = true;
    _lastQuery = query;
    notifyListeners();

    try {
      // Search vendors
      final vendorResponse = await _apiService.searchVendors(query);
      List<Vendor> vendors = [];
      if (vendorResponse.success && vendorResponse.data != null) {
        final data = vendorResponse.data;
        if (data is Map && data['data'] != null) {
          final vendorData = data['data'];
          if (vendorData is Map && vendorData['data'] != null) {
            vendors = (vendorData['data'] as List)
                .map((e) => Vendor.fromJson(e))
                .toList();
          } else if (vendorData is List) {
            vendors = vendorData.map((e) => Vendor.fromJson(e)).toList();
          }
        }
      }

      // Search products
      final productResponse = await _apiService.searchProducts(query);
      List<Product> products = [];
      if (productResponse.success && productResponse.data != null) {
        final data = productResponse.data;
        if (data is Map && data['data'] != null) {
          final productData = data['data'];
          if (productData is Map && productData['data'] != null) {
            products = (productData['data'] as List)
                .map((e) => Product.fromJson(e))
                .toList();
          } else if (productData is List) {
            products = productData.map((e) => Product.fromJson(e)).toList();
          }
        }
      }

      // Search categories (from home data for now)
      final homeResponse = await _apiService.getHomeData();
      List<Category> categories = [];
      if (homeResponse.success && homeResponse.data != null) {
        final allCategories = (homeResponse.data['categories'] as List)
            .map((e) => Category.fromJson(e))
            .where((c) => c.name.toLowerCase().contains(query.toLowerCase()))
            .toList();
        categories = allCategories.take(5).toList();
      }

      _results = SearchResult(
        vendors: vendors.take(5).toList(),
        categories: categories,
        products: products.take(5).toList(),
        brands: [], // You can add brand search later
      );
      
      _error = null;
    } catch (e) {
      _error = e.toString();
      print('Unified search error: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  void clear() {
    _results = SearchResult(
      vendors: [],
      categories: [],
      products: [],
      brands: [],
    );
    _lastQuery = '';
    _error = null;
    notifyListeners();
  }
}