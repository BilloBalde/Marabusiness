import 'package:flutter_test/flutter_test.dart';
import 'package:mara_business_mobile_new/core/constants/app_constants.dart';
import 'package:mara_business_mobile_new/utils/image_url.dart';

/// Every case here was handled correctly by exactly one of the five hand-written
/// helpers this replaces, and wrongly by the other four.
void main() {
  final base = AppConstants.baseUrl;

  group('ImageUrl.resolve', () {
    test('a relative path gets the uploads prefix', () {
      // What products, categories and banners return.
      expect(
        ImageUrl.resolve('products/01KDBCHF.jpg'),
        '$base/uploads/products/01KDBCHF.jpg',
      );
    });

    test('an absolute url is left alone', () {
      // VendorPresenter and ApiProductsPageController build these with
      // url('uploads/'...), so they arrive complete. Prefixing again produced
      // .../uploads/http://.../uploads/...
      const absolute = 'https://afrobridgeinnov.com/uploads/vendors/logo.jpg';

      expect(ImageUrl.resolve(absolute), absolute);
    });

    test('a path already naming uploads is not doubled', () {
      // Only product_detail_screen caught this; elsewhere it became
      // /uploads/uploads/products/x.jpg.
      expect(
        ImageUrl.resolve('uploads/products/x.jpg'),
        '$base/uploads/products/x.jpg',
      );
    });

    test('a leading slash does not produce a doubled separator', () {
      // Only order_detail_screen and success_page handled this.
      expect(ImageUrl.resolve('/products/x.jpg'), '$base/uploads/products/x.jpg');
    });

    test('leading backslashes are stripped too', () {
      // Only home_screen stripped these; stored paths from older imports carry
      // Windows separators.
      expect(ImageUrl.resolve('\\products\\x.jpg'), '$base/uploads/products\\x.jpg');
    });

    test('an empty path falls back to the placeholder, not a broken image', () {
      // Four of the five helpers returned '', which renders as a broken image
      // rather than as "no picture".
      expect(ImageUrl.resolve(''), ImageUrl.placeholder);
      expect(ImageUrl.resolve(null), ImageUrl.placeholder);
      expect(ImageUrl.resolve('   '), ImageUrl.placeholder);
    });

    test('a caller can ask for an empty result instead of the placeholder', () {
      // Some screens would rather hide the image entirely than show a grey box.
      expect(ImageUrl.resolve(null, fallback: ''), '');
    });

    test('a path that is nothing but separators falls back', () {
      expect(ImageUrl.resolve('///'), ImageUrl.placeholder);
      expect(ImageUrl.resolve('uploads/'), ImageUrl.placeholder);
    });
  });

  group('ImageUrl.fromList', () {
    test('takes the first image', () {
      expect(
        ImageUrl.fromList(['products/a.jpg', 'products/b.jpg']),
        '$base/uploads/products/a.jpg',
      );
    });

    test('an empty list falls back to the placeholder', () {
      // The API returns "images": [] for products nobody has photographed —
      // two of them exist in the catalogue today.
      expect(ImageUrl.fromList([]), ImageUrl.placeholder);
      expect(ImageUrl.fromList(null), ImageUrl.placeholder);
    });

    test('skips blank entries rather than resolving to a broken url', () {
      expect(
        ImageUrl.fromList(['', '  ', 'products/c.jpg']),
        '$base/uploads/products/c.jpg',
      );
    });

    test('a list of only blanks falls back', () {
      expect(ImageUrl.fromList(['', '   ']), ImageUrl.placeholder);
    });
  });
}
