import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/storage_service.dart';

class CurrencyProvider extends ChangeNotifier {
  final ApiService _apiService;
  final StorageService _storageService;
  
  String _selectedCurrency = 'USD';
   String _currencyCode = 'USD';
  
  String get currencyCode => _currencyCode;
  
  void setCurrency(String code) {
    _currencyCode = code;
    notifyListeners();
  }
  final double _rate = 1.0;
  
  CurrencyProvider(this._apiService, this._storageService) {
    _loadSavedCurrency();
  }
  
  String get selectedCurrency => _selectedCurrency;
  double get rate => _rate;
  
  String get symbol {
    switch (_selectedCurrency) {
      case 'USD':
        return '\$';
      case 'EUR':
        return '€';
      case 'GBP':
        return '£';
      case 'GNF':
        return 'FG';
      default:
        return '\$';
    }
  }
  
  Future<void> _loadSavedCurrency() async {
    _selectedCurrency = _storageService.getCurrency();
    _apiService.setCurrency(_selectedCurrency);
    notifyListeners();
  }
  
  Future<void> changeCurrency(String currencyCode) async {
    _selectedCurrency = currencyCode;
    await _storageService.saveCurrency(currencyCode);
    _apiService.setCurrency(currencyCode);
    notifyListeners();
  }
}