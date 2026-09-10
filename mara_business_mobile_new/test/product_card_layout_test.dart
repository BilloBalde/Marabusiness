import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mara_business_mobile_new/core/models/product_unified.dart';
import 'package:mara_business_mobile_new/widgets/product_card.dart';

/// ProductCard was written for the horizontal carousels on the home screen and
/// hard-coded `width: 160` with a trailing 12px gap. The brief asks for a
/// staggered two-column grid on the home page, where the cell decides the width
/// and the card heights deliberately differ so the columns fall out of step.
///
/// A 160-wide card inside a grid cell either overflows a narrow column or leaves
/// a gap in a wide one, so the card now takes its sizing from the parent when
/// `inGrid` is set, and its image height from `imageHeight`.
void main() {
  UnifiedProduct product({int id = 1}) {
    return UnifiedProduct(
      id: id,
      name: 'Test Product',
      slug: 'test-product',
      // Empty so the card renders its own grey placeholder instead of reaching
      // for the network, which a widget test cannot serve.
      images: const [],
      stock: 5,
      currency: 'GNF',
      displayPrice: 50000,
      originalPrice: 50000,
      hasVendor: true,
    );
  }

  Future<void> pump(WidgetTester tester, Widget child) async {
    await tester.pumpWidget(
      MaterialApp(home: Scaffold(body: child)),
    );
  }

  testWidgets('a carousel card keeps its fixed width', (tester) async {
    await pump(
      tester,
      Row(children: [ProductCard(product: product(), onTap: () {})]),
    );

    final box = tester.renderObject<RenderBox>(
      find.byType(ProductCard),
    );

    // 160 of card plus the 12 trailing margin that separates carousel cards;
    // the margin is inside the Container, so it counts towards the box.
    expect(box.size.width, 172);
  });

  testWidgets('a grid card takes the width its cell gives it', (tester) async {
    // 300 wide, split into two columns with a 12px gap, is 144 per column —
    // narrower than the 160 the card used to insist on, which is exactly the
    // case that overflowed.
    await pump(
      tester,
      SizedBox(
        width: 300,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(child: ProductCard(product: product(), onTap: () {}, inGrid: true)),
            const SizedBox(width: 12),
            Expanded(child: ProductCard(product: product(id: 2), onTap: () {}, inGrid: true)),
          ],
        ),
      ),
    );

    expect(tester.takeException(), isNull, reason: 'a grid card must not overflow its cell');

    final widths = tester
        .renderObjectList<RenderBox>(find.byType(ProductCard))
        .map((box) => box.size.width)
        .toList();

    expect(widths, [144.0, 144.0]);
  });

  testWidgets('a taller image makes a taller card, which is what staggers the columns',
      (tester) async {
    await pump(
      tester,
      SizedBox(
        width: 300,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: ProductCard(
                product: product(),
                onTap: () {},
                inGrid: true,
                imageHeight: 180,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ProductCard(
                product: product(id: 2),
                onTap: () {},
                inGrid: true,
                imageHeight: 125,
              ),
            ),
          ],
        ),
      ),
    );

    final heights = tester
        .renderObjectList<RenderBox>(find.byType(ProductCard))
        .map((box) => box.size.height)
        .toList();

    // Two cards side by side must not be the same height — if they were, the
    // grid would sit in lockstep rows and the layout would be the uniform one
    // the brief is moving away from.
    expect(heights[0], greaterThan(heights[1]));
    expect(heights[0] - heights[1], closeTo(55, 1), reason: '180 - 125');
  });
}
