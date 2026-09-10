import '../core/constants/app_constants.dart';

/// The one place an image path becomes a URL.
///
/// This was written out by hand in fourteen places — five near-identical private
/// helpers and nine inline expressions — and no two agreed. Each handled a case
/// the others got wrong:
///
///   - only common_product_card returned a placeholder for an empty path; the
///     rest returned '', which renders as a broken image
///   - only product_detail_screen noticed a path already starting with
///     'uploads/'; elsewhere it became /uploads/uploads/...
///   - only order_detail_screen and success_page handled a leading '/';
///     elsewhere it became /uploads//path
///   - only home_screen stripped leading slashes and backslashes
///
/// The API makes all of these reachable, because it is not consistent either:
/// products, categories and banners come back as relative paths
/// ('products/x.jpg') while VendorPresenter and ApiProductsPageController build
/// absolute ones with url('uploads/'...). A screen using the wrong helper for
/// the field it is showing produces a URL that 404s.
class ImageUrl {
  /// The placeholder that ships with the application.
  ///
  /// Six Blade views and the mobile cards fall back to this same file, so an
  /// item with no picture looks the same on the web and in the app.
  static String get placeholder => '${AppConstants.baseUrl}/uploads/default.png';

  /// Turns whatever the API returned into something loadable.
  ///
  /// [fallback] is what an empty path resolves to. It defaults to the
  /// placeholder rather than an empty string: a widget handed '' shows a broken
  /// image, which is worse than showing "no picture" deliberately. Pass an empty
  /// string where the caller would rather hide the image entirely.
  static String resolve(String? path, {String? fallback}) {
    final raw = path?.trim() ?? '';

    if (raw.isEmpty) {
      return fallback ?? placeholder;
    }

    // Already absolute — VendorPresenter and ApiProductsPageController build
    // these with url('uploads/'...), so they arrive complete and must not be
    // prefixed again.
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
      return raw;
    }

    // Leading slashes and backslashes appear in stored paths from several
    // eras; without stripping them the join produces a doubled separator.
    var cleaned = raw.replaceAll(RegExp(r'^[/\\]+'), '');

    // A path that already names the uploads directory must not get a second one.
    if (cleaned.startsWith('uploads/')) {
      cleaned = cleaned.substring('uploads/'.length);
    }

    if (cleaned.isEmpty) {
      return fallback ?? placeholder;
    }

    return '${AppConstants.baseUrl}/uploads/$cleaned';
  }

  /// Resolves the first usable entry of an image list.
  ///
  /// Products carry a list, and several arrive empty — the API returns
  /// `"images": []` for products nobody has photographed.
  static String fromList(List<String>? images, {String? fallback}) {
    if (images == null || images.isEmpty) {
      return fallback ?? placeholder;
    }

    return resolve(
      images.firstWhere((i) => i.trim().isNotEmpty, orElse: () => ''),
      fallback: fallback,
    );
  }
}
