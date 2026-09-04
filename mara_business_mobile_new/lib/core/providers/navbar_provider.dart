import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/navbar_models.dart';
import '../models/home_models.dart';

class NavbarProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  NavbarData? _navbarData;
  bool _isLoading = false;
  String? _error;

  NavbarProvider(this._apiService);

  // Basic getters
  NavbarData? get navbarData => _navbarData;
  bool get isLoading => _isLoading;
  String? get error => _error;
  
  // User related getters
  User? get user => _navbarData?.user;
  int? get managerId => _navbarData?.managerId;
  
  // Count getters
  int get cartCount => _navbarData?.cartCount ?? 0;
  int get wishlistCount => _navbarData?.wishlistCount ?? 0;
  
  // Currency related getters
  String get currentCurrency => _navbarData?.currentCurrency ?? 'USD';
  List<Currency> get currencies => _navbarData?.currencies ?? [];
  
  // Language related getters - THIS IS WHAT YOU NEED
  String get currentLanguage => _navbarData?.currentLanguage ?? 'en';
  List<Language> get languages => _navbarData?.languages ?? [];
  
  // Get current language object
  Language? get currentLanguageObj {
    if (_navbarData == null) return null;
    try {
      return _navbarData!.languages.firstWhere(
        (lang) => lang.code == _navbarData!.currentLanguage,
      );
    } catch (e) {
      return _navbarData!.languages.isNotEmpty ? _navbarData!.languages.first : null;
    }
  }
  
  // Category/Family related getters
  List<String> get families => _navbarData?.families ?? [];
  Map<String, List<CategoryItem>> get menuData => _navbarData?.menuData ?? {};
  
  // Social links
  Map<String, String> get socialLinks => _navbarData?.socialLinks ?? {};

  // Methods
  Future<void> loadNavbarData() async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _apiService.getNavbarData();
      
      if (response.success && response.data != null) {
        _navbarData = NavbarData.fromJson(response.data);
      } else {
        _error = response.message ?? 'Failed to load navbar data';
      }
    } catch (e) {
      _error = e.toString();
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> switchCurrency(String currencyCode) async {
    try {
      final response = await _apiService.switchCurrency(currencyCode);
      
      if (response.success) {
        if (_navbarData != null) {
          _navbarData = NavbarData(
            user: _navbarData!.user,
            managerId: _navbarData!.managerId,
            currencies: _navbarData!.currencies,
            currentCurrency: currencyCode,
            cartCount: _navbarData!.cartCount,
            wishlistCount: _navbarData!.wishlistCount,
            families: _navbarData!.families,
            menuData: _navbarData!.menuData,
            languages: _navbarData!.languages,
            currentLanguage: _navbarData!.currentLanguage,
            socialLinks: _navbarData!.socialLinks,
          );
        }
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<bool> switchLanguage(String languageCode) async {
    try {
      final response = await _apiService.switchLanguage(languageCode);
      
      if (response.success) {
        if (_navbarData != null) {
          _navbarData = NavbarData(
            user: _navbarData!.user,
            managerId: _navbarData!.managerId,
            currencies: _navbarData!.currencies,
            currentCurrency: _navbarData!.currentCurrency,
            cartCount: _navbarData!.cartCount,
            wishlistCount: _navbarData!.wishlistCount,
            families: _navbarData!.families,
            menuData: _navbarData!.menuData,
            languages: _navbarData!.languages,
            currentLanguage: languageCode,
            socialLinks: _navbarData!.socialLinks,
          );
        }
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  void updateCartCount(int count) {
    if (_navbarData != null) {
      _navbarData = NavbarData(
        user: _navbarData!.user,
        managerId: _navbarData!.managerId,
        currencies: _navbarData!.currencies,
        currentCurrency: _navbarData!.currentCurrency,
        cartCount: count,
        wishlistCount: _navbarData!.wishlistCount,
        families: _navbarData!.families,
        menuData: _navbarData!.menuData,
        languages: _navbarData!.languages,
        currentLanguage: _navbarData!.currentLanguage,
        socialLinks: _navbarData!.socialLinks,
      );
      notifyListeners();
    }
  }

  void updateWishlistCount(int count) {
    if (_navbarData != null) {
      _navbarData = NavbarData(
        user: _navbarData!.user,
        managerId: _navbarData!.managerId,
        currencies: _navbarData!.currencies,
        currentCurrency: _navbarData!.currentCurrency,
        cartCount: _navbarData!.cartCount,
        wishlistCount: count,
        families: _navbarData!.families,
        menuData: _navbarData!.menuData,
        languages: _navbarData!.languages,
        currentLanguage: _navbarData!.currentLanguage,
        socialLinks: _navbarData!.socialLinks,
      );
      notifyListeners();
    }
  }
}