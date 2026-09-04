// lib/core/providers/address_provider.dart

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
      print('🔵 Loading addresses...');
      final response = await _apiService.getAddresses();
      
      if (response.success) {
        final data = response.data;
        print('🔵 Response data type: ${data.runtimeType}');
        
        if (data is Map && data.containsKey('data')) {
          _addresses = (data['data'] as List)
              .map((item) => Address.fromJson(item))
              .toList();
          print('✅ Loaded ${_addresses.length} addresses from paginated response');
        } else if (data is List) {
          _addresses = data
              .map((item) => Address.fromJson(item))
              .toList();
          print('✅ Loaded ${_addresses.length} addresses from list response');
        } else if (data is Map && data.containsKey('addresses')) {
          _addresses = (data['addresses'] as List)
              .map((item) => Address.fromJson(item))
              .toList();
          print('✅ Loaded ${_addresses.length} addresses from addresses field');
        } else {
          print('⚠️ Unexpected response format: $data');
          _addresses = [];
        }
      } else {
        _error = response.message ?? 'Failed to load addresses';
        print('❌ Error: $_error');
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Exception: $_error');
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> createAddress(Map<String, dynamic> addressData) async {
    _isProcessing = true;
    _error = null;
    notifyListeners();

    try {
      print('🔵 Creating address with data: $addressData');
      final response = await _apiService.createAddress(addressData);
      
      if (response.success) {
        print('✅ Address created successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to create address';
        print('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Exception: $_error');
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
      print('🔵 Updating address ID: $id with data: $addressData');
      final response = await _apiService.updateAddress(id, addressData);
      
      if (response.success) {
        print('✅ Address updated successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to update address';
        print('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Exception: $_error');
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
      print('🔵 Deleting address ID: $id');
      final response = await _apiService.deleteAddress(id);
      
      if (response.success) {
        print('✅ Address deleted successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to delete address';
        print('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Exception: $_error');
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
      print('🔵 Setting address ID: $id as default');
      final response = await _apiService.setDefaultAddress(id);
      
      if (response.success) {
        print('✅ Default address set successfully');
        await loadAddresses(); // Reload addresses
        _isProcessing = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Failed to set default address';
        print('❌ Error: $_error');
        _isProcessing = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      _error = e.toString();
      print('❌ Exception: $_error');
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