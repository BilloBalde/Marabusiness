// lib/core/providers/address_provider.dart

import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../models/order.dart';

class AddressProvider extends ChangeNotifier {
  final ApiService _apiService;
  List<Address> _addresses = [];
  bool _isLoading = false;
  bool _isProcessing = false;
  String? _error;
  int? _currentUserId;

  AddressProvider(this._apiService);

  List<Address> get addresses => _addresses;
  bool get isLoading => _isLoading;
  bool get isProcessing => _isProcessing;
  String? get error => _error;

  Future<void> loadAddresses() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      logDebug('🔵 Loading addresses...');
      final response = await _apiService.getAddresses();
      
      if (response.success) {
        final data = response.data;
        logDebug('🔵 Response data type: ${data.runtimeType}');
        
        if (data is Map && data.containsKey('data')) {
          _addresses = (data['data'] as List)
              .map((item) => Address.fromJson(item))
              .toList();
          logDebug('✅ Loaded ${_addresses.length} addresses from paginated response');
        } else if (data is List) {
          _addresses = data
              .map((item) => Address.fromJson(item))
              .toList();
          logDebug('✅ Loaded ${_addresses.length} addresses from list response');
        } else if (data is Map && data.containsKey('addresses')) {
          _addresses = (data['addresses'] as List)
              .map((item) => Address.fromJson(item))
              .toList();
          logDebug('✅ Loaded ${_addresses.length} addresses from addresses field');
        } else {
          logDebug('⚠️ Unexpected response format: $data');
          _addresses = [];
        }
      } else {
        _error = response.message ?? 'Failed to load addresses';
        logDebug('❌ Error: $_error');
      }
    } catch (e) {
      _error = e.toString();
      logDebug('❌ Exception: $_error');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> createAddress(Map<String, dynamic> addressData) async {
    _isProcessing = true;
    _error = null;
    notifyListeners();

    try {
      logDebug('🔵 Creating address with data: $addressData');
      final response = await _apiService.createAddress(addressData);
      
      if (response.success) {
        logDebug('✅ Address created successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to create address';
        logDebug('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      logDebug('❌ Exception: $_error');
      _isProcessing = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateAddress(int id, Map<String, dynamic> addressData) async {
    _isProcessing = true;
    _error = null;
    notifyListeners();

    try {
      logDebug('🔵 Updating address ID: $id with data: $addressData');
      final response = await _apiService.updateAddress(id, addressData);
      
      if (response.success) {
        logDebug('✅ Address updated successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to update address';
        logDebug('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      logDebug('❌ Exception: $_error');
      _isProcessing = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> deleteAddress(int id) async {
    _isProcessing = true;
    _error = null;
    notifyListeners();

    try {
      logDebug('🔵 Deleting address ID: $id');
      final response = await _apiService.deleteAddress(id);
      
      if (response.success) {
        logDebug('✅ Address deleted successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to delete address';
        logDebug('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      logDebug('❌ Exception: $_error');
      _isProcessing = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> setDefaultAddress(int id) async {
    _isProcessing = true;
    _error = null;
    notifyListeners();

    try {
      logDebug('🔵 Setting address ID: $id as default');
      final response = await _apiService.setDefaultAddress(id);
      
      if (response.success) {
        logDebug('✅ Default address set successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to set default address';
        logDebug('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      logDebug('❌ Exception: $_error');
      _isProcessing = false;
      notifyListeners();
      return false;
    }
  }

  Address? getDefaultAddress() {
    try {
      return _addresses.firstWhere((address) => address.isDefault);
    } catch (e) {
      return _addresses.isNotEmpty ? _addresses.first : null;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }

  void clearAddresses() {
    _addresses = [];
    _currentUserId = null;
    notifyListeners();
  }
}