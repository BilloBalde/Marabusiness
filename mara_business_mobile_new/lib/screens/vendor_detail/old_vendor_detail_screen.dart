import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/wishlist_provider.dart';
import '../../core/providers/vendor_detail_provider.dart';
import '../../core/providers/home_provider.dart';
import '../../core/constants/app_constants.dart';
import '../../core/models/vendor_models.dart';
import '../../core/models/home_models.dart';
import 'package:go_router/go_router.dart';

class VendorDetailScreen extends StatefulWidget {
  final String slug;
  const VendorDetailScreen({super.key, required this.slug});

  @override
  State<VendorDetailScreen> createState() => _VendorDetailScreenState();
}

class _VendorDetailScreenState extends State<VendorDetailScreen> {
  String _sortBy = 'popular';
  int? _selectedCategoryId;
  final TextEditingController _minPriceController = TextEditingController();
  final TextEditingController _maxPriceController = TextEditingController();
  
  // Review form
  int _rating = 0;
  final TextEditingController _commentController = TextEditingController();
  bool _isSubmittingReview = false;

  // Delete modal
  bool _showDeleteModal = false;
  bool _isEditingReview = false;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _minPriceController.dispose();
    _maxPriceController.dispose();
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    await context.read<VendorDetailProvider>().loadVendorData(widget.slug);
  }

  Future<void> _applyFilters() async {
    final minPrice = double.tryParse(_minPriceController.text);
    final maxPrice = double.tryParse(_maxPriceController.text);
    
    await context.read<VendorDetailProvider>().applyFilters(
      sortBy: _sortBy,
      categoryId: _selectedCategoryId,
      minPrice: minPrice,
      maxPrice: maxPrice,
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

    setState(() => _isSubmittingReview = true);
    
    final success = await context.read<VendorDetailProvider>().submitReview(
      _rating,
      _commentController.text.trim(),
    );

    setState(() => _isSubmittingReview = false);

    if (success) {
      _showMessage('Avis soumis avec succès! En attente d\'approbation.', Colors.green);
      setState(() {
        _rating = 0;
        _commentController.clear();
        _isEditingReview = false;
      });
    } else {
      _showMessage('Erreur lors de la soumission de l\'avis', Colors.red);
    }
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

  String _getCurrencySymbol(String currencyCode) {
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

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final homeProvider = context.watch<HomeProvider>();
    final isLoggedIn = authProvider.isAuthenticated;

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () {
            if (context.canPop()) {
              context.pop();
            } else {
              // If there's nothing to pop, go to a default screen
              context.go('/shops'); // or '/vendors' or '/'
            }
          },
        ),
        title: const Text(
          'Boutique',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
      ),
      body: Consumer<VendorDetailProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading) {
            return const Center(
              child: CircularProgressIndicator(
                color: Color(0xFFD4AF37),
              ),
            );
          }

          if (provider.error != null) {
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
                    provider.error!,
                    style: TextStyle(color: Colors.grey[600]),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _loadData,
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

          final vendor = provider.vendor;
          if (vendor == null) {
            return const Center(child: Text('Vendeur non trouvé'));
          }

          return CustomScrollView(
            slivers: [
              // Vendor Header
              SliverToBoxAdapter(
                child: _buildVendorHeader(vendor, provider, isLoggedIn),
              ),

              const SliverToBoxAdapter(child: SizedBox(height: 16)),

              // Filters
              SliverToBoxAdapter(
                child: _buildFilters(provider, homeProvider),
              ),

              const SliverToBoxAdapter(child: SizedBox(height: 16)),

              // Products Grid
              if (provider.products.isNotEmpty)
                SliverPadding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  sliver: SliverGrid(
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 0.6, // Changed from 0.8 to 0.6 to give more height
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                    ),
                    delegate: SliverChildBuilderDelegate(
                      (context, index) {
                        if (index >= provider.products.length) {
                          return null;
                        }
                        final product = provider.products[index];
                        return _buildProductCard(product, vendor);
                      },
                      childCount: provider.products.length,
                    ),
                  ),
                )
              else
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.all(16),
                    padding: const EdgeInsets.all(32),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Column(
                      children: [
                        Icon(Icons.inventory, size: 48, color: Colors.grey),
                        SizedBox(height: 16),
                        Text(
                          'Aucun produit disponible pour le moment.',
                          style: TextStyle(color: Colors.grey),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ),

              // Load more products indicator
              if (provider.hasMoreProducts)
                SliverToBoxAdapter(
                  child: Container(
                    margin: const EdgeInsets.all(16),
                    child: Center(
                      child: provider.isLoadingMoreProducts
                          ? const CircularProgressIndicator(color: Color(0xFFD4AF37))
                          : TextButton(
                              onPressed: () => provider.loadMoreProducts(),
                              child: const Text('Charger plus de produits'),
                            ),
                    ),
                  ),
                ),

              const SliverToBoxAdapter(child: SizedBox(height: 16)),

              // Reviews Section
              SliverToBoxAdapter(
                child: _buildReviewsSection(provider, isLoggedIn),
              ),

              const SliverToBoxAdapter(child: SizedBox(height: 20)),
            ],
          );
        },
      ),
    );
  }

  Widget _buildVendorHeader(ExtendedVendor vendor, VendorDetailProvider provider, bool isLoggedIn) {
    return Container(
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
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Logo
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: Colors.grey[100],
              shape: BoxShape.circle,
              border: Border.all(color: const Color(0xFFD4AF37).withOpacity(0.3), width: 2),
              image: vendor.logo != null
                  ? DecorationImage(
                      image: CachedNetworkImageProvider(
                        vendor.logo!.startsWith('http')
                            ? vendor.logo!
                            : '${AppConstants.baseUrl}/uploads/${vendor.logo}',
                      ),
                      fit: BoxFit.cover,
                    )
                  : null,
            ),
            child: vendor.logo == null
                ? Center(
                    child: Text(
                      vendor.storeName.isNotEmpty ? vendor.storeName[0].toUpperCase() : '?',
                      style: const TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFD4AF37),
                      ),
                    ),
                  )
                : null,
          ),
          const SizedBox(width: 16),

          // Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        vendor.storeName,
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    if (vendor.isVerified == true)
                      const Icon(
                        Icons.verified,
                        color: Colors.blue,
                        size: 20,
                      ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  vendor.description ?? 'Boutique Officielle',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
                const SizedBox(height: 12),

                // Stats Row
                Row(
                  children: [
                    // Rating
                    Row(
                      children: [
                        _buildRatingStars(vendor.rating ?? 0),
                        const SizedBox(width: 4),
                        Text(
                          vendor.rating?.toStringAsFixed(1) ?? '0.0',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    const SizedBox(width: 16),

                    // Followers
                    Row(
                      children: [
                        Icon(Icons.people_outline, size: 16, color: Colors.grey[600]),
                        const SizedBox(width: 4),
                        Text('${vendor.followersCount ?? 0}'),
                      ],
                    ),
                    const SizedBox(width: 16),

                    // Products
                    Row(
                      children: [
                        Icon(Icons.inventory_2_outlined, size: 16, color: Colors.grey[600]),
                        const SizedBox(width: 4),
                        Text('${vendor.productsCount ?? 0}'),
                      ],
                    ),
                  ],
                ),

                const SizedBox(height: 12),

                // Follow Button
                if (isLoggedIn)
                  ElevatedButton(
                    onPressed: () => provider.toggleFollow(),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: provider.isFollowing ? Colors.grey[200] : const Color(0xFFD4AF37),
                      foregroundColor: provider.isFollowing ? Colors.black87 : Colors.white,
                      minimumSize: const Size(double.infinity, 40),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                    ),
                    child: Text(provider.isFollowing ? 'Abonné' : 'Suivre'),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilters(VendorDetailProvider provider, HomeProvider homeProvider) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                flex: 5,
                child: DropdownButtonFormField<String>(
                  initialValue: _sortBy,
                  decoration: const InputDecoration(
                    labelText: 'Trier par',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    isDense: true,
                  ),
                  items: const [
                    DropdownMenuItem(value: 'popular', child: Text('Populaire', style: TextStyle(fontSize: 12))),
                    DropdownMenuItem(value: 'newest', child: Text('Plus récents', style: TextStyle(fontSize: 12))),
                    DropdownMenuItem(value: 'price_asc', child: Text('Prix ↑', style: TextStyle(fontSize: 12))),
                    DropdownMenuItem(value: 'price_desc', child: Text('Prix ↓', style: TextStyle(fontSize: 12))),
                  ],
                  onChanged: (value) {
                    setState(() => _sortBy = value!);
                  },
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                flex: 5,
                child: DropdownButtonFormField<int?>(
                  initialValue: _selectedCategoryId,
                  decoration: const InputDecoration(
                    labelText: 'Catégorie',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    isDense: true,
                  ),
                  items: [
                    const DropdownMenuItem<int?>(
                      value: null,
                      child: Text('Toutes'),
                    ),
                    ...homeProvider.categories.map((category) {
                      return DropdownMenuItem<int?>(
                        value: category.id,
                        child: Text(category.name, style: const TextStyle(fontSize: 12)),
                      );
                    }),
                  ],
                  onChanged: (value) {
                    setState(() => _selectedCategoryId = value);
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                flex: 3,
                child: TextField(
                  controller: _minPriceController,
                  decoration: const InputDecoration(
                    labelText: 'Min',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                    isDense: true,
                  ),
                  keyboardType: TextInputType.number,
                  style: const TextStyle(fontSize: 12),
                ),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 8),
                child: Text('-', style: TextStyle(fontSize: 12)),
              ),
              Expanded(
                flex: 3,
                child: TextField(
                  controller: _maxPriceController,
                  decoration: const InputDecoration(
                    labelText: 'Max',
                    border: OutlineInputBorder(),
                    contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                    isDense: true,
                  ),
                  keyboardType: TextInputType.number,
                  style: const TextStyle(fontSize: 12),
                ),
              ),
              const SizedBox(width: 8),
              ElevatedButton(
                onPressed: _applyFilters,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  minimumSize: const Size(60, 36),
                  textStyle: const TextStyle(fontSize: 11),
                ),
                child: const Text('OK'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildProductCard(VendorProduct product, ExtendedVendor vendor) {
    final hasVariations = product.hasVariations;
    final inStock = product.stock > 0;
    final hasDiscount = product.salePrice != null && product.salePrice! < product.price;
    
    // Format price display based on variations
    String priceDisplay = product.formattedPrice;

    return GestureDetector(
      onTap: () {
        context.go('/product/${product.slug}/${product.vendorProductId}');
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
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
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image - Fixed height
            ClipRRect(
              borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
              child: product.imageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: product.imageUrl!.startsWith('http')
                          ? product.imageUrl!
                          : '${AppConstants.baseUrl}/uploads/${product.imageUrl}',
                      height: 140,
                      width: double.infinity,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => Container(
                        height: 140,
                        color: Colors.grey[200],
                        child: const Center(
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      ),
                      errorWidget: (_, __, ___) => Container(
                        height: 140,
                        color: Colors.grey[200],
                        child: const Icon(Icons.image, color: Colors.grey),
                      ),
                    )
                  : Container(
                      height: 140,
                      color: Colors.grey[200],
                      child: const Icon(Icons.image, color: Colors.grey, size: 40),
                    ),
            ),

            // Details - Fixed height with padding
            Padding(
              padding: const EdgeInsets.all(6.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product name
                  Text(
                    product.name,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  
                  const SizedBox(height: 2),

                  // Price
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          priceDisplay,
                          style: const TextStyle(
                            color: Color(0xFFD4AF37),
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (hasDiscount)
                        Padding(
                          padding: const EdgeInsets.only(left: 4),
                          child: Text(
                            '${_getCurrencySymbol(product.currency)} ${product.price.toStringAsFixed(2)}',
                            style: TextStyle(
                              fontSize: 8,
                              decoration: TextDecoration.lineThrough,
                              color: Colors.grey[500],
                            ),
                          ),
                        ),
                    ],
                  ),

                  // Variation count if has variations
                  if (hasVariations)
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Text(
                        'Plusieurs variantes',
                        style: TextStyle(
                          fontSize: 8,
                          color: Colors.grey[600],
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),

                  const SizedBox(height: 4),

                  // Stock status
                  Text(
                    inStock ? 'En stock' : 'Rupture de stock',
                    style: TextStyle(
                      fontSize: 9,
                      color: inStock ? Colors.green : Colors.red,
                      fontWeight: FontWeight.w500,
                    ),
                    maxLines: 1,
                  ),

                  const SizedBox(height: 4),

                  // Add to Wishlist button
                  SizedBox(
                    width: double.infinity,
                    child: inStock
                        ? ElevatedButton(
                            onPressed: () {
                              context.read<WishlistProvider>().addToWishlist(product.vendorProductId);
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Ajouté aux favoris'),
                                  backgroundColor: Colors.green,
                                  behavior: SnackBarBehavior.floating,
                                ),
                              );
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFFD4AF37),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 4),
                              minimumSize: const Size(double.infinity, 26),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(6),
                              ),
                              textStyle: const TextStyle(fontSize: 9),
                            ),
                            child: const Text(
                              'Ajouter aux favoris',
                              style: TextStyle(fontSize: 11),
                            ),
                          )
                        : Container(
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.grey[200],
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Center(
                              child: Text(
                                'Rupture de stock',
                                style: TextStyle(
                                  fontSize: 9,
                                  color: Colors.grey,
                                ),
                              ),
                            ),
                          ),
                  ),

                  //const SizedBox(height: 2),

                  // View details link
                  Center(
                    child: TextButton(
                      onPressed: () {
                        context.go('/product/${product.slug}/${product.vendorProductId}');
                      },
                      style: TextButton.styleFrom(
                        padding: EdgeInsets.zero,
                        minimumSize: const Size(40, 20),
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      child: const Text(
                        'Détails →',
                        style: TextStyle(
                          color: Color(0xFFD4AF37),
                          fontSize: 8,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildReviewsSection(VendorDetailProvider provider, bool isLoggedIn) {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Avis',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),

          // Review Form (if logged in)
          if (isLoggedIn) _buildReviewForm(provider),

          // Reviews List
          if (provider.reviews.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(
                child: Text(
                  'Aucun avis pour le moment.',
                  style: TextStyle(color: Colors.grey),
                ),
              ),
            )
          else
            ...provider.reviews.map((review) => _buildReviewCard(review)),

          // Load more button
          if (provider.hasMoreReviews)
            Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Center(
                child: provider.isLoadingMoreReviews
                    ? const CircularProgressIndicator(color: Color(0xFFD4AF37))
                    : TextButton(
                        onPressed: () => provider.loadMoreReviews(),
                        child: const Text('Charger plus d\'avis'),
                      ),
              ),
            ),

          // Delete modal
          if (_showDeleteModal) _buildDeleteModal(provider),
        ],
      ),
    );
  }

  Widget _buildReviewForm(VendorDetailProvider provider) {
    final userReview = provider.userReview;

    if (userReview != null && !_isEditingReview) {
      return Container(
        margin: const EdgeInsets.only(bottom: 16),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.grey[50],
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Colors.grey[200]!),
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
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                  ),
                ),
                Row(
                  children: [
                    TextButton(
                      onPressed: () {
                        setState(() {
                          _rating = userReview.rating;
                          _commentController.text = userReview.comment;
                          _isEditingReview = true;
                        });
                      },
                      child: const Text('Modifier'),
                    ),
                    TextButton(
                      onPressed: () {
                        setState(() => _showDeleteModal = true);
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
            _buildRatingStars(userReview.rating.toDouble()),
            const SizedBox(height: 8),
            Text(userReview.comment),
            if (!userReview.isApproved)
              const Padding(
                padding: EdgeInsets.only(top: 8),
                child: Text(
                  'En attente d\'approbation',
                  style: TextStyle(
                    color: Colors.orange,
                    fontSize: 12,
                    fontStyle: FontStyle.italic,
                  ),
                ),
              ),
          ],
        ),
      );
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[50],
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            userReview != null ? 'Modifier votre avis' : 'Donner votre avis',
            style: const TextStyle(
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 12),

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
                  setState(() {
                    _rating = index + 1;
                  });
                },
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              );
            }),
          ),

          const SizedBox(height: 8),

          // Comment Field
          TextField(
            controller: _commentController,
            decoration: InputDecoration(
              hintText: 'Partagez votre expérience avec ce vendeur...',
              border: const OutlineInputBorder(),
              contentPadding: const EdgeInsets.all(12),
              errorText: _commentController.text.isNotEmpty && _commentController.text.length < 10
                  ? 'Minimum 10 caractères'
                  : null,
            ),
            maxLines: 3,
          ),

          const SizedBox(height: 12),

          // Submit Button
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: _isSubmittingReview ? null : _submitReview,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFD4AF37),
                    foregroundColor: Colors.white,
                  ),
                  child: _isSubmittingReview
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      : Text(userReview != null ? 'Mettre à jour' : 'Soumettre'),
                ),
              ),
              if (userReview != null)
                const SizedBox(width: 8),
              if (userReview != null)
                Expanded(
                  child: TextButton(
                    onPressed: () {
                      setState(() {
                        _rating = 0;
                        _commentController.clear();
                        _isEditingReview = false;
                      });
                    },
                    child: const Text('Annuler'),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildReviewCard(VendorReview review) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey[200]!),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              // User Avatar
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: Colors.grey[200],
                  shape: BoxShape.circle,
                  image: review.userAvatar != null
                      ? DecorationImage(
                          image: CachedNetworkImageProvider(review.userAvatar!),
                          fit: BoxFit.cover,
                        )
                      : null,
                ),
                child: review.userAvatar == null
                    ? Center(
                        child: Text(
                          review.userName.isNotEmpty ? review.userName[0].toUpperCase() : '?',
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                      )
                    : null,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      review.userName,
                      style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                    ),
                    Text(
                      review.createdAtHuman,
                      style: TextStyle(
                        fontSize: 10,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              _buildRatingStars(review.rating.toDouble()),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            review.comment,
            style: const TextStyle(fontSize: 13),
          ),
          if (!review.isApproved)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: Text(
                'En attente d\'approbation',
                style: TextStyle(
                  color: Colors.orange,
                  fontSize: 11,
                  fontStyle: FontStyle.italic,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildDeleteModal(VendorDetailProvider provider) {
    return Container(
      color: Colors.black54,
      child: Center(
        child: Container(
          margin: const EdgeInsets.all(20),
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.red[50],
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.delete_outline,
                  color: Colors.red,
                  size: 32,
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Supprimer l\'avis',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Êtes-vous sûr de vouloir supprimer votre avis ? Cette action est irréversible.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.grey),
              ),
              const SizedBox(height: 20),
              Row(
                children: [
                  Expanded(
                    child: TextButton(
                      onPressed: () {
                        setState(() => _showDeleteModal = false);
                      },
                      child: const Text('Annuler'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () async {
                        setState(() => _showDeleteModal = false);
                        final success = await provider.deleteReview();
                        if (success) {
                          _showMessage('Avis supprimé avec succès', Colors.green);
                        } else {
                          _showMessage('Erreur lors de la suppression', Colors.red);
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red,
                        foregroundColor: Colors.white,
                      ),
                      child: const Text('Supprimer'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRatingStars(double rating) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (index) {
        if (index < rating.floor()) {
          return const Icon(Icons.star, color: Colors.amber, size: 14);
        } else if (index == rating.floor() && rating - index > 0.5) {
          return const Icon(Icons.star_half, color: Colors.amber, size: 14);
        } else {
          return const Icon(Icons.star_border, color: Colors.amber, size: 14);
        }
      }),
    );
  }
}