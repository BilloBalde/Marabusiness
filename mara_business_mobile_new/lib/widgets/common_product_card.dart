// lib/widgets/common_product_card.dart - WISHLIST REMOVED

import '../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/models/home_models.dart';
import '../core/models/products_page_models.dart';

class CommonProductCard extends StatelessWidget {
  final dynamic product;
  final VoidCallback onTap;

  const CommonProductCard({
    super.key,
    required this.product,
    required this.onTap,
  });

  String? get imageUrl {
    if (product is Product) {
      return (product as Product).imageUrl;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).image;
    }
    return null;
  }

  String get name {
    if (product is Product) {
      return (product as Product).name;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).name;
    }
    return '';
  }

  String get vendorName {
    if (product is Product) {
      return (product as Product).vendorName ?? '';
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).vendorName;
    }
    return '';
  }

  String get currency {
    if (product is Product) {
      return (product as Product).currency;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).currency;
    }
    return 'USD';
  }

  double get displayPrice {
    if (product is Product) {
      return (product as Product).displayPrice;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).displayPrice;
    }
    return 0;
  }

  double? get salePrice {
    if (product is Product) {
      return (product as Product).salePrice;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).salePrice;
    }
    return null;
  }

  int? get discount {
    if (product is Product) {
      return (product as Product).discount;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).discount;
    }
    return null;
  }

  int get vendorProductId {
    if (product is Product) {
      return (product as Product).vendorProductId ?? 0;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).id;
    }
    return 0;
  }

  String get slug {
    if (product is Product) {
      return (product as Product).slug;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).slug;
    }
    return '';
  }

  bool get isOnSale {
    if (product is Product) {
      return (product as Product).salePrice != null;
    } else if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).salePrice != null;
    }
    return false;
  }

  String get displayText {
    if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).displayText;
    }
    return '';
  }

  String get vendorStockText {
    if (product is ProductsPageProduct) {
      return (product as ProductsPageProduct).vendorStockText;
    }
    return '';
  }

  /// This copy was the only one that fell back to the placeholder instead of an
  /// empty string. ImageUrl makes that the default everywhere.
  String _getFullImageUrl(String? path) => ImageUrl.resolve(path);

  String _getCurrencySymbol(String currencyCode) {
    switch (currencyCode) {
      case 'USD': return '\$';
      case 'EUR': return '€';
      case 'GBP': return '£';
      case 'GNF': return 'FG';
      case 'CNY': return '¥';
      default: return '\$';
    }
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withValues(alpha: 0.1),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image - FIXED HEIGHT
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(8)),
                  child: CachedNetworkImage(
                    imageUrl: _getFullImageUrl(imageUrl),
                    height: 120,
                    width: double.infinity,
                    fit: BoxFit.cover,
                    placeholder: (_, __) => Container(
                      height: 120,
                      color: Colors.grey[200],
                      child: const Center(
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                    ),
                    errorWidget: (_, __, ___) => Container(
                      height: 120,
                      color: Colors.grey[200],
                      child: const Icon(Icons.image_not_supported, color: Colors.grey, size: 30),
                    ),
                  ),
                ),
                
                // Discount badge
                if (isOnSale && discount != null && discount! > 0)
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                      decoration: BoxDecoration(
                        color: Colors.red,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        '-$discount%',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
              ],
            ),

            // Details - REDUCED PADDING
            Padding(
              padding: const EdgeInsets.all(6.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product name - SMALLER FONT
                  Text(
                    name,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 12,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),

                  const SizedBox(height: 2),

                  // Vendor info - FIXED OVERFLOW
                  if (product is ProductsPageProduct && vendorStockText.isNotEmpty)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey[300]!, width: 0.5),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        vendorStockText,
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey[700],
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                      ),
                    )
                  else
                    Text(
                      vendorName,
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey[600],
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),

                  const SizedBox(height: 6),

                  // Price row - FIXED OVERFLOW (Wishlist button removed)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Price - takes all available space
                      Expanded(
                        child: product is ProductsPageProduct && displayText.isNotEmpty
                            ? Text(
                                displayText,
                                style: const TextStyle(
                                  color: Color(0xFFD4AF37),
                                  fontWeight: FontWeight.bold,
                                  fontSize: 12,
                                ),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              )
                            : Row(
                                children: [
                                  Flexible(
                                    child: Text(
                                      '${_getCurrencySymbol(currency)} ${displayPrice.toStringAsFixed(2)}',
                                      style: const TextStyle(
                                        color: Color(0xFFD4AF37),
                                        fontWeight: FontWeight.bold,
                                        fontSize: 12,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  if (isOnSale && salePrice != null && salePrice! < displayPrice) ...[
                                    const SizedBox(width: 2),
                                    Flexible(
                                      child: Text(
                                        '${_getCurrencySymbol(currency)} ${salePrice!.toStringAsFixed(2)}',
                                        style: TextStyle(
                                          fontSize: 8,
                                          decoration: TextDecoration.lineThrough,
                                          color: Colors.grey[500],
                                        ),
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                      ),
                      
                      // No more wishlist button
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}