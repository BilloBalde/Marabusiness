// lib/core/models/products_page_models.dart

class ProductsPageResponse {
  final bool success;
  final ProductsPageData data;
  final String message;

  ProductsPageResponse({
    required this.success,
    required this.data,
    required this.message,
  });

  factory ProductsPageResponse.fromJson(Map<String, dynamic> json) {
    return ProductsPageResponse(
      success: json['success'] ?? false,
      data: ProductsPageData.fromJson(json['data'] ?? {}),
      message: json['message'] ?? '',
    );
  }
}

class ProductsPageData {
  final List<ProductsPageProduct> products;
  final ProductsPagePagination pagination;
  final ProductsPageFilters filters;

  ProductsPageData({
    required this.products,
    required this.pagination,
    required this.filters,
  });

  factory ProductsPageData.fromJson(Map<String, dynamic> json) {
    return ProductsPageData(
      products: (json['products'] as List? ?? [])
          .map((e) => ProductsPageProduct.fromJson(e as Map<String, dynamic>))
          .toList(),
      pagination: ProductsPagePagination.fromJson(json['pagination'] ?? {}),
      filters: ProductsPageFilters.fromJson(json['filters'] ?? {}),
    );
  }
}

class ProductsPageProduct {
  final int id; // This is vendor_product_id
  final int productId;
  final String name;
  final String slug;
  final String? image;
  final List<String> images;
  final int vendorId;
  final String vendorName;
  final String vendorSlug;
  final String vendorCurrency;
  final int stock;
  final bool inStock;
  final String currency;
  final double displayPrice;
  final double minPrice;
  final double maxPrice;
  final bool hasPriceRange;
  final int? discount;
  final double? salePrice;
  final double? originalPrice;
  final bool hasVariations;
  final int variationsCount;
  final String displayText;
  final String? originalText;
  final String stockText;
  final String vendorStockText;

  ProductsPageProduct({
    required this.id,
    required this.productId,
    required this.name,
    required this.slug,
    this.image,
    required this.images,
    required this.vendorId,
    required this.vendorName,
    required this.vendorSlug,
    required this.vendorCurrency,
    required this.stock,
    required this.inStock,
    required this.currency,
    required this.displayPrice,
    required this.minPrice,
    required this.maxPrice,
    required this.hasPriceRange,
    this.salePrice,
    this.originalPrice,
    this.discount,
    required this.hasVariations,
    required this.variationsCount,
    required this.displayText,
    this.originalText,
    required this.stockText,
    required this.vendorStockText,
  });

  factory ProductsPageProduct.fromJson(Map<String, dynamic> json) {
    print('🔵 PRODUCTS PAGE PRODUCT: ${json['name']}');
    print('🔵 PRODUCTS PAGE KEYS: ${json.keys}');
    print('🔵 PRODUCTS PAGE - display_price: ${json['display_price']}');
    print('🔵 PRODUCTS PAGE - min_price: ${json['min_price']}');
    print('🔵 PRODUCTS PAGE - max_price: ${json['max_price']}');
    // Helper function to convert dynamic to double safely
    double toDouble(dynamic value) {
      if (value == null) return 0.0;
      if (value is double) return value;
      if (value is int) return value.toDouble();
      if (value is String) {
        // Remove commas and convert
        return double.tryParse(value.replaceAll(',', '')) ?? 0.0;
      }
      return 0.0;
    }
    return ProductsPageProduct(
      id: json['id'] ?? 0,
      productId: json['product_id'] ?? 0,
      name: json['name'] ?? '',
      slug: json['slug'] ?? '',
      image: json['image'],
      images: List<String>.from(json['images'] ?? []),
      vendorId: json['vendor_id'] ?? 0,
      vendorName: json['vendor_name'] ?? '',
      vendorSlug: json['vendor_slug'] ?? '',
      vendorCurrency: json['vendor_currency'] ?? 'USD',
      stock: json['stock'] ?? 0,
      inStock: json['in_stock'] ?? false,
      currency: json['currency'] ?? 'USD',
      displayPrice: toDouble(json['display_price'] ?? 0),
      minPrice: toDouble(json['min_price'] ?? 0),
      maxPrice: toDouble(json['max_price'] ?? 0),
      hasPriceRange: json['has_price_range'] ?? false,
      salePrice: toDouble(json['sale_price']),
      originalPrice: toDouble(json['original_price']),
      hasVariations: json['has_variations'] ?? false,
      variationsCount: json['variations_count'] ?? 0,
      displayText: json['display_text'] ?? '',
      originalText: json['original_text'],
      stockText: json['stock_text'] ?? '',
      vendorStockText: json['vendor_stock_text'] ?? '',
    );
  }
}

class ProductsPagePagination {
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;
  final int? from;
  final int? to;

  ProductsPagePagination({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
    this.from,
    this.to,
  });

  factory ProductsPagePagination.fromJson(Map<String, dynamic> json) {
    return ProductsPagePagination(
      currentPage: json['current_page'] ?? 1,
      lastPage: json['last_page'] ?? 1,
      perPage: json['per_page'] ?? 12,
      total: json['total'] ?? 0,
      from: json['from'],
      to: json['to'],
    );
  }
}

class ProductsPageFilters {
  final List<Category> categories;
  final List<Brand> brands;
  final int priceRange;
  final String sort;
  final String currency;

  ProductsPageFilters({
    required this.categories,
    required this.brands,
    required this.priceRange,
    required this.sort,
    required this.currency,
  });

  factory ProductsPageFilters.fromJson(Map<String, dynamic> json) {
    return ProductsPageFilters(
      categories: (json['categories'] as List? ?? [])
          .map((e) => Category.fromJson(e as Map<String, dynamic>))
          .toList(),
      brands: (json['brands'] as List? ?? [])
          .map((e) => Brand.fromJson(e as Map<String, dynamic>))
          .toList(),
      priceRange: json['price_range'] ?? 0,
      sort: json['sort'] ?? 'latest',
      currency: json['currency'] ?? 'USD',
    );
  }
}

class Category {
  final int id;
  final String name;

  Category({required this.id, required this.name});

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }
}

class Brand {
  final int id;
  final String name;

  Brand({required this.id, required this.name});

  factory Brand.fromJson(Map<String, dynamic> json) {
    return Brand(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
    );
  }
}