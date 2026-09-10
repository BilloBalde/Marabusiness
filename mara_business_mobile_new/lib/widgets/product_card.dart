// product_card.dart - WISHLIST REMOVED

import '../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/models/product_unified.dart';

class ProductCard extends StatelessWidget {
  final UnifiedProduct product;
  final VoidCallback onTap;

  /// Fixed width, and the trailing gap that goes with it, suit the horizontal
  /// carousels this card was written for. In a grid the cell decides the width,
  /// and a card that insists on 160 either overflows a narrow column or leaves a
  /// gap in a wide one — so [inGrid] hands sizing back to the parent.
  final bool inGrid;

  /// Lets the staggered home grid vary card heights so the two columns fall out
  /// of step with each other, which is what gives that layout its shape. The
  /// carousels leave it alone and keep the original 125.
  final double imageHeight;

  const ProductCard({
    super.key,
    required this.product,
    required this.onTap,
    this.inGrid = false,
    this.imageHeight = 125,
  });

  @override
  Widget build(BuildContext context) {
    final hasDiscount = product.isOnSale;
    final inStock = product.stock > 0;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: inGrid ? null : 160,
        margin: inGrid ? EdgeInsets.zero : const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withValues(alpha: 0.1),
              spreadRadius: 1,
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image with discount badge
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(8),
                  ),
                  child: product.imageUrl != null && product.imageUrl!.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: ImageUrl.resolve(product.imageUrl),
                          height: imageHeight,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          placeholder: (context, url) => Container(
                            height: imageHeight,
                            color: Colors.grey[300],
                            child: const Center(
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                          ),
                          errorWidget: (context, url, error) => Container(
                            height: imageHeight,
                            color: Colors.grey[300],
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.broken_image, color: Colors.grey, size: 40),
                                Text(
                                  'ID: ${product.id}',
                                  style: const TextStyle(fontSize: 10, color: Colors.grey),
                                ),
                              ],
                            ),
                          ),
                        )
                      : Container(
                          height: imageHeight,
                          color: Colors.grey[300],
                          child: const Center(
                            child: Icon(Icons.image_not_supported, color: Colors.grey),
                          ),
                        ),
                ),
                
                // Wishlist button - REMOVED
                
                // Discount badge
                if (hasDiscount)
                  Positioned(
                    top: 8,
                    right: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.red,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        '-${product.discount}%',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            
            Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product name
                  Text(
                    product.name,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  
                  // Vendor name
                  if (product.vendorName != null)
                    Text(
                      product.vendorName!,
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey[600],
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  
                  const SizedBox(height: 4),
                  
                  // PRICE SECTION
                  if (hasDiscount) ...[
                    // Sale price
                    Text(
                      product.formattedDisplayPrice,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFD4AF37),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    // Original price with line-through
                    Text(
                      product.formattedOriginalPrice,
                      style: TextStyle(
                        fontSize: 12,
                        decoration: TextDecoration.lineThrough,
                        color: Colors.grey[500],
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ] else ...[
                    // Regular price
                    Text(
                      product.formattedDisplayPrice,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFD4AF37),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                  
                  const SizedBox(height: 2),
                  
                  // Stock status
                  Text(
                    inStock ? 'En stock' : 'Rupture de stock',
                    style: TextStyle(
                      fontSize: 10,
                      color: inStock ? Colors.green : Colors.red,
                      fontWeight: FontWeight.w500,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
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