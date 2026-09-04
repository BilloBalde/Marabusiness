import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/home_models.dart';

class SearchProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  List<Product> _results = [];
  bool _isLoading = false;
  String? _error;
  String _lastQuery = '';

  SearchProvider(this._apiService);

  List<Product> get results => _results;
  bool get isLoading => _isLoading;
  String? get error => _error;
  String get lastQuery => _lastQuery;

  Future<void> search(String query) async {
    if (query.length < 2) {
      _results = [];
      _lastQuery = query;
      notifyListeners();
      return;
    }

    _isLoading = true;
    _lastQuery = query;
    notifyListeners();

    try {
      final response = await _apiService.searchProducts(query);
      
      if (response.success && response.data != null) {
        final responseData = response.data;
        List<dynamic> productsList = [];
        
        if (responseData is Map<String, dynamic>) {
          if (responseData.containsKey('data')) {
            final data = responseData['data'];
            if (data is List) {
              productsList = data;
            } else if (data is Map && data.containsKey('data')) {
              productsList = data['data'];
            }
          }
        } else if (responseData is List) {
          productsList = responseData;
        }
        
        _results = productsList.map((e) => Product.fromJson(e)).toList();
        _error = null;
      } else {
        _error = response.message ?? 'Search failed';
      }
    } catch (e) {
      _error = e.toString();
      print('Search error: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  void clear() {
    _results = [];
    _lastQuery = '';
    _error = null;
    notifyListeners();
  }
}