// Create a new file: core/models/product_unified.dart
import 'home_models.dart';
import 'product_models.dart';
/// Unified product model that works for both home screen and vendor products
class UnifiedProduct {
  final int id;
  final String name;
  final String slug;
  final List<String> images;
  final int? vendorId;
  final int? vendorProductId;
  final String? vendorName;
  final String? vendorSlug;
  final int stock;
  final String currency;
  final double displayPrice;
  final double originalPrice;
  final double? salePrice;
  final String? saleEnd;
  final int? discount;
  final bool hasVendor;
  final bool hasVariations;
  final String? createdAt;

  UnifiedProduct({
    required this.id,
    required this.name,
    required this.slug,
    required this.images,
    this.vendorId,
    this.vendorProductId,
    this.vendorName,
    this.vendorSlug,
    required this.stock,
    required this.currency,
    required this.displayPrice,
    required this.originalPrice,
    this.salePrice,
    this.saleEnd,
    this.discount,
    required this.hasVendor,
    this.hasVariations = false,
    this.createdAt,
  });

  // Convert from home_models.Product
  factory UnifiedProduct.fromHomeProduct(Product product) {
    return UnifiedProduct(
      id: product.id,
      name: product.name,
      slug: product.slug,
      images: product.images,
      vendorId: product.vendorId,
      vendorProductId: product.vendorProductId,
      vendorName: product.vendorName,
      vendorSlug: product.vendorSlug,
      stock: product.stock,
      currency: product.currency,
      displayPrice: product.displayPrice,
      originalPrice: product.originalPrice,
      salePrice: product.salePrice,
      saleEnd: product.saleEnd,
      discount: product.discount,
      hasVendor: product.hasVendor,
      hasVariations: false,
    );
  }

  // Convert from product_models.VendorProduct
  factory UnifiedProduct.fromVendorProduct(VendorProduct product) {
    return UnifiedProduct(
      id: product.id,
      name: product.name,
      slug: product.slug,
      images: product.images,
      vendorId: product.vendorId,
      vendorProductId: product.vendorProductId,
      vendorName: product.vendorName,
      vendorSlug: product.vendorSlug,
      stock: product.stock,
      currency: product.currency,
      displayPrice: product.displayPrice,
      originalPrice: product.price,
      salePrice: product.salePrice,
      saleEnd: null, // VendorProduct doesn't have saleEnd
      discount: product.discount,
      hasVendor: true,
      hasVariations: product.hasVariations,
      createdAt: product.createdAt,
    );
  }

  // Check if product is on sale
  bool get isOnSale => salePrice != null && salePrice! < originalPrice;

  // Get display price (sale price if on sale, otherwise regular price)
  double get effectiveDisplayPrice => isOnSale ? salePrice! : displayPrice;

  // First image URL
  String? get imageUrl => images.isNotEmpty ? images.first : null;

  String get currencySymbol {
    switch (currency) {
      case 'USD': return '\$';
      case 'EUR': return '€';
      case 'GBP': return '£';
      case 'GNF': return 'FG';
      case 'CNY': return '¥';
      default: return '\$';
    }
  }

  String get formattedDisplayPrice {
    return '$currencySymbol ${effectiveDisplayPrice.toStringAsFixed(2)}';
  }

  String get formattedOriginalPrice {
    if (!isOnSale) return '';
    return '$currencySymbol ${originalPrice.toStringAsFixed(2)}';
  }
}