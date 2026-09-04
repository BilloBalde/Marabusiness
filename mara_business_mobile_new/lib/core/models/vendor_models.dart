import 'home_models.dart'; // For Product, Category, etc.
import 'package:intl/intl.dart';

class VendorDetail {
  final Vendor vendor;
  final VendorProducts products;
  final VendorReviews reviews;
  final bool isFollowing;
  final Map<String, dynamic>? userReview;

  VendorDetail({
    required this.vendor,
    required this.products,
    required this.reviews,
    required this.isFollowing,
    this.userReview,
  });

  factory VendorDetail.fromJson(Map<String, dynamic> json) {
    return VendorDetail(
      vendor: Vendor.fromJson(json['vendor']),
      products: VendorProducts.fromJson(json['products']),
      reviews: VendorReviews.fromJson(json['reviews']),
      isFollowing: json['is_following'] ?? false,
      userReview: json['user_review'],
    );
  }
}

class VendorProducts {
  final List<VendorProduct> items;
  final int currentPage;
  final int lastPage;
  final int total;

  VendorProducts({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory VendorProducts.fromJson(Map<String, dynamic> json) {
    // Handle paginated response
    if (json.containsKey('data')) {
      return VendorProducts(
        items: (json['data'] as List)
            .map((e) => VendorProduct.fromJson(e))
            .toList(),
        currentPage: json['current_page'] ?? 1,
        lastPage: json['last_page'] ?? 1,
        total: json['total'] ?? 0,
      );
    }
    
    // Handle direct list
    return VendorProducts(
      items: (json as List).map((e) => VendorProduct.fromJson(e)).toList(),
      currentPage: 1,
      lastPage: 1,
      total: (json as List).length,
    );
  }
}

class VendorProduct {
  final int id;
  final String name;
  final String slug;
  final List<String> images;
  final String? category;
  final String? brand;
  final int vendorProductId;
  final double price;
  final double? salePrice;
  final int? discountPercent;
  final int stock;
  final bool hasVariations;
  final String currency;
  final String? createdAt;

  VendorProduct({
    required this.id,
    required this.name,
    required this.slug,
    required this.images,
    this.category,
    this.brand,
    required this.vendorProductId,
    required this.price,
    this.salePrice,
    this.discountPercent,
    required this.stock,
    required this.hasVariations,
    required this.currency,
    this.createdAt,
  });

  factory VendorProduct.fromJson(Map<String, dynamic> json) {
    return VendorProduct(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      images: List<String>.from(json['images'] ?? []),
      category: json['category'],
      brand: json['brand'],
      vendorProductId: json['vendor_product_id'],
      price: (json['price'] ?? 0).toDouble(),
      salePrice: json['sale_price']?.toDouble(),
      discountPercent: json['discount_percent'],
      stock: json['stock'] ?? 0,
      hasVariations: json['has_variations'] ?? false,
      currency: json['currency'] ?? 'USD',
      createdAt: json['created_at'],
    );
  }

  double get displayPrice => salePrice ?? price;
  
  String get formattedPrice {
    // Format with thousand separator
    final formatter = NumberFormat('#,###.##', 'fr');
    return '${_getSymbol(currency)} ${formatter.format(displayPrice)}';
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

  String? get imageUrl => images.isNotEmpty ? images.first : null;
}
class VendorReviews {
  final List<VendorReview> items;
  final int currentPage;
  final int lastPage;
  final int total;

  VendorReviews({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory VendorReviews.fromJson(Map<String, dynamic> json) {
    // Handle paginated response
    if (json.containsKey('data')) {
      return VendorReviews(
        items: (json['data'] as List)
            .map((e) => VendorReview.fromJson(e))
            .toList(),
        currentPage: json['current_page'] ?? 1,
        lastPage: json['last_page'] ?? 1,
        total: json['total'] ?? 0,
      );
    }
    
    // Handle direct list
    return VendorReviews(
      items: (json as List).map((e) => VendorReview.fromJson(e)).toList(),
      currentPage: 1,
      lastPage: 1,
      total: (json as List).length,
    );
  }
}

class VendorReview {
  final int id;
  final int userId;
  final String userName;
  final String? userAvatar;
  final int rating;
  final String comment;
  final bool isApproved;
  final DateTime createdAt;
  final String createdAtHuman;

  VendorReview({
    required this.id,
    required this.userId,
    required this.userName,
    this.userAvatar,
    required this.rating,
    required this.comment,
    required this.isApproved,
    required this.createdAt,
    required this.createdAtHuman,
  });

  factory VendorReview.fromJson(Map<String, dynamic> json) {
    return VendorReview(
      id: json['id'],
      userId: json['user_id'],
      userName: json['user_name'] ?? 'Anonymous',
      userAvatar: json['user_avatar'],
      rating: json['rating'],
      comment: json['comment'],
      isApproved: json['is_approved'] ?? false,
      createdAt: DateTime.parse(json['created_at'] ?? DateTime.now().toIso8601String()),
      createdAtHuman: json['created_at_human'] ?? 'Just now',
    );
  }
}

// Extended Vendor model with more fields for detail page
class ExtendedVendor extends Vendor {
  final String? address;
  final String? phone;
  final String? email;
  final String? website;
  final Map<String, String>? socialLinks;
  final Map<String, int>? ratingBreakdown;

  ExtendedVendor({
    required super.id,
    required super.storeName,
    required super.slug,
    super.logo,
    super.banner,
    super.description,
    super.currency,
    super.currencyRate,
    super.rating,
    super.reviewsCount,
    super.followersCount,
    super.productsCount,
    super.isVerified,
    super.isFeatured,
    super.createdAt,
    this.address,
    this.phone,
    this.email,
    this.website,
    this.socialLinks,
    this.ratingBreakdown,
  });

  factory ExtendedVendor.fromJson(Map<String, dynamic> json) {
    return ExtendedVendor(
      id: json['id'],
      storeName: json['store_name'] ?? '',
      slug: json['slug'] ?? '',
      logo: json['logo'],
      banner: json['banner'],
      description: json['description'],
      currency: json['currency'],
      currencyRate: (json['currency_rate'] ?? 1).toDouble(),
      rating: (json['rating'] ?? 4.5).toDouble(),
      reviewsCount: json['reviews_count'] ?? 0,
      followersCount: json['followers_count'] ?? 0,
      productsCount: json['products_count'] ?? 0,
      isVerified: json['is_verified'] ?? false,
      isFeatured: json['is_featured'] ?? false,
      createdAt: json['created_at'],
      address: json['address'],
      phone: json['phone'],
      email: json['email'],
      website: json['website'],
      socialLinks: json['social_links'] != null 
          ? Map<String, String>.from(json['social_links']) 
          : null,
      ratingBreakdown: json['rating_breakdown'] != null
          ? Map<String, int>.from(json['rating_breakdown'])
          : null,
    );
  }
}