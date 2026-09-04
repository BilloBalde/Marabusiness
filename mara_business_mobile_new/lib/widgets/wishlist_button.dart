// lib/widgets/wishlist_button.dart - SIMPLE VERSION

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../core/providers/wishlist_provider.dart';

class WishlistButton extends StatefulWidget {
  final int vendorProductId; // This is all we need
  final double? size;
  final Color? color;
  final bool showFeedback;

  const WishlistButton({
    super.key,
    required this.vendorProductId,
    this.size,
    this.color,
    this.showFeedback = true,
  });

  @override
  State<WishlistButton> createState() => _WishlistButtonState();
}

class _WishlistButtonState extends State<WishlistButton> {
  @override
  Widget build(BuildContext context) {
    return Consumer<WishlistProvider>(
      builder: (context, wishlistProvider, child) {
        final isInWishlist = wishlistProvider.isInWishlist(widget.vendorProductId);
        
        return GestureDetector(
          onTap: () async {
            final success = await wishlistProvider.toggleWishlist(widget.vendorProductId);
            
            if (success && widget.showFeedback && mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(isInWishlist ? 'Retiré des favoris' : 'Ajouté aux favoris'),
                  duration: const Duration(seconds: 1),
                  backgroundColor: isInWishlist ? Colors.orange : Colors.green,
                  behavior: SnackBarBehavior.floating,
                ),
              );
            }
          },
          child: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
              border: Border.all(color: const Color(0xFFD4AF37)),
            ),
            child: Icon(
              isInWishlist ? Icons.favorite : Icons.favorite_border,
              color: isInWishlist ? Colors.red : const Color(0xFFD4AF37),
              size: widget.size ?? 20,
            ),
          ),
        );
      },
    );
  }
}