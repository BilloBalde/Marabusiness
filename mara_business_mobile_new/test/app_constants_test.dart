import 'package:flutter_test/flutter_test.dart';
import 'package:mara_business_mobile_new/core/constants/app_constants.dart';

/// baseUrl used to be six stacked declarations with five commented out, switched
/// by editing source. A build shipped with the wrong line uncommented would point
/// the whole app at a localhost no phone can reach, and nothing in review would
/// show it. It is now --dart-define driven, and these guard the default.
void main() {
  group('AppConstants.baseUrl', () {
    test('defaults to production when no --dart-define is given', () {
      expect(AppConstants.baseUrl, 'https://afrobridgeinnov.com');
    });

    test('is always https', () {
      expect(AppConstants.baseUrl.startsWith('https://'), isTrue);
    });

    test('never points at a loopback or private address by default', () {
      const forbidden = ['localhost', '127.0.0.1', '10.0.2.2', '192.168.'];
      for (final needle in forbidden) {
        expect(AppConstants.baseUrl.contains(needle), isFalse,
            reason: 'baseUrl must not ship pointing at $needle');
      }
    });

    test('apiBaseUrl appends the versioned prefix', () {
      expect(AppConstants.apiBaseUrl, '${AppConstants.baseUrl}/api/v1');
    });
  });
}
