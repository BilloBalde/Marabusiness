import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/home_models.dart';
import '../models/brand_models.dart';

class HomeProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  HomeData? _homeData;
  bool _isLoading = false;
  String? _error;

  List<Brand> _brands = [];

  List<Brand> get brands => _brands;

  HomeProvider(this._apiService);

  HomeData? get homeData => _homeData;
  bool get isLoading => _isLoading;
  String? get error => _error;

  List<Product> get featuredProducts => _homeData?.featuredProducts ?? [];
  List<Product> get saleProducts => _homeData?.saleProducts ?? [];
  List<Category> get categories => _homeData?.categories ?? [];
  List<Vendor> get vendors => _homeData?.vendors ?? [];
  List<Service> get services => _homeData?.services ?? [];
  Map<String, String> get banners => _homeData?.banners ?? {};
  Currency get currency => _homeData?.currency ?? Currency(code: 'USD', rate: 1);

Future<void> loadHomeData() async {
  _isLoading = true;
  _error = null;
  notifyListeners();

  try {
    final response = await _apiService.getHomeData();

    // If the API call failed
    if (!response.success || response.data == null) {
      _error = response.message ?? 'Failed to load home data';
      _isLoading = false;
      notifyListeners();
      return;
    }

    // 🔥 CRITICAL: Check the actual type of response.data
    final rawData = response.data;
    if (rawData is! Map<String, dynamic>) {
      // Build a detailed error message with type and preview
      final dataType = rawData.runtimeType;
      String preview;
      if (rawData is List) {
        preview = 'List of ${rawData.length} items, first item: ${rawData.isNotEmpty ? rawData.first : 'empty'}';
      } else if (rawData is String) {
        preview = rawData.length > 200 ? '${rawData.substring(0, 200)}...' : rawData;
      } else {
        preview = rawData.toString();
      }
      _error = 'API returned unexpected format: $dataType.\n'
               'Expected Map<String, dynamic> but got $dataType.\n'
               'Preview: $preview';
      print('❌ Invalid data type: $rawData');
      _isLoading = false;
      notifyListeners();
      return;
    }

    // Now it's safe to parse
    final data = rawData as Map<String, dynamic>;

    // Parse brands if present
    if (data['brands'] != null) {
      _brands = (data['brands'] as List)
          .map((e) => Brand.fromJson(e))
          .toList();
    }

    // Parse home data
    _homeData = HomeData.fromJson(data);
    print('✅ Home data loaded: featured=${_homeData?.featuredProducts.length}, '
          'categories=${_homeData?.categories.length}');

  } catch (e) {
    _error = e.toString();
    print('❌ Exception: $e');
  }

  _isLoading = false;
  notifyListeners();
}
  Future<void> refresh() async {
    await loadHomeData();
  }
}