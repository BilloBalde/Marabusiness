import '../core/constants/app_constants.dart';
import '../utils/app_logger.dart';
import 'package:flutter_web_auth_2/flutter_web_auth_2.dart';
import 'dart:convert';

class GoogleLoginService {
  static Future<Map<String, dynamic>?> authenticate() async {
    // AppConstants.baseUrl, not a private hardcoded copy — that copy always
    // pointed at production regardless of --dart-define=API_BASE_URL, so a
    // build meant to test against a local Laravel server (10.0.2.2:8000,
    // 127.0.0.1:8000) silently sent Google sign-in to production instead.
    // AppConstants.baseUrl's own docblock already explains exactly why a
    // second hardcoded copy is the wrong pattern — it used to be the api
    // base itself, before that lesson was learned there.
    final authUrl = '${AppConstants.baseUrl}/auth/google?platform=mobile';

    try {
      // Open the browser for OAuth
      final result = await FlutterWebAuth2.authenticate(
        url: authUrl,
        callbackUrlScheme: 'mara',
      );

      // The result is the full callback URL: mara://login/callback?data=...
      final uri = Uri.parse(result);
      final dataParam = uri.queryParameters['data'];
      if (dataParam == null) {
        throw Exception('No data in callback');
      }

      // Decode base64
      final jsonString = utf8.decode(base64Decode(dataParam));
      final Map<String, dynamic> userData = jsonDecode(jsonString);
      return userData;
    } catch (e) {
      logDebug('Google login error: $e');
      return null;
    }
  }
}