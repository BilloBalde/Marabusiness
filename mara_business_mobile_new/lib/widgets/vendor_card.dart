import '../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/models/home_models.dart';

class VendorCard extends StatelessWidget {
  final Vendor vendor; // Change from Map<String, dynamic> to Vendor
  final VoidCallback onTap;

  const VendorCard({
    super.key,
    required this.vendor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 150,
        margin: const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withValues(alpha: 0.1),
              spreadRadius: 1,
              blurRadius: 4,
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              height: 80,
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.grey[200],
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(8),
                ),
              ),
              child: vendor.logo != null && vendor.logo!.isNotEmpty && vendor.logo != 'logo'
                  ? CachedNetworkImage(
                      // VendorPresenter returns an absolute url for a logo while
                      // products and categories come back relative; this site
                      // prefixed both alike, doubling the host on the absolute one.
                      imageUrl: ImageUrl.resolve(vendor.logo),
                      fit: BoxFit.cover,
                      placeholder: (_, __) => const Center(
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                      errorWidget: (_, __, ___) => const Center(
                        child: Icon(Icons.store, size: 40, color: Color(0xFFD4AF37)),
                      ),
                    )
                  : const Center(
                      child: Icon(Icons.store, size: 40, color: Color(0xFFD4AF37)),
                    ),
            ),
            Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          vendor.storeName,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (vendor.isVerified == true)
                        const Icon(
                          Icons.verified,
                          color: Colors.blue,
                          size: 16,
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star, color: Colors.amber, size: 14),
                      const SizedBox(width: 2),
                      Text(
                        vendor.rating?.toStringAsFixed(1) ?? '4.5',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '${vendor.productsCount ?? 0} products',
                        style: TextStyle(
                          fontSize: 10,
                          color: Colors.grey[500],
                        ),
                      ),
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