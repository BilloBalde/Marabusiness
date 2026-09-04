import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../core/models/home_models.dart'; // Import the Category model
import '../core/constants/app_constants.dart';

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
                        imageUrl: category.image != null && category.image!.isNotEmpty
                            ? '${AppConstants.baseUrl}/uploads/${category.image}'
                            : 'https://via.placeholder.com/80',
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