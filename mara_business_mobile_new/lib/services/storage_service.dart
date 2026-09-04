import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/constants/app_constants.dart';

class StorageService {
  static final StorageService _instance = StorageService._internal();
  factory StorageService() => _instance;
  StorageService._internal();

  late SharedPreferences _prefs;
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();

  static Future<StorageService> init() async {
    _instance._prefs = await SharedPreferences.getInstance();
    return _instance;
  }

  // Shared Preferences methods
  Future<void> setString(String key, String value) async {
    await _prefs.setString(key, value);
  }

  String? getString(String key) {
    return _prefs.getString(key);
  }

  Future<void> setBool(String key, bool value) async {
    await _prefs.setBool(key, value);
  }

  bool? getBool(String key) {
    return _prefs.getBool(key);
  }

  Future<void> setInt(String key, int value) async {
    await _prefs.setInt(key, value);
  }

  int? getInt(String key) {
    return _prefs.getInt(key);
  }

  Future<void> remove(String key) async {
    await _prefs.remove(key);
  }

  Future<void> clear() async {
    await _prefs.clear();
  }

  // Secure Storage methods (for tokens, etc.)
  Future<void> setSecureString(String key, String value) async {
    await _secureStorage.write(key: key, value: value);
  }

  Future<String?> getSecureString(String key) async {
    return await _secureStorage.read(key: key);
  }

  Future<void> removeSecure(String key) async {
    await _secureStorage.delete(key: key);
  }

  Future<void> clearSecure() async {
    await _secureStorage.deleteAll();
  }

  // Auth specific methods
  Future<void> saveAuthToken(String token) async {
    await setSecureString(AppConstants.prefAuthToken, token);
  }

  Future<String?> getAuthToken() async {
    return await getSecureString(AppConstants.prefAuthToken);
  }

  Future<void> saveUser(Map<String, dynamic> user) async {
    await setString(AppConstants.prefUser, json.encode(user));
  }

  Map<String, dynamic>? getUser() {
    final userJson = getString(AppConstants.prefUser);
    if (userJson != null) {
      return json.decode(userJson);
    }
    return null;
  }

  Future<void> clearAuth() async {
    await removeSecure(AppConstants.prefAuthToken);
    await remove(AppConstants.prefUser);
  }

  // Currency methods
  Future<void> saveCurrency(String currency) async {
    await setString(AppConstants.prefCurrency, currency);
  }

  String getCurrency() {
    return getString(AppConstants.prefCurrency) ?? AppConstants.defaultCurrency;
  }

  // Language methods
  Future<void> saveLanguage(String language) async {
    await setString(AppConstants.prefLanguage, language);
  }

  String getLanguage() {
    return getString(AppConstants.prefLanguage) ?? AppConstants.defaultLanguage;
  }
}