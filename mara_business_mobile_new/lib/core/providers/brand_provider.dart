import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/brand_models.dart';

class BrandProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  List<Brand> _brands = [];
  List<Brand> _filteredBrands = [];
  bool _isLoading = false;
  String? _error;

  BrandProvider(this._apiService);

  List<Brand> get brands => _filteredBrands;
  List<Brand> get allBrands => _brands;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadBrands() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.getBrands();
      
      //logDebug('Brands API Response: ${response.data}'); // Add this line
      
      if (response.success && response.data != null) {
        final List<dynamic> data = response.data['data'] ?? response.data;
        //logDebug('Brands data type: ${data.runtimeType}'); // Add this
        //logDebug('First brand: ${data.isNotEmpty ? data.first : 'empty'}'); // Add this
        
        _brands = data.map((e) {
          //logDebug('Processing brand: $e'); // Add this
          return Brand.fromJson(e);
        }).toList();
        
        _filteredBrands = _brands;
        //logDebug('Brands loaded: ${_brands.length}');
      } else {
        _error = response.message ?? 'Failed to load brands';
      }
    } catch (e) {
      _error = e.toString();
      //logDebug('Error loading brands: $e');
      //logDebug('Stack trace: ${StackTrace.current}'); // Add this
    }

    _isLoading = false;
    notifyListeners();
  }

  void search(String query) {
    if (query.isEmpty) {
      _filteredBrands = _brands;
    } else {
      _filteredBrands = _brands.where((brand) =>
        brand.name.toLowerCase().contains(query.toLowerCase()) ||
        brand.getTranslatedName('fr').toLowerCase().contains(query.toLowerCase()) ||
        brand.getTranslatedName('en').toLowerCase().contains(query.toLowerCase())
      ).toList();
    }
    notifyListeners();
  }

  void clearSearch() {
    _filteredBrands = _brands;
    notifyListeners();
  }

  Future<void> refresh() async {
    await loadBrands();
  }
}