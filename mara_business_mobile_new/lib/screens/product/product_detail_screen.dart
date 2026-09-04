// lib/screens/product/product_detail_screen.dart - WISHLIST REMOVED

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:carousel_slider/carousel_slider.dart';
import 'package:go_router/go_router.dart';
import '../../core/providers/product_provider.dart';
import '../../core/providers/cart_provider.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/constants/app_constants.dart';
import '../../core/models/product_detail_models.dart';
import '../../widgets/rating_bar.dart';
import '../../widgets/review_card.dart';
import '../../widgets/review_list_screen.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:share_plus/share_plus.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';

class ProductDetailScreen extends StatefulWidget {
  final String slug;
  final int vendorProductId;

  const ProductDetailScreen({
    super.key,
    required this.slug,
    required this.vendorProductId,
  });

  @override
  State<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends State<ProductDetailScreen> {
  final GlobalKey _shareButtonKey = GlobalKey();
  int _quantity = 1;
  int? _selectedVariationId;
  final Map<String, String> _selectedAttributes = {};
  String _customNote = '';
  int _currentImageIndex = 0;
  VideoPlayerController? _videoController;
  ChewieController? _chewieController;
  bool _isVideoInitialized = false;
  bool _isPlayingVideo = false;
  String _mainMediaType = 'image';
  
  // Review states
  int _rating = 0;
  final TextEditingController _commentController = TextEditingController();
  final CarouselSliderController _carouselController = CarouselSliderController();
  bool _isEditingReview = false;

  // Wholesale tiers
  List<Map<String, dynamic>> _wholesaleTiers = [];
  Map<int, List<Map<String, dynamic>>> _wholesaleTiersByVariation = {};

  bool _isAddingToCart = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadProductData();
    });
  }

  @override
  void dispose() {
    _commentController.dispose();
    _videoController?.dispose();
    super.dispose();
  }

  Future<void> _loadProductData() async {
    final provider = context.read<ProductProvider>();
    await provider.loadProductDetail(widget.slug, widget.vendorProductId);
    
    if (provider.productDetail != null) {
      final product = provider.productDetail!;
      
      setState(() {
        // Parse wholesale tiers from the model
        _wholesaleTiers = product.wholesaleTiers.map((tier) => {
          'min_qty': tier.minQty,
          'max_qty': tier.maxQty,
          'price': tier.price,
        }).toList();
        
        // Parse wholesale tiers by variation
        _wholesaleTiersByVariation = product.wholesaleTiersByVariation.map(
          (key, value) => MapEntry(
            key, 
            value.map((tier) => {
              'min_qty': tier.minQty,
              'max_qty': tier.maxQty,
              'price': tier.price,
            }).toList()
          )
        );

        // Select default variation if exists
        if (product.variations.isNotEmpty) {
          final firstVar = product.variations.first;
          _selectedVariationId = firstVar.id;
          _selectedAttributes.clear();
          _selectedAttributes.addAll(firstVar.attributes);
        }
      });
    }
  }

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

  String _getFullImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('uploads/')) {
      return '${AppConstants.baseUrl}/$path';
    }
    return '${AppConstants.baseUrl}/uploads/$path';
  }

  String? _getYoutubeId(String? url) {
    if (url == null) return null;
    final regex = RegExp(r'(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})');
    final match = regex.firstMatch(url);
    return match?.group(1);
  }

  String? _getVimeoId(String? url) {
    if (url == null) return null;
    final regex = RegExp(r'vimeo\.com\/(?:video\/)?(\d+)');
    final match = regex.firstMatch(url);
    return match?.group(1);
  }

  double _getCurrentPrice() {
    final provider = context.read<ProductProvider>();
    final product = provider.productDetail;
    if (product == null) return 0;

    // Check wholesale tiers first
    final wholesalePrice = provider.getPriceForQuantity(_quantity, variationId: _selectedVariationId);
    if (wholesalePrice > 0) return wholesalePrice;

    // Then check variation
    if (_selectedVariationId != null) {
      final variation = provider.getVariationById(_selectedVariationId!);
      if (variation != null) {
        return variation.displayPrice;
      }
    }
    
    // Finally, product price
    return product.displayPrice;
  }

  double _getWholesaleUnitPrice(int quantity) {
    final provider = context.read<ProductProvider>();
    return provider.getPriceForQuantity(quantity, variationId: _selectedVariationId);
  }

  int _getCurrentStock() {
    final provider = context.read<ProductProvider>();
    final product = provider.productDetail;
    if (product == null) return 0;
    
    if (_selectedVariationId != null) {
      final variation = product.variations.firstWhere(
        (v) => v.id == _selectedVariationId,
        orElse: () => product.variations.first,
      );
      return variation.stock;
    }
    return product.stock;
  }

  void _updateQuantity(int newQuantity) {
    final provider = context.read<ProductProvider>();
    final product = provider.productDetail;
    if (product == null) return;

    final maxStock = _getCurrentStock();
    final minOrder = product.minOrderQuantity;
    final maxOrder = product.maxOrderQuantity;

    if (newQuantity >= minOrder && newQuantity <= maxOrder && newQuantity <= maxStock) {
      setState(() => _quantity = newQuantity);
    }
  }

  void _selectVariation(int variationId, Map<String, String> attributes) {
    setState(() {
      _selectedVariationId = variationId;
      _selectedAttributes.clear();
      _selectedAttributes.addAll(attributes);
      _quantity = 1;
    });
  }

  Future<void> _addToCart() async {
    if (!context.read<AuthProvider>().isAuthenticated) {
      _showMessage('Veuillez vous connecter pour ajouter au panier', Colors.orange);
      context.go('/login?redirect=/product/${widget.slug}/${widget.vendorProductId}');
      return;
    }

    final provider = context.read<ProductProvider>();
    final product = provider.productDetail;
    if (product == null) return;

    final maxStock = _getCurrentStock();
    final minOrder = product.minOrderQuantity;
    final maxOrder = product.maxOrderQuantity;

    if (_quantity > maxStock) {
      _showMessage('Seulement $maxStock articles disponibles', Colors.red);
      return;
    }

    if (_quantity < minOrder) {
      _showMessage('Quantité minimum: $minOrder', Colors.orange);
      return;
    }

    if (_quantity > maxOrder) {
      _showMessage('Quantité maximum: $maxOrder', Colors.orange);
      return;
    }

    // Set loading state to true
    setState(() {
      _isAddingToCart = true;
    });

    final cart = context.read<CartProvider>();
    
    final result = await cart.addToCart(
      widget.vendorProductId,
      _quantity,
      variationId: _selectedVariationId,
      selectedVariations: _selectedAttributes.isNotEmpty ? _selectedAttributes : null,
      customNote: _customNote.isNotEmpty ? _customNote : null,
    );

     // Set loading state back to false
    setState(() {
      _isAddingToCart = false;
    });

    if (result) {
      _showMessage('Ajouté au panier', Colors.green);
    } else if (cart.error?.contains('conflict') ?? false) {
      _showConflictDialog();
    } else {
      _showMessage(cart.error ?? 'Erreur lors de l\'ajout au panier', Colors.red);
    }
  }

  void _showConflictDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Conflit de panier'),
        content: const Text('Ce produit a des restrictions de quantité ou de vendeur.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              context.go('/cart');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFD4AF37),
              foregroundColor: Colors.white,
            ),
            child: const Text('Voir le panier'),
          ),
        ],
      ),
    );
  }

  void _showMessage(String message, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _viewAllReviews() {
    final provider = context.read<ProductProvider>();
    final product = provider.productDetail;
    
    if (product == null) return;
    
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ReviewListScreen(
          productId: product.id,
          productName: product.name,
          initialReviews: product.reviews,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      body: Consumer<ProductProvider>(
        builder: (context, provider, child) {
          if (provider.isLoadingDetail && provider.productDetail == null) {
            return const Center(
              child: CircularProgressIndicator(color: Color(0xFFD4AF37)),
            );
          }

          if (provider.detailError != null) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.error_outline, size: 64, color: Colors.grey[400]),
                  const SizedBox(height: 16),
                  Text(
                    'Erreur de chargement',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey[800]),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    provider.detailError!,
                    style: TextStyle(color: Colors.grey[600]),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadProductData,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD4AF37),
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Réessayer'),
                  ),
                ],
              ),
            );
          }

          final product = provider.productDetail;
          if (product == null) {
            return const Center(child: Text('Produit non trouvé'));
          }

          final vendorCurrency = product.currency;
          final images = product.images;
          final descriptionImages = product.descriptionImages;
          final variations = product.variations;
          final hasVariations = product.hasVariations;
          final currentPrice = _getCurrentPrice();
          final originalPrice = product.originalPrice;
          final hasDiscount = product.salePrice != null && currentPrice < originalPrice;
          final stock = _getCurrentStock();
          final inStock = stock > 0;
          final minOrder = product.minOrderQuantity;
          final maxOrder = product.maxOrderQuantity;

          return CustomScrollView(
            slivers: [
              // App Bar with image
              SliverAppBar(
                expandedHeight: 400,
                pinned: true,
                flexibleSpace: FlexibleSpaceBar(
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      if (_mainMediaType == 'video')
                        _buildVideoPlayer(product)
                      else
                        _buildImageCarousel(images),

                      Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: [
                              Colors.transparent,
                              Colors.black.withOpacity(0.3),
                            ],
                          ),
                        ),
                      ),

                      if (product.video != null && _mainMediaType == 'image')
                        Positioned(
                          top: 60,
                          right: 16,
                          child: GestureDetector(
                            onTap: () {
                              setState(() {
                                _mainMediaType = 'video';
                                _isPlayingVideo = true;
                              });
                              // Initialize and play video when tapped
                              if (_videoController != null && !_videoController!.value.isInitialized) {
                                _initializeVideoPlayer(product.video!);
                              } else if (_videoController != null) {
                                _videoController!.play();
                              }
                            },
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                shape: BoxShape.circle,
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.2),
                                    blurRadius: 8,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: const Icon(
                                Icons.play_arrow,
                                color: Color(0xFFD4AF37),
                                size: 28,
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                leading: IconButton(
                  icon: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: const Icon(Icons.arrow_back, color: Colors.black87, size: 20),
                  ),
                  onPressed: () {
                    if (context.canPop()) {
                      context.pop();
                    } else {
                      context.go('/shops');
                    }
                  },
                ),
                actions: [
                  const SizedBox(width: 8),
                  IconButton(
                    key: _shareButtonKey,
                    icon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.1),
                            blurRadius: 4,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: const Icon(Icons.share, color: Colors.black87, size: 20),
                    ),
                    onPressed: () => _shareProduct(product),
                  ),
                  const SizedBox(width: 16),
                ],
              ),

              // Thumbnails
              if (images.isNotEmpty || product.video != null || descriptionImages.isNotEmpty)
                SliverToBoxAdapter(
                  child: Container(
                    height: 80,
                    margin: const EdgeInsets.symmetric(vertical: 8),
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      itemCount: images.length + 
                                (product.video != null ? 1 : 0) + 
                                (descriptionImages.isNotEmpty ? 1 : 0),
                      itemBuilder: (context, index) {
                        int imageIndex = 0;
                        
                        // Main images
                        if (index < images.length) {
                          imageIndex = index;
                          return _buildThumbnail(
                            type: 'image',
                            isSelected: _mainMediaType == 'image' && _currentImageIndex == imageIndex,
                            child: CachedNetworkImage(
                              imageUrl: _getFullImageUrl(images[imageIndex]),
                              fit: BoxFit.cover,
                            ),
                            onTap: () {
                              setState(() {
                                _mainMediaType = 'image';
                                _currentImageIndex = imageIndex;
                              });
                              _carouselController.animateToPage(imageIndex);
                            },
                          );
                        }
                        
                        // Video thumbnail
                        if (product.video != null && index == images.length) {
                          return _buildThumbnail(
                            type: 'video',
                            isSelected: _mainMediaType == 'video',
                            child: product.videoThumbnail != null
                                ? CachedNetworkImage(
                                    imageUrl: _getFullImageUrl(product.videoThumbnail!),
                                    fit: BoxFit.cover,
                                  )
                                : Container(color: Colors.grey[900]),
                            onTap: () {
                              setState(() {
                                _mainMediaType = 'video';
                                _isPlayingVideo = true;
                              });
                            },
                          );
                        }
                        
                        // Description images button
                        if (descriptionImages.isNotEmpty && index == images.length + (product.video != null ? 1 : 0)) {
                          return _buildThumbnail(
                            type: 'gallery',
                            isSelected: false,
                            child: Container(
                              color: Colors.grey[900],
                              child: Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    const Icon(Icons.photo_library, color: Colors.white, size: 20),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${descriptionImages.length}',
                                      style: const TextStyle(color: Colors.white, fontSize: 10),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            onTap: () {
                              _showDescriptionImages(descriptionImages);
                            },
                          );
                        }
                        
                        return const SizedBox.shrink();
                      },
                    ),
                  ),
                ),

              // Product details
              SliverToBoxAdapter(
                child: Container(
                  margin: const EdgeInsets.all(16),
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.1),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildVendorInfo(product),

                      const SizedBox(height: 20),

                      Text(
                        product.name,
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                        ),
                      ),

                      if (product.shortDescription != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Text(
                            product.shortDescription!,
                            style: TextStyle(color: Colors.grey[600], fontSize: 14),
                          ),
                        ),

                      const SizedBox(height: 8),

                      Row(
                        children: [
                          RatingBar(
                            rating: product.rating,
                            size: 18,
                          ),
                          const SizedBox(width: 8),
                          GestureDetector(
                            onTap: _viewAllReviews,
                            child: Text(
                              '(${product.reviewsCount} avis)',
                              style: TextStyle(
                                color: const Color(0xFFD4AF37),
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 20),

                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.grey[50],
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.grey[200]!),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              flex: 3,
                              child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Prix unitaire',
                                  style: TextStyle(fontSize: 12, color: Colors.grey),
                                ),
                                const SizedBox(height: 4),
                                Wrap(
                                  crossAxisAlignment: WrapCrossAlignment.end,
                                  children: [
                                    Text(
                                      '${_getCurrencySymbol(vendorCurrency)} ${currentPrice.toStringAsFixed(2)}',
                                      style: const TextStyle(
                                        fontSize: 28,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFFD4AF37),
                                      ),
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      vendorCurrency,
                                      style: TextStyle(
                                        fontSize: 14,
                                        color: Colors.grey[600],
                                      ),
                                    ),
                                    if (hasDiscount) ...[
                                    const SizedBox(width: 8),
                                    Text(
                                      '${_getCurrencySymbol(vendorCurrency)} ${originalPrice.toStringAsFixed(2)}',
                                      style: TextStyle(
                                        fontSize: 16,
                                        decoration: TextDecoration.lineThrough,
                                        color: Colors.grey[500],
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Container(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 8,
                                        vertical: 4,
                                      ),
                                      decoration: BoxDecoration(
                                        color: Colors.red,
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(
                                        '-${product.discount}%',
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontWeight: FontWeight.bold,
                                          fontSize: 12,
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ],
                            ),
                            ),
                            if (_quantity > 1)
                            Container(
                              width: 120,
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(8),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.grey.withOpacity(0.1),
                                    blurRadius: 4,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Text(
                                    'Total',
                                    style: TextStyle(fontSize: 12, color: Colors.grey),
                                  ),
                                  const SizedBox(height: 4),
                                  FittedBox(
                                    fit: BoxFit.scaleDown,
                                    child: Row(
                                      children: [
                                        Text(
                                          '${_getCurrencySymbol(vendorCurrency)} ${(currentPrice * _quantity).toStringAsFixed(2)}',
                                          style: const TextStyle(
                                            fontSize: 18,
                                            fontWeight: FontWeight.bold,
                                            color: Color(0xFFD4AF37),
                                          ),
                                        ),
                                        const SizedBox(width: 2),
                                        Text(
                                          vendorCurrency,
                                          style: TextStyle(
                                            fontSize: 10,
                                            color: Colors.grey[500],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),

                      if (hasVariations) ...[
                        const SizedBox(height: 24),
                        _buildVariations(variations),
                      ],

                      if (_wholesaleTiers.isNotEmpty) ...[
                        const SizedBox(height: 24),
                        _buildWholesaleTiers(vendorCurrency),
                      ],

                      const SizedBox(height: 24),
                      _buildCustomNote(),

                      const SizedBox(height: 24),
                      _buildQuantityAndActions(inStock, minOrder, maxOrder, product),

                      const SizedBox(height: 24),
                      _buildPaymentInfo(),
                    ],
                  ),
                ),
              ),

              // Description
              if (product.description.isNotEmpty)
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.all(16),
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.1),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Description',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Html(
                          data: product.description,
                          style: {
                            'body': Style(
                              fontSize: FontSize(14),
                              lineHeight: LineHeight(1.6),
                              color: Colors.black87,
                            ),
                            'ul': Style(
                              margin: Margins.only(left: 20),
                            ),
                            'li': Style(
                              margin: Margins.only(bottom: 8),
                            ),
                          },
                        ),
                      ],
                    ),
                  ),
                ),

              // Description images gallery
              if (descriptionImages.isNotEmpty)
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.all(16),
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.1),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Galerie d\'images',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        GridView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 3,
                            crossAxisSpacing: 8,
                            mainAxisSpacing: 8,
                            childAspectRatio: 1,
                          ),
                          itemCount: descriptionImages.length,
                          itemBuilder: (context, index) {
                            return GestureDetector(
                              onTap: () => _showImageFullScreen(_getFullImageUrl(descriptionImages[index])),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: CachedNetworkImage(
                                  imageUrl: _getFullImageUrl(descriptionImages[index]),
                                  fit: BoxFit.cover,
                                  placeholder: (_, __) => Container(color: Colors.grey[200]),
                                ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                ),

              // Specifications
              if (product.specifications != null && product.specifications!.isNotEmpty)
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.all(16),
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.1),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Caractéristiques',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        ..._buildSpecifications(product.specifications!),
                      ],
                    ),
                  ),
                ),

              // Reviews section
              SliverToBoxAdapter(
                child: Container(
                  margin: const EdgeInsets.all(16),
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.1),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Avis',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (product.reviews.isNotEmpty)
                            TextButton(
                              onPressed: _viewAllReviews,
                              child: const Text('Voir tous les avis'),
                            ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _buildReviewForm(product),
                      const SizedBox(height: 16),
                      _buildReviewsList(product),
                    ],
                  ),
                ),
              ),

              const SliverToBoxAdapter(child: SizedBox(height: 20)),

              // Similar Products Section
              if (product.similarProducts.isNotEmpty)
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.all(16),
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.1),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Produits similaires',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 14),
                        SizedBox(
                          height: 200,
                          child: ListView.builder(
                            scrollDirection: Axis.horizontal,
                            itemCount: product.similarProducts.length,
                            itemBuilder: (context, index) {
                              final similar = product.similarProducts[index];
                              return _buildSimilarProductCard(similar);
                            },
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                          
            ],
          );
        },
      ),
    );
  }
  void _shareProduct(ProductDetail product) {
    // Get the position of the share button
    final RenderBox? renderBox = _shareButtonKey.currentContext?.findRenderObject() as RenderBox?;
    Rect? sharePositionOrigin;
    
    if (renderBox != null) {
      final Offset position = renderBox.localToGlobal(Offset.zero);
      final Size size = renderBox.size;
      sharePositionOrigin = Rect.fromLTWH(position.dx, position.dy, size.width, size.height);
    } else {
      // Fallback: use the center of the screen
      final size = MediaQuery.of(context).size;
      sharePositionOrigin = Rect.fromLTWH(size.width / 2, size.height / 2, 0, 0);
    }

    final String productUrl = '${AppConstants.baseUrl}/products/${product.slug}/${widget.vendorProductId}';
    final String shareText = '''
    Découvrez ${product.name} sur MARA BUSINESS!

    💰 Prix: ${_getCurrencySymbol(product.currency)} ${product.displayPrice.toStringAsFixed(2)} ${product.currency}

    🔗 Lien: $productUrl

    Téléchargez l'application MARA BUSINESS pour plus de produits!
    ''';

    Share.share(shareText, subject: product.name, sharePositionOrigin: sharePositionOrigin);
  }

  Widget _buildThumbnail({
    required String type,
    required bool isSelected,
    required Widget child,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 80,
        height: 80,
        margin: const EdgeInsets.only(right: 8),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: isSelected ? const Color(0xFFD4AF37) : Colors.grey[300]!,
            width: 2,
          ),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(6),
          child: Stack(
            fit: StackFit.expand,
            children: [
              child,
              if (type == 'video')
                Container(
                  color: Colors.black54,
                  child: const Icon(
                    Icons.play_arrow,
                    color: Colors.white,
                    size: 30,
                  ),
                ),
              if (type == 'gallery')
                Container(
                  color: Colors.black54,
                  child: const Icon(
                    Icons.photo_library,
                    color: Colors.white,
                    size: 30,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildImageCarousel(List<String> images) {
    if (images.isEmpty) {
      return Container(
        color: Colors.grey[300],
        child: const Center(
          child: Icon(Icons.image, size: 64, color: Colors.grey),
        ),
      );
    }

    return CarouselSlider(
      carouselController: _carouselController, 
      options: CarouselOptions(
        height: 400,
        viewportFraction: 1,
        enableInfiniteScroll: images.length > 1,
        onPageChanged: (index, _) {
          setState(() => _currentImageIndex = index);
        },
      ),
      items: images.map((image) {
        return CachedNetworkImage(
          imageUrl: _getFullImageUrl(image),
          fit: BoxFit.cover,
          width: double.infinity,
          placeholder: (_, __) => Container(color: Colors.grey[300]),
          errorWidget: (_, __, ___) => Container(
            color: Colors.grey[300],
            child: const Icon(Icons.broken_image, color: Colors.grey),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildVideoPlayer(ProductDetail product) {
    final videoUrl = product.video;
    final videoType = product.videoType;
    
    if (videoUrl == null || videoUrl.isEmpty) {
      return Container(color: Colors.black);
    }

    // For YouTube videos, you can use youtube_player_flutter package
    // For now, let's handle local video files and direct video URLs
    if (videoType == null || videoType == 'mp4' || videoUrl.endsWith('.mp4')) {
      // Initialize video controller if not already done
      if (_videoController == null) {
        _initializeVideoPlayer(videoUrl);
      }
      
      if (!_isVideoInitialized) {
        return Container(
          color: Colors.black,
          child: const Center(
            child: CircularProgressIndicator(color: Color(0xFFD4AF37)),
          ),
        );
      }
      
      return Stack(
        children: [
          AspectRatio(
            aspectRatio: _videoController!.value.aspectRatio,
            child: VideoPlayer(_videoController!),
          ),
          // Custom play/pause button overlay
          Center(
            child: IconButton(
              icon: Icon(
                _videoController!.value.isPlaying ? Icons.pause : Icons.play_arrow,
                color: Colors.white,
                size: 50,
              ),
              onPressed: () {
                setState(() {
                  if (_videoController!.value.isPlaying) {
                    _videoController!.pause();
                  } else {
                    _videoController!.play();
                  }
                });
              },
            ),
          ),
          // Positioned close button to return to images
          Positioned(
            top: 10,
            right: 10,
            child: Container(
              decoration: BoxDecoration(
                color: Colors.black54,
                shape: BoxShape.circle,
              ),
              child: IconButton(
                icon: const Icon(Icons.close, color: Colors.white),
                onPressed: () {
                  setState(() {
                    _mainMediaType = 'image';
                    _isPlayingVideo = false;
                  });
                  _videoController?.pause();
                },
              ),
            ),
          ),
        ],
      );
    } 
    else if (videoType == 'youtube') {
      final youtubeId = _getYoutubeId(videoUrl);
      if (youtubeId != null) {
        return Container(
          color: Colors.black,
          child: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.play_circle_filled, color: Colors.white, size: 80),
                const SizedBox(height: 8),
                Text(
                  'YouTube Video: $youtubeId',
                  style: const TextStyle(color: Colors.white),
                ),
                const SizedBox(height: 16),
                Text(
                  'Install youtube_player_flutter package',
                  style: TextStyle(color: Colors.grey[400], fontSize: 12),
                ),
              ],
            ),
          ),
        );
      }
    } 
    else if (videoType == 'vimeo') {
      final vimeoId = _getVimeoId(videoUrl);
      if (vimeoId != null) {
        return Container(
          color: Colors.black,
          child: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.play_circle_filled, color: Colors.white, size: 80),
                const SizedBox(height: 8),
                Text(
                  'Vimeo Video: $vimeoId',
                  style: const TextStyle(color: Colors.white),
                ),
                const SizedBox(height: 16),
                Text(
                  'Install vimeo_player_flutter package',
                  style: TextStyle(color: Colors.grey[400], fontSize: 12),
                ),
              ],
            ),
          ),
        );
      }
    }

    return Container(
      color: Colors.black,
      child: const Center(
        child: Text(
          'Video Player',
          style: TextStyle(color: Colors.white),
        ),
      ),
    );
  }

  void _initializeVideoPlayer(String videoUrl) {
  // Ensure the URL is properly formatted
  String fullUrl = _getFullImageUrl(videoUrl);
  print('🎥 Initializing video from: $fullUrl');
  
  _videoController = VideoPlayerController.networkUrl(
    Uri.parse(fullUrl),
  )..initialize().then((_) {
      setState(() {
        _isVideoInitialized = true;
      });
      if (_isPlayingVideo) {
        _videoController!.play();
      }
      print('✅ Video initialized successfully');
    }).catchError((error) {
      print('❌ Error initializing video: $error');
      setState(() {
        _isVideoInitialized = true; // Still set to true to show error state
      });
      
      // Show error message to user
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Could not load video: ${error.toString()}'),
          backgroundColor: Colors.red,
          duration: const Duration(seconds: 3),
        ),
      );
    });
}
  
  Widget _buildSimilarProductCard(SimilarProduct similar) {
    return GestureDetector(
      onTap: () {
        context.push('/product/${similar.slug}/${similar.vendorProductId}');
      },
      child: Container(
        width: 140,
        margin: const EdgeInsets.only(right: 8),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.grey[200]!),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.05),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(12),
                  ),
                  child: similar.image != null
                      ? CachedNetworkImage(
                          imageUrl: _getFullImageUrl(similar.image),
                          height: 100,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          placeholder: (_, __) => Container(
                            height: 100,
                            color: Colors.grey[200],
                            child: const Center(
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                          ),
                          errorWidget: (_, __, ___) => Container(
                            height: 100,
                            color: Colors.grey[200],
                            child: const Icon(Icons.image, color: Colors.grey, size: 40),
                          ),
                        )
                      : Container(
                          height: 100,
                          color: Colors.grey[200],
                          child: const Icon(Icons.image, color: Colors.grey, size: 40),
                        ),
                ),
                if (!similar.inStock)
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 4,
                        vertical: 1,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.red,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: const Text(
                        'Rupture',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 7,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            
            // Content
            Padding(
              padding: const EdgeInsets.all(6.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Name
                  Text(
                    similar.name,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  
                  const SizedBox(height: 3),
                  
                  // Price
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          similar.priceDisplay,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFFD4AF37),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(width: 2),
                      Text(
                        similar.currency,
                        style: TextStyle(
                          fontSize: 8,
                          color: Colors.grey[500],
                        ),
                      ),
                    ],
                  ),
                  
                  // Variation info
                  if (similar.hasVariations)
                    Padding(
                      padding: const EdgeInsets.only(top: 1),
                      child: Text(
                        '${similar.variationsCount} variantes',
                        style: TextStyle(
                          fontSize: 10,
                          color: Colors.grey[500],
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  
                  const SizedBox(height: 1),
                  
                  // Vendor name
                  Row(
                    children: [
                      const Icon(
                        Icons.store,
                        size: 10,
                        color: Color(0xFFD4AF37),
                      ),
                      const SizedBox(width: 1),
                      Expanded(
                        child: Text(
                          similar.vendorName,
                          style: TextStyle(
                            fontSize: 10,
                            color: Colors.grey[600],
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
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

  void _showDescriptionImages(List<String> images) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        child: Container(
          height: 400,
          padding: const EdgeInsets.all(8),
          child: GridView.builder(
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
            ),
            itemCount: images.length,
            itemBuilder: (context, index) {
              return GestureDetector(
                onTap: () => _showImageFullScreen(_getFullImageUrl(images[index])),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: CachedNetworkImage(
                    imageUrl: _getFullImageUrl(images[index]),
                    fit: BoxFit.cover,
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  void _showImageFullScreen(String imageUrl) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        backgroundColor: Colors.transparent,
        child: Stack(
          children: [
            InteractiveViewer(
              child: CachedNetworkImage(imageUrl: imageUrl),
            ),
            Positioned(
              top: 8,
              right: 8,
              child: IconButton(
                icon: const Icon(Icons.close, color: Colors.white),
                onPressed: () => Navigator.pop(context),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildVendorInfo(ProductDetail product) {
    return GestureDetector(
      onTap: () => context.go('/vendor/${product.vendorSlug}'),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.grey[50],
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey[200]!),
        ),
        child: Row(
          children: [
            CircleAvatar(
              radius: 20,
              backgroundColor: Colors.grey[200],
              backgroundImage: product.vendorLogo != null
                  ? CachedNetworkImageProvider(_getFullImageUrl(product.vendorLogo))
                  : null,
              child: product.vendorLogo == null
                  ? Text(
                      product.vendorName[0],
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    )
                  : null,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.vendorName,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  RatingBar(
                    rating: product.vendorRating,
                    size: 14,
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: Color(0xFFD4AF37)),
          ],
        ),
      ),
    );
  }

  Widget _buildVariations(List<Variation> variations) {
    // Build attribute map
    final Map<String, Set<String>> attributeValues = {};
    for (var variation in variations) {
      variation.attributes.forEach((key, value) {
        attributeValues.putIfAbsent(key, () => {}).add(value);
      });
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: attributeValues.entries.map((entry) {
        final attribute = entry.key;
        final values = entry.value.toList();
        
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              attribute,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: values.map((value) {
                final isSelected = _selectedAttributes[attribute] == value;
                return FilterChip(
                  label: Text(value),
                  selected: isSelected,
                  onSelected: (selected) {
                    if (selected) {
                      final newAttributes = Map<String, String>.from(_selectedAttributes);
                      newAttributes[attribute] = value;
                      
                      // Find matching variation
                      for (var variation in variations) {
                        bool match = newAttributes.entries.every(
                          (e) => variation.attributes[e.key] == e.value
                        );
                        if (match) {
                          _selectVariation(
                            variation.id,
                            newAttributes,
                          );
                          break;
                        }
                      }
                    } else {
                      final newAttributes = Map<String, String>.from(_selectedAttributes);
                      newAttributes.remove(attribute);
                      
                      if (newAttributes.isEmpty) {
                        setState(() {
                          _selectedVariationId = null;
                          _selectedAttributes.clear();
                        });
                      } else {
                        // Find variation with remaining attributes
                        for (var variation in variations) {
                          bool match = newAttributes.entries.every(
                            (e) => variation.attributes[e.key] == e.value
                          );
                          if (match) {
                            _selectVariation(
                              variation.id,
                              newAttributes,
                            );
                            break;
                          }
                        }
                      }
                    }
                  },
                  selectedColor: const Color(0xFFD4AF37).withOpacity(0.2),
                  checkmarkColor: const Color(0xFFD4AF37),
                );
              }).toList(),
            ),
            const SizedBox(height: 16),
          ],
        );
      }).toList(),
    );
  }

  Widget _buildWholesaleTiers(String currency) {
    final tiers = _selectedVariationId != null && _wholesaleTiersByVariation.containsKey(_selectedVariationId)
        ? _wholesaleTiersByVariation[_selectedVariationId]!
        : _wholesaleTiers;

    if (tiers.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Tarifs de gros',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.grey[200]!),
          ),
          child: Column(
            children: tiers.map((tier) {
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      '${tier['min_qty']}${tier['max_qty'] != null ? ' - ${tier['max_qty']}' : '+'} pièces',
                      style: const TextStyle(fontWeight: FontWeight.w500),
                    ),
                    Row(
                      children: [
                        Text(
                          '${_getCurrencySymbol(currency)} ${(tier['price'] as double).toStringAsFixed(2)}',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Color(0xFFD4AF37),
                          ),
                        ),
                        const SizedBox(width: 2),
                        Text(
                          currency,
                          style: TextStyle(
                            fontSize: 10,
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _buildCustomNote() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Note pour le vendeur',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 8),
        TextField(
          maxLines: 3,
          decoration: InputDecoration(
            hintText: 'Instructions spéciales, emballage cadeau, message...',
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            filled: true,
            fillColor: Colors.grey[50],
          ),
          onChanged: (value) => _customNote = value,
        ),
      ],
    );
  }

  Widget _buildQuantityAndActions(bool inStock, int minOrder, int maxOrder, ProductDetail product) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.grey[50],
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(
            children: [
              const Text(
                'Quantité:',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
              ),
              const SizedBox(width: 16),
              Container(
                width: 120,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.grey[300]!),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.remove, size: 18),
                      onPressed: inStock && _quantity > minOrder
                          ? () => _updateQuantity(_quantity - 1)
                          : null,
                      color: Colors.grey[700],
                      constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                      padding: EdgeInsets.zero,
                    ),
                    Text(
                      '$_quantity',
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    IconButton(
                      icon: const Icon(Icons.add, size: 18),
                      onPressed: inStock && _quantity < maxOrder && _quantity < _getCurrentStock()
                          ? () => _updateQuantity(_quantity + 1)
                          : null,
                      color: Colors.grey[700],
                      constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                      padding: EdgeInsets.zero,
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      inStock ? 'En stock' : 'Rupture de stock',
                      style: TextStyle(
                        color: inStock ? Colors.green : Colors.red,
                        fontWeight: FontWeight.w500,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (inStock && (minOrder > 1 || maxOrder < product.stock))
                      Text(
                        'Min: $minOrder, Max: ${maxOrder < product.stock ? maxOrder : product.stock}',
                        style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                        overflow: TextOverflow.ellipsis,
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        Row(
          children: [
            Expanded(
              child: ElevatedButton(
                onPressed: inStock ? _addToCart : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  disabledBackgroundColor: Colors.grey[300],
                ),
                child: _isAddingToCart
                    ? Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Text(
                            'Adding.',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                        ],
                      )
                    : const Text(
                        'Ajouter au panier',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: OutlinedButton(
                onPressed: inStock
                    ? () async {
                        await _addToCart();
                        context.go('/cart');
                      }
                    : null,
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFFD4AF37),
                  side: const BorderSide(color: Color(0xFFD4AF37)),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Acheter',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildPaymentInfo() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12),
      decoration: BoxDecoration(
        border: Border(
          top: BorderSide(color: Colors.grey[200]!),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Row(
            children: [
              Icon(Icons.lock_outline, size: 16, color: Colors.grey[600]),
              const SizedBox(width: 4),
              Text(
                'Paiement sécurisé',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
            ],
          ),
          const SizedBox(width: 24),
          Row(
            children: [
              Icon(Icons.local_shipping_outlined, size: 16, color: Colors.grey[600]),
              const SizedBox(width: 4),
              Text(
                'Livraison rapide',
                style: TextStyle(fontSize: 12, color: Colors.grey[600]),
              ),
            ],
          ),
        ],
      ),
    );
  }

  List<Widget> _buildSpecifications(Map<String, dynamic> specs) {
    return specs.entries.map((entry) {
      return Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 120,
              child: Text(
                entry.key,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.grey,
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                entry.value.toString(),
                style: const TextStyle(fontSize: 14),
              ),
            ),
          ],
        ),
      );
    }).toList();
  }

  Widget _buildReviewForm(ProductDetail product) {
  final authProvider = context.watch<AuthProvider>();
  final isLoggedIn = authProvider.isAuthenticated;
  final hasReviewed = product.userReview != null;
  // In _buildReviewForm, add this at the beginning:
  print('🔍 Building review form - isLoggedIn: $isLoggedIn');
  print('🔍 userReview: ${product.userReview}');
  print('🔍 hasReviewed: $hasReviewed');
  print('🔍 isEditingReview: $_isEditingReview');

  if (!isLoggedIn) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Row(
        children: [
          const Icon(Icons.lock_outline, color: Colors.grey),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'Connectez-vous pour laisser un avis',
              style: TextStyle(color: Colors.grey[600]),
            ),
          ),
          TextButton(
            onPressed: () => context.go('/login?redirect=/product/${widget.slug}/${widget.vendorProductId}'),
            child: const Text('Se connecter'),
          ),
        ],
      ),
    );
  }

  if (hasReviewed && !_isEditingReview) {
    final userReview = product.userReview!;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.green[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.green[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Votre avis',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              Row(
                children: [
                  TextButton(
                    onPressed: () {
                      setState(() {
                        _isEditingReview = true;
                        _rating = userReview['rating'] ?? 0;
                        _commentController.text = userReview['comment'] ?? '';
                      });
                    },
                    child: const Text('Modifier'),
                  ),
                  const SizedBox(width: 8),
                  TextButton(
                    onPressed: () {
                      _showDeleteConfirmation(product);
                    },
                    style: TextButton.styleFrom(
                      foregroundColor: Colors.red,
                    ),
                    child: const Text('Supprimer'),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: List.generate(5, (index) {
              return Icon(
                index < (userReview['rating'] ?? 0) ? Icons.star : Icons.star_border,
                color: Colors.amber,
                size: 16,
              );
            }),
          ),
          const SizedBox(height: 8),
          Text(userReview['comment'] ?? ''),
          if (userReview['is_approved'] == false)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.orange[50],
                  borderRadius: BorderRadius.circular(4),
                  border: Border.all(color: Colors.orange[200]!),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.hourglass_empty, color: Colors.orange[700], size: 14),
                    const SizedBox(width: 4),
                    Text(
                      'En attente d\'approbation',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.orange[700],
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  // Review form for new review or editing
  return Container(
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.grey[50],
      borderRadius: BorderRadius.circular(12),
      border: Border.all(color: Colors.grey[200]!),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          _isEditingReview ? 'Modifier votre avis' : 'Donner votre avis',
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 16),

        // Star Rating
        Row(
          children: List.generate(5, (index) {
            return IconButton(
              icon: Icon(
                index < _rating ? Icons.star : Icons.star_border,
                color: Colors.amber,
                size: 28,
              ),
              onPressed: () {
                setState(() => _rating = index + 1);
              },
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
            );
          }),
        ),

        const SizedBox(height: 12),

        // Comment Field
        TextField(
          controller: _commentController,
          maxLines: 3,
          decoration: InputDecoration(
            hintText: 'Partagez votre expérience avec ce produit...',
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            filled: true,
            fillColor: Colors.white,
            errorText: _commentController.text.isNotEmpty && _commentController.text.length < 10
                ? 'Minimum 10 caractères'
                : null,
          ),
        ),

        const SizedBox(height: 16),

        // Submit Button
        Row(
          children: [
            Expanded(
              child: ElevatedButton(
                onPressed: _submitReview,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: Text(_isEditingReview ? 'Mettre à jour' : 'Soumettre'),
              ),
            ),
            if (_isEditingReview) ...[
              const SizedBox(width: 12),
              Expanded(
                child: TextButton(
                  onPressed: () {
                    setState(() {
                      _isEditingReview = false;
                      _rating = 0;
                      _commentController.clear();
                    });
                  },
                  child: const Text('Annuler'),
                ),
              ),
            ],
          ],
        ),
      ],
    ),
  );
}

  // Update the delete confirmation method
  void _showDeleteConfirmation(ProductDetail product) {
  showDialog(
    context: context,
    builder: (BuildContext context) {
      return AlertDialog(
        title: const Text('Supprimer l\'avis'),
        content: const Text('Êtes-vous sûr de vouloir supprimer votre avis ? Cette action est irréversible.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () async {
              Navigator.of(context).pop();
              final provider = context.read<ProductProvider>();
              final success = await provider.deleteReview(
                reviewId: product.userReview!['id'],
                vendorProductId: widget.vendorProductId,
              );
              if (success) {
                _showMessage('Avis supprimé avec succès', Colors.green);
                // Refresh the product detail to update UI
                await provider.loadProductDetail(widget.slug, widget.vendorProductId);
              } else {
                _showMessage(provider.detailError ?? 'Erreur lors de la suppression', Colors.red);
              }
            },
            style: TextButton.styleFrom(
              foregroundColor: Colors.red,
            ),
            child: const Text('Supprimer'),
          ),
        ],
      );
    },
  );
}
  
  Widget _buildReviewsList(ProductDetail product) {
    if (product.reviews.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: Text(
            'Aucun avis pour le moment',
            style: TextStyle(color: Colors.grey),
          ),
        ),
      );
    }

    return Column(
      children: [
        ...product.reviews.take(3).map((review) => ReviewCard(review: review)),
        if (product.reviews.length > 3)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: TextButton(
              onPressed: _viewAllReviews,
              child: Text(
                'Voir tous les ${product.reviews.length} avis',
                style: const TextStyle(color: Color(0xFFD4AF37)),
              ),
            ),
          ),
      ],
    );
  }

  Future<void> _submitReview() async {
  if (_rating == 0) {
    _showMessage('Veuillez sélectionner une note', Colors.orange);
    return;
  }
  if (_commentController.text.length < 10) {
    _showMessage('Le commentaire doit contenir au moins 10 caractères', Colors.orange);
    return;
  }

  // After login, in your redirect:
  Future<void> afterLogin() async {
    await Provider.of<ProductProvider>(context, listen: false)
        .loadProductDetail(widget.slug, widget.vendorProductId);
  }

  final provider = context.read<ProductProvider>();
  final product = provider.productDetail;
  if (product == null) return;

  bool success;
  
  if (_isEditingReview && product.userReview != null) {
    // Update existing review
    success = await provider.updateReview(
      reviewId: product.userReview!['id'],
      rating: _rating,
      comment: _commentController.text.trim(),
      vendorProductId: widget.vendorProductId,
    );
  } else {
    // Submit new review
    success = await provider.submitReview(
      vendorProductId: widget.vendorProductId,
      rating: _rating,
      comment: _commentController.text.trim(),
    );
  }

  if (success) {
    _showMessage(
      _isEditingReview 
          ? 'Avis mis à jour avec succès!' 
          : 'Avis soumis avec succès! En attente d\'approbation.',
      Colors.green
    );
    setState(() {
      _isEditingReview = false;
      _rating = 0;
      _commentController.clear();
    });
    // Refresh the product detail to update UI
    await provider.loadProductDetail(widget.slug, widget.vendorProductId);
  } else {
    _showMessage(
      provider.detailError ?? 'Erreur lors de la soumission de l\'avis',
      Colors.red
    );
  }
}
}