// product_models.dart - COMPLETE FIXED VERSION


class VendorProduct {
  final int id;
  final String name;
  final String slug;
  final List<String> images;
  final int vendorId;
  final int vendorProductId;
  final String vendorName;
  final String vendorSlug;
  final double? vendorRating;
  final int stock;
  final String currency;
  final double price;
  final double? salePrice;
  final int? discountPercent;
  final String? category;
  final String? brand;
  final bool hasVariations;
  final String? createdAt;

  VendorProduct({
    required this.id,
    required this.name,
    required this.slug,
    required this.images,
    required this.vendorId,
    required this.vendorProductId,
    required this.vendorName,
    required this.vendorSlug,
    this.vendorRating,
    required this.stock,
    required this.currency,
    required this.price,
    this.salePrice,
    this.discountPercent,
    this.category,
    this.brand,
    required this.hasVariations,
    this.createdAt,
  });

  factory VendorProduct.fromJson(Map<String, dynamic> json) {
    print('🔵 VENDOR PRODUCT PARSING: ${json['name']}');
    print('🔵 VENDOR PRODUCT KEYS: ${json.keys}');
    print('🔵 VENDOR PRODUCT - price: ${json['price']}');
    print('🔵 VENDOR PRODUCT - display_price: ${json['display_price']}');
    print('🔵 VENDOR PRODUCT - original_price: ${json['original_price']}');
    print('🔵 VENDOR PRODUCT - sale_price: ${json['sale_price']}');
    
    // Try to get price from various possible field names
    double productPrice = 0;
    if (json['price'] != null && json['price'] != 0) {
      productPrice = (json['price'] ?? 0).toDouble();
    } else if (json['display_price'] != null && json['display_price'] != 0) {
      productPrice = (json['display_price'] ?? 0).toDouble();
    } else if (json['original_price'] != null && json['original_price'] != 0) {
      productPrice = (json['original_price'] ?? 0).toDouble();
    }
  
    // Try to get sale price
    double? productSalePrice;
    if (json['sale_price'] != null && json['sale_price'] != 0) {
      productSalePrice = (json['sale_price'] ?? 0).toDouble();
    }
    
    // Try to get discount
    int? productDiscount;
    if (json['discount'] != null) {
      productDiscount = json['discount'];
    } else if (json['discount_percent'] != null) {
      productDiscount = json['discount_percent'];
    }
    
    return VendorProduct(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      slug: json['slug'] ?? '',
      images: List<String>.from(json['images'] ?? []),
      vendorId: json['vendor_id'] ?? 0,
      vendorProductId: json['vendor_product_id'] ?? 0,
      vendorName: json['vendor_name'] ?? '',
      vendorSlug: json['vendor_slug'] ?? '',
      vendorRating: (json['vendor_rating'] ?? 0).toDouble(),
      stock: json['stock'] ?? 0,
      currency: json['currency'] ?? 'USD',
      price: productPrice,
      salePrice: productSalePrice,
      discountPercent: productDiscount,
      category: json['category'],
      brand: json['brand'],
      hasVariations: json['has_variations'] ?? false,
      createdAt: json['created_at'],
    );
  }

  // Check if product is on sale
  bool get isOnSale => salePrice != null && salePrice! < price && salePrice! > 0;

  // Get display price (sale price if on sale, otherwise regular price)
double get displayPrice => isOnSale ? salePrice! : price;

// Get discount percentage
int? get discount => isOnSale && price > 0
    ? ((price - salePrice!) / price * 100).round()
    : discountPercent;

// Formatted display price
String get formattedDisplayPrice {
  final priceToShow = isOnSale ? (salePrice ?? price) : price;
  if (priceToShow <= 0) return '${_getSymbol(currency)} 0.00';
  return '${_getSymbol(currency)} ${priceToShow.toStringAsFixed(2)}';
}

// Formatted original price
String get formattedOriginalPrice {
  if (!isOnSale || price <= 0) return '';
  return '${_getSymbol(currency)} ${price.toStringAsFixed(2)}';
}

  // First image URL
  String? get imageUrl => images.isNotEmpty ? images.first : null;

  String _getSymbol(String currencyCode) {
    switch (currencyCode) {
      case 'USD':
        return '\$';
      case 'EUR':
        return '€';
      case 'GBP':
        return '£';
      case 'GNF':
        return 'FG';
      case 'CNY':
        return '¥';
      default:
        return '\$';
    }
  }
}

class VendorProductResponse {
  final List<VendorProduct> items;
  final int currentPage;
  final int lastPage;
  final int total;
  final int perPage;

  VendorProductResponse({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.perPage,
  });

  factory VendorProductResponse.fromJson(Map<String, dynamic> json) {
    print('🔵 PARSING RESPONSE - JSON keys: ${json.keys}');
    
    List<VendorProduct> items = [];
    
    // Handle Laravel pagination structure
    if (json.containsKey('data')) {
      print('🔵 Found "data" key, type: ${json['data'].runtimeType}');
      final data = json['data'];
      
      if (data is List) {
        print('🔵 Data is List with ${data.length} items');
        items = data.map((e) => VendorProduct.fromJson(e as Map<String, dynamic>)).toList();
      } else if (data is Map<String, dynamic>) {
        // Check if it's a paginated response with nested data
        if (data.containsKey('data')) {
          print('🔵 Nested data found');
          final nestedData = data['data'];
          if (nestedData is List) {
            items = nestedData.map((e) => VendorProduct.fromJson(e as Map<String, dynamic>)).toList();
          }
        } else {
          print('🔵 Data is single product');
          items = [VendorProduct.fromJson(data)];
        }
      }
    } else if (json.containsKey('items')) {
      print('🔵 Found "items" key');
      final dataList = json['items'];
      if (dataList is List) {
        items = dataList.map((e) => VendorProduct.fromJson(e as Map<String, dynamic>)).toList();
      }
    } else if (json.containsKey('products')) {
      print('🔵 Found "products" key');
      final dataList = json['products'];
      if (dataList is List) {
        items = dataList.map((e) => VendorProduct.fromJson(e as Map<String, dynamic>)).toList();
      }
    }

    print('🔵 PARSED ${items.length} products');
    
    // Debug first product if exists
    if (items.isNotEmpty) {
      print('🔵 First product name: ${items.first.name}');
      print('🔵 First product price: ${items.first.price}');
      print('🔵 First product currency: ${items.first.currency}');
    }
    
    // Get pagination data
    int currentPage = json['current_page'] ?? 1;
    int lastPage = json['last_page'] ?? 1;
    int total = json['total'] ?? items.length;
    int perPage = json['per_page'] ?? items.length;

    return VendorProductResponse(
      items: items,
      currentPage: currentPage,
      lastPage: lastPage,
      total: total,
      perPage: perPage,
    );
  }

  static VendorProductResponse fromJsonList(List<dynamic> jsonList) {
    final items = jsonList.map((e) => VendorProduct.fromJson(e as Map<String, dynamic>)).toList();
    
    return VendorProductResponse(
      items: items,
      currentPage: 1,
      lastPage: 1,
      total: items.length,
      perPage: items.length,
    );
  }
}