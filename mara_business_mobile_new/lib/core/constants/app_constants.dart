class AppConstants {
  static const String appName = 'MARA BUSINESS';
  
  /// Set at build time, production by default.
  ///
  /// This used to be six `baseUrl` lines stacked on top of each other with five
  /// commented out, so switching environments meant editing source and
  /// remembering to put it back. A build shipped with the wrong line uncommented
  /// points the whole app at a localhost that no phone can reach — and nothing
  /// in the code or the review would show it.
  ///
  /// Override per build instead of editing this file:
  ///
  ///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000   # Android emulator
  ///   flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000  # iOS simulator
  ///   flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000  # real device
  ///
  /// With no --dart-define, this is production, which is what a release build
  /// should be without anyone having to remember anything.
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://afrobridgeinnov.com',
  );


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