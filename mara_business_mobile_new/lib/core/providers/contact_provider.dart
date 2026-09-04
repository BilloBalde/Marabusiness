// lib/core/providers/contact_provider.dart

import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../constants/api_endpoints.dart';

class ContactProvider extends ChangeNotifier {
  final ApiService _apiService;
  
  bool _isLoading = false;
  String? _error;

  ContactProvider(this._apiService);

  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<bool> submitContact({
    required String name,
    required String email,
    required String phone,
    required String subject,
    required String message,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final Map<String, dynamic> data = {
        'name': name,
        'email': email,
        'phone': phone,
        'subject': subject,
        'message': message,
      };

      print('🔵 Submitting contact form to: ${ApiEndpoints.contact}');
      print('🔵 Data: $data');
      
      final response = await _apiService.post(
        ApiEndpoints.contact, 
        data: data,
      );

      print('🔵 Response success: ${response.success}');
      print('🔵 Response message: ${response.message}');

      if (response.success) {
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response.message ?? 'Échec de l\'envoi du message';
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      print('🔴 Error submitting contact: $e');
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}