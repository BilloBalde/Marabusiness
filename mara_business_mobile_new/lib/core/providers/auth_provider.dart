import '../../utils/app_logger.dart';
import 'dart:convert';  // Add this import for json
import 'package:provider/provider.dart';
import 'package:flutter/material.dart';
import '../../services/google_login_service.dart';
import '../../services/storage_service.dart';
import '../../services/api_service.dart';
import '../models/user.dart';
import 'order_provider.dart';      // Add this
import 'address_provider.dart';    // Add this
import 'cart_provider.dart';       // Add this
import 'vendor_provider.dart';  

class AuthProvider extends ChangeNotifier {
  final ApiService _apiService;
  final StorageService _prefs;
  
  User? _user;
  String? _token;
  bool _isLoading = false;
  String? _error;

  AuthProvider(this._apiService, this._prefs) {
    _loadStoredUser();
  }

  User? get user => _user;
  String? get token => _token;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _user != null && _token != null;

  /// The token now lives in flutter_secure_storage (Keystore / Keychain) rather
  /// than SharedPreferences, which is a plain XML file on Android and was
  /// included in device backups. getAuthToken() still reads the old location as
  /// a fallback, so anyone already signed in stays signed in and simply migrates
  /// on their next login rather than being kicked out by this change.
  Future<void> _loadStoredUser() async {
    _token = await _prefs.getAuthToken();
    final userJson = _prefs.getString('user');
    
    if (_token != null && userJson != null) {
      _user = User.fromJson(Map<String, dynamic>.from(json.decode(userJson)));
      _apiService.setToken(_token!);
      notifyListeners();
    }
  }

  Future<bool> loginWithGoogle() async {
  _isLoading = true;
  _error = null;
  notifyListeners();

  try {
    final userData = await GoogleLoginService.authenticate();
    if (userData == null) {
      _error = 'Google login canceled or failed';
      _isLoading = false;
      notifyListeners();
      return false;
    }

    // userData contains: id, name, email, phone, avatar, roles, token
    _user = User.fromJson(userData);
    _token = userData['token'];
    await _prefs.saveAuthToken(_token!);
    await _prefs.setString('user', json.encode(_user!.toJson()));
    _apiService.setToken(_token!);

    _isLoading = false;
    notifyListeners();
    return true;
  } catch (e) {
    _error = 'Google login error: $e';
    _isLoading = false;
    notifyListeners();
    return false;
  }
}
  Future<bool> login(String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.login(email, password);
      
      if (response.success) {
        final data = response.data as Map<String, dynamic>;
        _user = User.fromJson(data['user']);
        _token = data['token'];
        
        await _prefs.saveAuthToken(_token!);
        await _prefs.setString('user', json.encode(_user!.toJson()));
        
        _apiService.setToken(_token!);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Login failed';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> register(Map<String, dynamic> userData) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.register(userData);
      
      if (response.success) {
        final data = response.data as Map<String, dynamic>;
        _user = User.fromJson(data['user']);
        _token = data['token'];
        
        await _prefs.saveAuthToken(_token!);
        await _prefs.setString('user', json.encode(_user!.toJson()));
        
        _apiService.setToken(_token!);
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Registration failed';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // Add this method to AuthProvider

  Future<bool> updateProfile(Map<String, dynamic> data) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      logDebug('🔵 Updating profile with data: $data');
      final response = await _apiService.updateProfile(data);
      
      logDebug('🔵 Response success: ${response.success}');
      logDebug('🔵 Response data: ${response.data}');
      
      if (response.success) {
        // The API returns user data in the 'data' field
        final Map<String, dynamic> userData;
        if (response.data is Map && response.data.containsKey('data')) {
          userData = response.data['data'] as Map<String, dynamic>;
        } else {
          userData = response.data as Map<String, dynamic>;
        }
        
        final updatedUser = User.fromJson(userData);
        _user = updatedUser;
        
        // Update stored user data
        await _prefs.setString('user', json.encode(updatedUser.toJson()));
        
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to update profile';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      logDebug('🔴 Error updating profile: $e');
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> refreshUser() async {
    if (_user != null) {
      try {
        logDebug('🔵 Refreshing user data');
        final response = await _apiService.getUserProfile();
        logDebug('🔵 Refresh response: ${response.data}');
        
        if (response.success) {
          final Map<String, dynamic> userData;
          if (response.data is Map && response.data.containsKey('data')) {
            userData = response.data['data'] as Map<String, dynamic>;
          } else {
            userData = response.data as Map<String, dynamic>;
          }
          
          _user = User.fromJson(userData);
          await _prefs.setString('user', json.encode(_user!.toJson()));
          notifyListeners();
        }
      } catch (e) {
        logDebug('Error refreshing user: $e');
      }
    }
  }
    
  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      await _apiService.logout();
    } catch (e) {
      debugPrint('Logout error: $e');
    }

    // clearAuth() wipes the token from both the secure store and
    // SharedPreferences, plus the cached user — it replaces the two separate
    // remove() calls that used to live here.
    await _prefs.clearAuth();

    _user = null;
    _token = null;
    _apiService.clearToken();
    await _resetAllProviders();
    _isLoading = false;
    notifyListeners();
  }
  Future<void> _resetAllProviders() async {
    try {
      // Reset order provider
      if (context != null) {
        context!.read<OrderProvider>().reset();
        context!.read<AddressProvider>().clearAddresses();
        context!.read<CartProvider>().resetCart();
        context!.read<VendorProvider>().reset();
      }
    } catch (e) {
      debugPrint('Error resetting providers: $e');
    }
  }

  // Add a static context reference (we'll set this in main.dart)
  static BuildContext? context;

  // Add this method to set context from main.dart
  static void setContext(BuildContext ctx) {
    context = ctx;
  }
}