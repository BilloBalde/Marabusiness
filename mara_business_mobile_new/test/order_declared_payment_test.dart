import 'package:flutter_test/flutter_test.dart';
import 'package:mara_business_mobile_new/core/models/order.dart';

/// An offline payment the buyer has declared does not count towards total_paid
/// until the vendor confirms the money arrived, so such an order still reports
/// payment_status 'pending' — byte for byte the same as one where nothing was
/// ever paid. The app used those two fields alone to decide whether to show the
/// payment button, so it kept inviting a buyer who had already handed cash to
/// the courier to pay again.
///
/// declared_awaiting_confirmation is what separates the two cases. These tests
/// pin the parsing and the derived flag, including the case where the field is
/// absent — an app talking to an older API build must keep working.
void main() {
  Map<String, dynamic> orderJson(Map<String, dynamic> overrides) {
    return {
      'id': 1,
      'order_number': 'ORD-1',
      'created_at': '2026-09-09 10:00:00',
      'status': 'new',
      'payment_status': 'pending',
      'grand_total': 290000,
      'total_paid': 0,
      'total_remaining': 290000,
      'shipping_amount': 0,
      'payment_method': 'cod',
      'currency': 'GNF',
      'items': <dynamic>[],
      ...overrides,
    };
  }

  group('Order.declaredAwaitingConfirmation', () {
    test('a declared but unconfirmed payment is visible on the order', () {
      final order = Order.fromJson(
        orderJson({'declared_awaiting_confirmation': 290000}),
      );

      expect(order.declaredAwaitingConfirmation, 290000);
      expect(order.hasPaymentAwaitingConfirmation, isTrue);
    });

    test('an order with nothing declared is not marked as awaiting', () {
      final order = Order.fromJson(
        orderJson({'declared_awaiting_confirmation': 0}),
      );

      expect(order.hasPaymentAwaitingConfirmation, isFalse);
    });

    test('a declared payment is distinguishable from an unpaid order', () {
      // Both orders report payment_status 'pending' and total_paid 0. Only this
      // field tells them apart, which is the whole reason it exists.
      final declared = Order.fromJson(
        orderJson({'declared_awaiting_confirmation': 290000}),
      );
      final untouched = Order.fromJson(orderJson({}));

      expect(declared.paymentStatus, untouched.paymentStatus);
      expect(declared.totalPaid, untouched.totalPaid);
      expect(
        declared.hasPaymentAwaitingConfirmation,
        isNot(untouched.hasPaymentAwaitingConfirmation),
      );
    });

    test('an API response without the field parses as nothing declared', () {
      final order = Order.fromJson(orderJson({}));

      expect(order.declaredAwaitingConfirmation, 0);
      expect(order.hasPaymentAwaitingConfirmation, isFalse);
    });

    test('an integer amount from JSON is read as a double', () {
      // PHP casts this to float, but json_encode drops a trailing .0, so the
      // field arrives as an int whenever the amount is whole — which for GNF,
      // a currency with no minor unit, is every single time.
      final order = Order.fromJson(
        orderJson({'declared_awaiting_confirmation': 290000}),
      );

      expect(order.declaredAwaitingConfirmation, isA<double>());
    });
  });
}
