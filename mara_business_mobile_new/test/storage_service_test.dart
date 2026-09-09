import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:mara_business_mobile_new/services/storage_service.dart';

/// clearAuth() used to delete only the secure copy of the token, while
/// AuthProvider writes the working one to SharedPreferences under the same key —
/// so signing out through it left the real token in place, and getAuthToken()
/// handed it straight back. Nothing called clearAuth() at the time, so nobody was
/// affected; it is now wired to the 401 handler, which is exactly the use that
/// would have hit the bug.
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  // flutter_secure_storage talks over a MethodChannel; back it with a plain map.
  final Map<String, String> secureStore = {};

  setUp(() async {
    secureStore.clear();
    SharedPreferences.setMockInitialValues({});

    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(
      const MethodChannel('plugins.it_nomads.com/flutter_secure_storage'),
      (MethodCall call) async {
        switch (call.method) {
          case 'write':
            secureStore[call.arguments['key'] as String] =
                call.arguments['value'] as String;
            return null;
          case 'read':
            return secureStore[call.arguments['key'] as String];
          case 'delete':
            secureStore.remove(call.arguments['key'] as String);
            return null;
          case 'deleteAll':
            secureStore.clear();
            return null;
          case 'readAll':
            return secureStore;
          default:
            return null;
        }
      },
    );

    await StorageService.init();
  });

  test('the token is written to secure storage, not shared preferences', () async {
    final storage = StorageService();
    await storage.saveAuthToken('secret-token');

    expect(secureStore['auth_token'], 'secret-token');

    final prefs = await SharedPreferences.getInstance();
    expect(prefs.getString('auth_token'), isNull,
        reason: 'the token must not land in the plain-text store');
  });

  test('a token left in shared preferences by an older build is still read',
      () async {
    // Migration path: nobody already signed in gets kicked out by the move.
    SharedPreferences.setMockInitialValues({'auth_token': 'legacy-token'});
    await StorageService.init();

    expect(await StorageService().getAuthToken(), 'legacy-token');
  });

  test('clearAuth removes the token from both stores', () async {
    SharedPreferences.setMockInitialValues({'auth_token': 'legacy-token'});
    await StorageService.init();
    final storage = StorageService();
    await storage.saveAuthToken('secure-token');

    await storage.clearAuth();

    expect(secureStore['auth_token'], isNull);
    expect(await storage.getAuthToken(), isNull,
        reason: 'the SharedPreferences copy used to survive clearAuth()');
  });

  test('clearAuth also drops the cached user', () async {
    final storage = StorageService();
    await storage.saveUser({'id': 1, 'name': 'Test'});
    expect(storage.getUser(), isNotNull);

    await storage.clearAuth();

    expect(storage.getUser(), isNull);
  });
}
