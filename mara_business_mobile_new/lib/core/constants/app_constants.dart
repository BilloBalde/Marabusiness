class AppConstants {
  static const String appName = 'MARA BUSINESS';
  
  // IMPORTANT: Change this to your local IP address
  // If using Android emulator: use 10.0.2.2
  // If using iOS simulator: use localhost or 127.0.0.1
  // If using real device: use your computer's IP address (e.g., 192.168.1.100)
  //static const String baseUrl = 'http://localhost:8000'; // For Local emulator
  static const String baseUrl = 'https://afrobridgeinnov.com';
  //static const String baseUrl = 'http://10.0.2.2:8000'; // Android emulator
  //static const String baseUrl = 'http://192.168.0.148:8000'; // Android emulator
  
  // For iOS simulator, use:
  //static const String baseUrl = 'http://127.0.0.1:8000';
  
  // For real device on same network, use your computer's IP:
  //static const String baseUrl = 'http://192.168.10.87:8000';
  
  // API Version
  static const String apiVersion = '/api/v1';
  
  // Full API Base URL
  static String get apiBaseUrl => '$baseUrl$apiVersion';
  
  // Shared Preferences Keys
  static const String prefAuthToken = 'auth_token';
  static const String prefUser = 'user';
  static const String prefCurrency = 'currency';
  static const String prefLanguage = 'language';
  
  // Default values
  static const String defaultCurrency = 'USD';
  static const String defaultLanguage = 'en';
  
  // Pagination
  static const int defaultPageSize = 15;
  
  // Timeouts
  static const int connectionTimeout = 30000;
  static const int receiveTimeout = 30000;
}