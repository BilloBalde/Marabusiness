import 'package:flutter_web_auth_2/flutter_web_auth_2.dart';
import 'dart:convert';
import 'dart:typed_data';

class GoogleLoginService {
  static const String authBaseUrl = 'https://afrobridgeinnov.com'; // change to your domain

  static Future<Map<String, dynamic>?> authenticate() async {
    // The redirect URI must match the custom scheme + path
    final authUrl = '$authBaseUrl/auth/google?platform=mobile';

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
      print('Google login error: $e');
      return null;
    }
  }
}