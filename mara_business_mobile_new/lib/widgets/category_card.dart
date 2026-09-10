import '../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/models/home_models.dart'; // Import the Category model

class CategoryCard extends StatelessWidget {
  final Category category; // Change from Map<String, dynamic> to Category
  final VoidCallback onTap;

  const CategoryCard({
    super.key,
    required this.category, // Update here
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 80,
        margin: const EdgeInsets.only(right: 8),
        child: Column(
          children: [
            Container(
              width: 60,
              height: 60,
              decoration: BoxDecoration(
                color: Colors.grey[200],
                borderRadius: BorderRadius.circular(30),
                border: Border.all(
                  color: const Color(0xFFD4AF37),
                  width: 1,
                ),
              ),
              child: category.image != null
                  ? ClipRRect(
                      borderRadius: BorderRadius.circular(30),
                      child:CachedNetworkImage(
                        // Was via.placeholder.com, a service that no longer exists;
                        // ImageUrl falls back to the local placeholder on its own.
                        imageUrl: ImageUrl.resolve(category.image),
                        imageBuilder: (context, imageProvider) => Container(
                          width: 60,
                          height: 60,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(30),
                            image: DecorationImage(
                              image: imageProvider,
                              fit: BoxFit.cover,
                            ),
                          ),
                        ),
                        placeholder: (context, url) => Container(
                          width: 60,
                          height: 60,
                          decoration: BoxDecoration(
                            color: Colors.grey[300],
                            borderRadius: BorderRadius.circular(30),
                          ),
                          child: const Center(
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                        ),
                        errorWidget: (context, url, error) => Container(
                          width: 60,
                          height: 60,
                          decoration: BoxDecoration(
                            color: Colors.grey[300],
                            borderRadius: BorderRadius.circular(30),
                          ),
                          child: const Icon(Icons.category, color: Color(0xFFD4AF37), size: 30),
                        ),
                      ),
                    )
                  : const Icon(
                      Icons.category,
                      color: Color(0xFFD4AF37),
                      size: 30,
                    ),
            ),
            const SizedBox(height: 4),
            Text(
              category.name, // Access name directly
              style: const TextStyle(fontSize: 12),
              maxLines: 2,
              textAlign: TextAlign.center,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}