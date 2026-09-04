class HomeData {
  final Currency currency;
  final List<Product> featuredProducts;
  final List<Product> saleProducts;
  final List<Category> categories;
  final List<Vendor> vendors;
  final List<Service> services;
  final Map<String, String> banners;

  HomeData({
    required this.currency,
    required this.featuredProducts,
    required this.saleProducts,
    required this.categories,
    required this.vendors,
    required this.services,
    required this.banners,
  });

  factory HomeData.fromJson(Map<String, dynamic> json) {
  // Helper to safely parse currency
  Currency parseCurrency(dynamic value) {
    if (value is Map<String, dynamic>) {
      return Currency.fromJson(value);
    }
    // If it's a List or something else, return default
    return Currency(code: 'USD', rate: 1);
  }

  // Helper to safely parse a list of products
  List<Product> parseProducts(dynamic value) {
    if (value is List) {
      return value.map((e) => Product.fromJson(e)).toList();
    }
    return [];
  }

  // Similar for categories, vendors, services
  List<Category> parseCategories(dynamic value) {
    if (value is List) {
      return value.map((e) => Category.fromJson(e)).toList();
    }
    return [];
  }

  // etc.

  return HomeData(
    currency: parseCurrency(json['currency']),
    featuredProducts: parseProducts(json['featured_products']),
    saleProducts: parseProducts(json['sale_products']),
    categories: parseCategories(json['categories']),
    vendors: (json['vendors'] is List)
        ? (json['vendors'] as List).map((e) => Vendor.fromJson(e)).toList()
        : [],
    services: (json['services'] is List)
        ? (json['services'] as List).map((e) => Service.fromJson(e)).toList()
        : [],
    banners: (json['banners'] is Map) ? Map<String, String>.from(json['banners']) : {},
  );
}
}

class Currency {
  final String code;
  final double rate;

  Currency({required this.code, required this.rate});

  factory Currency.fromJson(Map<String, dynamic> json) {
    return Currency(
      code: json['code'],
      rate: (json['rate'] ?? 1).toDouble(),
    );
  }

  String get symbol {
    switch (code) {
      case 'USD':
        return '\$';
      case 'EUR':
        return '€';
      case 'GBP':
        return '£';
      case 'GNF':
        return 'FG';
      default:
        return '\$';
    }
  }
}

class Product {
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

  Product({
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
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      images: List<String>.from(json['images'] ?? []),
      vendorId: json['vendor_id'],
      vendorProductId: json['vendor_product_id'],
      vendorName: json['vendor_name'],
      vendorSlug: json['vendor_slug'],
      stock: json['stock'] ?? 0,
      currency: json['currency'] ?? 'USD',
      displayPrice: (json['display_price'] ?? 0).toDouble(),
      originalPrice: (json['original_price'] ?? 0).toDouble(),
      salePrice: json['sale_price']?.toDouble(),
      saleEnd: json['sale_end'],
      discount: json['discount'],
      hasVendor: json['has_vendor'] ?? false,
    );
  }

  factory Product.fromVendorProductJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      images: List<String>.from(json['images'] ?? []),
      vendorId: json['vendor_id'], // You might need to get this from context
      vendorProductId: json['vendor_product_id'],
      vendorName: json['vendor_name'], // You might need to get this
      vendorSlug: json['vendor_slug'],
      stock: json['stock'] ?? 0,
      currency: json['currency'] ?? 'USD',
      displayPrice: (json['price'] ?? 0).toDouble(),
      originalPrice: (json['price'] ?? 0).toDouble(),
      salePrice: json['sale_price']?.toDouble(),
      saleEnd: json['sale_end'],
      discount: json['discount_percent'],
      hasVendor: true,
    );
  }

  String get formattedPrice {
    return '${_getSymbol()} ${displayPrice.toStringAsFixed(2)}';
  }

  String get formattedOriginalPrice {
    if (originalPrice <= 0) return '';
    return '${_getSymbol()} ${originalPrice.toStringAsFixed(2)}';
  }

  String _getSymbol() {
    switch (currency) {
      case 'USD':
        return '\$';
      case 'EUR':
        return '€';
      case 'GBP':
        return '£';
      case 'GNF':
        return 'FG';
      default:
        return '\$';
    }
  }

  String? get imageUrl => images.isNotEmpty ? images.first : null;
}

class Category {
  final int id;
  final String name;
  final String slug;
  final String? image;

  Category({
    required this.id,
    required this.name,
    required this.slug,
    this.image,
  });

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      image: json['image'],
    );
  }
}

class Vendor {
  final int id;
  final String storeName;
  final String slug;
  final String? logo;
  final String? banner;
  final String? description;  // Add this
  final String? currency;      // Add this
  final double? currencyRate;  // Add this
  final double? rating;        // You already have this
  final int? reviewsCount;     // Add this
  final int? followersCount;   // Add this
  final int? productsCount;    // You already have this
  final bool? isVerified;      // You already have this
  final bool? isFeatured;      // Add this
  final String? createdAt;     // Add this

  Vendor({
    required this.id,
    required this.storeName,
    required this.slug,
    this.logo,
    this.banner,
    this.description,
    this.currency,
    this.currencyRate,
    this.rating,
    this.reviewsCount,
    this.followersCount,
    this.productsCount,
    this.isVerified,
    this.isFeatured,
    this.createdAt,
  });

  factory Vendor.fromJson(Map<String, dynamic> json) {
    // Handle potential quoted keys from API
    String? logoValue;
    if (json.containsKey('logo')) {
      logoValue = json['logo'];
    } else if (json.containsKey('"logo"')) {
      logoValue = json['"logo"'];
    }
    
    String? bannerValue;
    if (json.containsKey('banner')) {
      bannerValue = json['banner'];
    } else if (json.containsKey('"banner"')) {
      bannerValue = json['"banner"'];
    }

    return Vendor(
      id: json['id'],
      storeName: json['store_name'] ?? '',
      slug: json['slug'] ?? '',
      logo: logoValue != 'logo' ? logoValue : null,
      banner: bannerValue != 'banner' ? bannerValue : null,
      description: json['description'],
      currency: json['currency'],
      currencyRate: (json['currency_rate'] ?? 1).toDouble(),
      rating: (json['rating'] ?? json['vendor_rating'] ?? 4.5).toDouble(),
      reviewsCount: json['reviews_count'] ?? json['approved_vendor_reviews_count'] ?? 0,
      followersCount: json['followers_count'] ?? 0,
      productsCount: json['products_count'] ?? 0,
      isVerified: json['is_verified'] ?? false,
      isFeatured: json['is_featured'] ?? false,
      createdAt: json['created_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'store_name': storeName,
      'slug': slug,
      'logo': logo,
      'banner': banner,
      'description': description,
      'currency': currency,
      'currency_rate': currencyRate,
      'rating': rating,
      'reviews_count': reviewsCount,
      'followers_count': followersCount,
      'products_count': productsCount,
      'is_verified': isVerified,
      'is_featured': isFeatured,
      'created_at': createdAt,
    };
  }
}
class Service {
  final int id;
  final String name;
  final String slug;
  final String? icon;
  final String? shortDescription;

  Service({
    required this.id,
    required this.name,
    required this.slug,
    this.icon,
    this.shortDescription,
  });

  factory Service.fromJson(Map<String, dynamic> json) {
    return Service(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      icon: json['icon'],
      shortDescription: json['short_description'],
    );
  }
}