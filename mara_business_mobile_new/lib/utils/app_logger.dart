import 'package:flutter/foundation.dart';

/// Debug-only logging.
///
/// The app used `print()` in 352 places across 27 files. `print()` writes to the
/// device log in release builds too — logcat on Android, Console on iOS — and a
/// good number of those calls dumped API responses and error bodies, which carry
/// account data. Anything reading the device log could read them.
///
/// `debugPrint` alone would not have fixed it: it also prints in release. The
/// guard is `kDebugMode`, a compile-time constant, so in a release build the
/// whole call is removed by the tree shaker rather than merely silenced.
void logDebug(Object? message) {
  if (kDebugMode) {
    debugPrint('$message');
  }
}
