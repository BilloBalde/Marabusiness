// lib/core/models/product_detail_models.dart
class SimilarProduct {
  final int id;
  final String name;
  final String slug;
  final String? image;
  final int vendorProductId;
  final String vendorName;
  final String vendorSlug;
  final String currency;
  final String priceDisplay;
  final bool hasVariations;
  final int variationsCount;
  final bool inStock;

  SimilarProduct({
    required this.id,
    required this.name,
    required this.slug,
    this.image,
    required this.vendorProductId,
    required this.vendorName,
    required this.vendorSlug,
    required this.currency,
    required this.priceDisplay,
    required this.hasVariations,
    required this.variationsCount,
    required this.inStock,
  });

  factory SimilarProduct.fromJson(Map<String, dynamic> json) {
    return SimilarProduct(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      slug: json['slug'] ?? '',
      image: json['image'],
      vendorProductId: json['vendor_product_id'] ?? 0,
      vendorName: json['vendor_name'] ?? '',
      vendorSlug: json['vendor_slug'] ?? '',
      currency: json['currency'] ?? 'USD',
      priceDisplay: json['price_display'] ?? '0.00',
      hasVariations: json['has_variations'] ?? false,
      variationsCount: json['variations_count'] ?? 0,
      inStock: json['in_stock'] ?? false,
    );
  }
}

class ProductDetail {
  final int id;
  final String name;
  final String description;
  final String? shortDescription;
  final String slug;
  final List<String> images;
  final List<String> descriptionImages;
  final String? video;
  final String? videoType;
  final String? videoThumbnail;
  final String? videoUrl;
  
  // Vendor info
  final int vendorProductId;
  final int vendorId;
  final String vendorName;
  final String vendorSlug;
  final String? vendorLogo;
  final double vendorRating;
  
  // Currency and prices
  final String currency;
  final double displayPrice;
  final double originalPrice;
  final double? salePrice;
  final String? saleEnd;
  final int? discount;
  
  // Stock
  final int stock;
  final String? sku;
  final int minOrderQuantity;
  final int maxOrderQuantity;
  
  // Variations
  final bool hasVariations;
  final List<Variation> variations;
  final Map<String, List<String>> variationAttributes;
  
  // Wholesale
  final bool hasWholesale;
  final List<WholesaleTier> wholesaleTiers;
  final Map<int, List<WholesaleTier>> wholesaleTiersByVariation;
  
  // Product info
  final Map<String, dynamic>? specifications;
  final dynamic category;
  final dynamic brand;
  final List<String>? tags;
  
  // Reviews
  final List<Review> reviews;
  final double rating;
  final int reviewsCount;
  final Map<String, dynamic>? userReview;

  final List<SimilarProduct> similarProducts;

  ProductDetail({
    required this.id,
    required this.name,
    required this.description,
    this.shortDescription,
    required this.slug,
    required this.images,
    required this.descriptionImages,
    this.video,
    this.videoType,
    this.videoThumbnail,
    this.videoUrl,
    required this.vendorProductId,
    required this.vendorId,
    required this.vendorName,
    required this.vendorSlug,
    this.vendorLogo,
    required this.vendorRating,
    required this.currency,
    required this.displayPrice,
    required this.originalPrice,
    this.salePrice,
    this.saleEnd,
    this.discount,
    required this.stock,
    this.sku,
    required this.minOrderQuantity,
    required this.maxOrderQuantity,
    required this.hasVariations,
    required this.variations,
    required this.variationAttributes,
    required this.hasWholesale,
    required this.wholesaleTiers,
    required this.wholesaleTiersByVariation,
    this.specifications,
    this.category,
    this.brand,
    this.tags,
    required this.reviews,
    required this.rating,
    required this.reviewsCount,
    this.userReview,
    required this.similarProducts,
  });

  // In product_detail_models.dart, update the fromJson factory:
  // In product_detail_models.dart, update the fromJson factory - FOCUS ON LINE 131

factory ProductDetail.fromJson(Map<String, dynamic> json) {
  print('🔵 Building ProductDetail from JSON');
  print('🔵 JSON keys: ${json.keys}');
  print('🔵 has_variations: ${json['has_variations']}');
  print('🔵 variation_attributes type: ${json['variation_attributes'].runtimeType}');
  
  // Helper to parse double safely
  double parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }

  // Parse variations - handle if it's a List or null
  List<Variation> variations = [];
  if (json['variations'] != null) {
    if (json['variations'] is List) {
      variations = (json['variations'] as List)
          .map((v) => Variation.fromJson(v as Map<String, dynamic>))
          .toList();
    }
  }

  // FIXED: Parse variation attributes - handle both Map and List
  Map<String, List<String>> variationAttributes = {};
  if (json['variation_attributes'] != null) {
    final varAttr = json['variation_attributes'];
    print('🔵 variation_attributes value: $varAttr');
    
    if (varAttr is Map) {
      // It's a Map - good!
      (varAttr).forEach((key, value) {
        if (value is List) {
          variationAttributes[key.toString()] = List<String>.from(value);
        }
      });
    } else if (varAttr is List) {
      // It's a List (empty probably) - just use empty map
      print('🔵 variation_attributes is a List (empty product with no variations)');
      // Keep empty map
    }
  }

  // Parse wholesale tiers
  List<WholesaleTier> wholesaleTiers = [];
  if (json['wholesale_tiers'] != null) {
    if (json['wholesale_tiers'] is List) {
      wholesaleTiers = (json['wholesale_tiers'] as List)
          .map((t) => WholesaleTier.fromJson(t as Map<String, dynamic>))
          .toList();
    }
  }

  // Parse wholesale tiers by variation
  Map<int, List<WholesaleTier>> wholesaleTiersByVariation = {};
  if (json['wholesale_tiers_by_variation'] != null) {
    if (json['wholesale_tiers_by_variation'] is Map) {
      (json['wholesale_tiers_by_variation'] as Map).forEach((key, value) {
        int varId = int.parse(key.toString());
        if (value is List) {
          wholesaleTiersByVariation[varId] = (value)
              .map((t) => WholesaleTier.fromJson(t as Map<String, dynamic>))
              .toList();
        }
      });
    }
  }

  // Parse reviews
  List<Review> reviews = [];
  if (json['reviews'] != null) {
    if (json['reviews'] is List) {
      reviews = (json['reviews'] as List)
          .map((r) => Review.fromJson(r as Map<String, dynamic>))
          .toList();
    } else if (json['reviews'] is Map) {
      final reviewsData = json['reviews']['data'];
      if (reviewsData is List) {
        reviews = (reviewsData)
            .map((r) => Review.fromJson(r as Map<String, dynamic>))
            .toList();
      }
    }
  }

  List<SimilarProduct> similarProducts = [];
  if (json['similar_products'] != null) {
    if (json['similar_products'] is List) {
      similarProducts = (json['similar_products'] as List)
          .map((s) => SimilarProduct.fromJson(s as Map<String, dynamic>))
          .toList();
    }
  }

  return ProductDetail(
    id: json['id'] ?? 0,
    name: json['name'] ?? '',
    description: json['description'] ?? '',
    shortDescription: json['short_description'],
    slug: json['slug'] ?? '',
    images: List<String>.from(json['images'] ?? []),
    descriptionImages: List<String>.from(json['description_images'] ?? []),
    video: json['video'],
    videoType: json['video_type'],
    videoThumbnail: json['video_thumbnail'],
    videoUrl: json['video_url'],
    vendorProductId: json['vendor_product_id'] ?? 0,
    vendorId: json['vendor_id'] ?? 0,
    vendorName: json['vendor_name'] ?? '',
    vendorSlug: json['vendor_slug'] ?? '',
    vendorLogo: json['vendor_logo'],
    vendorRating: parseDouble(json['vendor_rating']),
    currency: json['currency'] ?? 'USD',
    displayPrice: parseDouble(json['display_price']),
    originalPrice: parseDouble(json['original_price']),
    salePrice: json['sale_price'] != null ? parseDouble(json['sale_price']) : null,
    saleEnd: json['sale_end'],
    discount: json['discount'],
    stock: json['stock'] ?? 0,
    sku: json['sku'],
    minOrderQuantity: json['min_order_quantity'] ?? 1,
    maxOrderQuantity: json['max_order_quantity'] ?? 10,
    hasVariations: json['has_variations'] ?? false,
    variations: variations,
    variationAttributes: variationAttributes,
    hasWholesale: json['has_wholesale'] ?? false,
    wholesaleTiers: wholesaleTiers,
    wholesaleTiersByVariation: wholesaleTiersByVariation,
    specifications: json['specifications'],
    category: json['category'],
    brand: json['brand'],
    tags: json['tags'] != null ? List<String>.from(json['tags']) : null,
    reviews: reviews,
    rating: parseDouble(json['rating']),
    reviewsCount: json['reviews_count'] ?? 0,
    userReview: json['user_review'],
    similarProducts: similarProducts,
  );
}
  
  String get formattedDisplayPrice {
    return '${_getSymbol(currency)} ${displayPrice.toStringAsFixed(2)}';
  }

  String get formattedOriginalPrice {
    if (originalPrice <= 0) return '';
    return '${_getSymbol(currency)} ${originalPrice.toStringAsFixed(2)}';
  }

  String _getSymbol(String currencyCode) {
    switch (currencyCode) {
      case 'USD': return '\$';
      case 'EUR': return '€';
      case 'GBP': return '£';
      case 'GNF': return 'FG';
      case 'CNY': return '¥';
      default: return '\$';
    }
  }
}

class Variation {
  final int id;
  final Map<String, String> attributes;
  final String prettyAttributes;
  final String? sku;
  final int stock;
  final double price;
  final double? salePrice;
  final String? image;

  Variation({
    required this.id,
    required this.attributes,
    required this.prettyAttributes,
    this.sku,
    required this.stock,
    required this.price,
    this.salePrice,
    this.image,
  });

  factory Variation.fromJson(Map<String, dynamic> json) {
    double parseDouble(dynamic value) {
      if (value == null) return 0.0;
      if (value is double) return value;
      if (value is int) return value.toDouble();
      if (value is String) return double.tryParse(value) ?? 0.0;
      return 0.0;
    }

    return Variation(
      id: json['id'] ?? 0,
      attributes: Map<String, String>.from(json['attributes'] ?? {}),
      prettyAttributes: json['pretty_attributes'] ?? '',
      sku: json['sku'],
      stock: json['stock'] ?? 0,
      price: parseDouble(json['price']),
      salePrice: json['sale_price'] != null ? parseDouble(json['sale_price']) : null,
      image: json['image'],
    );
  }

  bool get isOnSale => salePrice != null && salePrice! < price;
  double get displayPrice => isOnSale ? salePrice! : price;
}

class WholesaleTier {
  final int id;
  final int minQty;
  final int? maxQty;
  final double price;

  WholesaleTier({
    required this.id,
    required this.minQty,
    this.maxQty,
    required this.price,
  });

  factory WholesaleTier.fromJson(Map<String, dynamic> json) {
    double parseDouble(dynamic value) {
      if (value == null) return 0.0;
      if (value is double) return value;
      if (value is int) return value.toDouble();
      if (value is String) return double.tryParse(value) ?? 0.0;
      return 0.0;
    }

    return WholesaleTier(
      id: json['id'] ?? 0,
      minQty: json['min_qty'] ?? 0,
      maxQty: json['max_qty'],
      price: parseDouble(json['price']),
    );
  }
}

class Review {
  final int id;
  final int userId;
  final String userName;
  final String? userAvatar;
  final int rating;
  final String comment;
  final String? createdAt;
  final String? createdAtHuman;
  final bool verifiedPurchase;
  final bool isApproved; // Add this

  Review({
    required this.id,
    required this.userId,
    required this.userName,
    this.userAvatar,
    required this.rating,
    required this.comment,
    this.createdAt,
    this.createdAtHuman,
    required this.verifiedPurchase,
    this.isApproved = true,
  });

  factory Review.fromJson(Map<String, dynamic> json) {
    return Review(
      id: json['id'] ?? 0,
      userId: json['user_id'] ?? 0,
      userName: json['user_name'] ?? 'Anonyme',
      userAvatar: json['user_avatar'],
      rating: json['rating'] ?? 0,
      comment: json['comment'] ?? '',
      createdAt: json['created_at'],
      createdAtHuman: json['created_at_human'],
      verifiedPurchase: json['verified_purchase'] ?? false,
    );
  }
}