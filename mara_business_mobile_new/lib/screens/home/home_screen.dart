import '../../utils/image_url.dart';
import '../../utils/app_logger.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:carousel_slider/carousel_slider.dart';
import '../../core/models/home_models.dart';
import '../../core/models/product_unified.dart';
import '../../core/providers/home_provider.dart';
import '../../core/providers/navbar_provider.dart';
import '../../core/providers/cart_provider.dart';
import '../../widgets/product_card.dart';
import '../../widgets/category_card.dart';
import '../../widgets/service_card.dart';
import '../../widgets/unified_search_bar.dart';
import 'package:go_router/go_router.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final CarouselSliderController _carouselController = CarouselSliderController();
  int _currentBannerIndex = 0;
  final ScrollController _scrollController = ScrollController();
  bool _showScrollTop = false;
  int _cartItemCount = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<HomeProvider>().loadHomeData();
      context.read<NavbarProvider>().loadNavbarData();
      context.read<CartProvider>().loadCart();
    });

    _scrollController.addListener(() {
      if (_scrollController.offset > 500 && !_showScrollTop) {
        setState(() => _showScrollTop = true);
      } else if (_scrollController.offset <= 500 && _showScrollTop) {
        setState(() => _showScrollTop = false);
      }
    });
  }

  // 🔥 Méthode pour charger le panier et mettre à jour le compteur
  Future<void> _loadCartAndUpdateCount() async {
    final cartProvider = context.read<CartProvider>();
    await cartProvider.loadCart();
    setState(() {
      _cartItemCount = cartProvider.totalItems;
    });
  }
  
  // 🔥 Méthode pour rafraîchir le compteur du panier
  void _refreshCartCount() {
    final cartProvider = context.read<CartProvider>();
    setState(() {
      _cartItemCount = cartProvider.totalItems;
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final provider = Provider.of<HomeProvider>(context);
  
    // 🔽 Add this condition to show the error
    if (provider.error != null) {
      return Scaffold(
        appBar: AppBar(title: Text('Error')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Text(
              '❌ Error loading home:\n${provider.error}',
              style: TextStyle(color: Colors.red, fontSize: 16),
              textAlign: TextAlign.center,
            ),
          ),
        ),
      );
    }
    return Consumer<CartProvider>(
      builder: (context, cartProvider, child) {
        // 🔥 Mettre à jour le compteur local quand le provider change
        if (_cartItemCount != cartProvider.totalItems) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            setState(() {
              _cartItemCount = cartProvider.totalItems;
            });
          });
        }
        return Scaffold(
          backgroundColor: Colors.grey[50],
          body: Stack(
            children: [
              CustomScrollView(
                controller: _scrollController,
                slivers: [
                  // STICKY HEADER
                  SliverAppBar(
                    pinned: true,
                    floating: false,
                    elevation: 0,
                    backgroundColor: Colors.white,
                    toolbarHeight: 70,
                    title: _buildHeader(context, cartProvider),
                  ),
                  
                  // Main Content
                  SliverFillRemaining(
                    child: RefreshIndicator(
                      onRefresh: () => context.read<HomeProvider>().refresh(),
                      color: const Color(0xFFD4AF37),
                      child: CustomScrollView(
                        slivers: [
                          // Categories Section
                          SliverToBoxAdapter(
                            child: Consumer<HomeProvider>(
                              builder: (context, provider, child) {
                                if (provider.isLoading && provider.homeData == null) {
                                  return _buildCategoriesShimmer();
                                }
                                return _buildCategoriesSection(context, provider);
                              },
                            ),
                          ),

                          // Banner Carousel
                          Consumer<HomeProvider>(
                            builder: (context, provider, child) {
                              if (provider.banners.isEmpty) return const SliverToBoxAdapter();
                              return SliverToBoxAdapter(
                                child: _buildBannerCarousel(provider.banners),
                              );
                            },
                          ),

                          // Promo Banners
                          SliverToBoxAdapter(
                            child: _buildPromoBanners(),
                          ),

                          // Featured Products Section
                          Consumer<HomeProvider>(
                            builder: (context, provider, child) {
                              if (provider.featuredProducts.isEmpty) return const SliverToBoxAdapter();
                              return SliverToBoxAdapter(
                                child: Column(
                                  children: [
                                    _buildSectionHeader(
                                      'Recommandé pour vous',
                                      onSeeAll: () => context.go('/products?featured=true'),
                                    ),
                                    // The main product feed is the staggered grid
                                    // from the brief, not a carousel. "En Promo"
                                    // and "Nos Vendeurs" below stay horizontal:
                                    // they are short curated strips, and turning
                                    // every section into a grid would leave
                                    // nothing but grids down the page.
                                    _buildProductsStaggered(provider.featuredProducts),
                                  ],
                                ),
                              );
                            },
                          ),

                          // Sale Products Section
                          Consumer<HomeProvider>(
                            builder: (context, provider, child) {
                              if (provider.saleProducts.isEmpty) return const SliverToBoxAdapter();
                              return SliverToBoxAdapter(
                                child: Column(
                                  children: [
                                    _buildSectionHeader(
                                      'En Promo',
                                      onSeeAll: () => context.go('/products?sale=true'),
                                    ),
                                    _buildProductsHorizontal(provider.saleProducts),
                                  ],
                                ),
                              );
                            },
                          ),

                          // Vendors Section - Updated to redirect to shops_screen
                          Consumer<HomeProvider>(
                            builder: (context, provider, child) {
                              if (provider.vendors.isEmpty) return const SliverToBoxAdapter();
                              return SliverToBoxAdapter(
                                child: Column(
                                  children: [
                                    _buildSectionHeader(
                                      'Nos Vendeurs',
                                      onSeeAll: () => context.go('/shops'),
                                    ),
                                    _buildVendorsHorizontal(provider.vendors),
                                  ],
                                ),
                              );
                            },
                          ),

                          // Services Section
                          /* Consumer<HomeProvider>(
                            builder: (context, provider, child) {
                              if (provider.services.isEmpty) return const SliverToBoxAdapter();
                              return SliverToBoxAdapter(
                                child: Column(
                                  children: [
                                    _buildSectionHeader(
                                      'Nos Services',
                                      onSeeAll: () => context.go('/services'),
                                    ),
                                    _buildServicesHorizontal(provider.services),
                                  ],
                                ),
                              );
                            },
                          ), */

                          const SliverToBoxAdapter(
                            child: SizedBox(height: 20),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),

              // Scroll to top button
              if (_showScrollTop)
                Positioned(
                  bottom: 20,
                  right: 20,
                  child: _buildScrollTopButton(),
                ),

              // Loading overlay
              if (context.watch<HomeProvider>().isLoading && 
                  context.watch<HomeProvider>().homeData == null)
                Container(
                  color: Colors.white.withValues(alpha: 0.7),
                  child: const Center(
                    child: CircularProgressIndicator(
                      color: Color(0xFFD4AF37),
                    ),
                  ),
                ),
            ],
          ),
        );
      }, 
    );
  }

  Widget _buildDefaultLogo() {
    return Row(
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.store,
            color: Color(0xFFD4AF37),
            size: 24,
          ),
        ),
        const SizedBox(width: 8),
        const Text(
          'MARA',
          style: TextStyle(
            color: Color(0xFFD4AF37),
            fontWeight: FontWeight.bold,
            fontSize: 18,
          ),
        ),
      ],
    );
  }
  
  // Header widget - FIXED VERSION
  Widget _buildHeader(BuildContext context, CartProvider cartProvider) {
    return Consumer<NavbarProvider>(
      builder: (context, navbarProvider, child) {
        return Row(
          children: [
            // Logo
            Image.asset(
              'assets/images/logo.png',
              height: 40,
              errorBuilder: (context, error, stackTrace) {
                logDebug('🔴 Logo error: $error');
                return _buildDefaultLogo();
              },
            ),
            
            const SizedBox(width: 12),
            
            // Search Bar - Expanded to take available space
            Expanded(
              child: Container(
                height: 45,
                decoration: BoxDecoration(
                  color: Colors.grey[100],
                  borderRadius: BorderRadius.circular(25),
                  border: Border.all(color: Colors.grey[300]!),
                ),
                child: const UnifiedSearchBar(),
              ),
            ),
            
            const SizedBox(width: 8),
            
            // Cart button with badge
            Stack(
              clipBehavior: Clip.none,
              children: [
                IconButton(
                  icon: const Icon(Icons.shopping_cart_outlined, color: Colors.black87),
                  onPressed: () => context.go('/cart'),
                ),
                if (_cartItemCount > 0)
                  Positioned(
                    right: 8,
                    top: 8,
                    child: Container(
                      padding: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        color: Colors.red,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      constraints: const BoxConstraints(
                        minWidth: 16,
                        minHeight: 16,
                      ),
                      child: Text(
                        '$_cartItemCount',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ),
              ],
            ),
          ],
        );
      },
    );
  }
    
  // Add this helper method OUTSIDE the build method, in the class
  Future<bool> _assetExists(String path) async {
    try {
      await rootBundle.load(path);
      return true;
    } catch (_) {
      return false;
    }
  }

  Widget _buildCategoriesShimmer() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  width: 150,
                  height: 24,
                  color: Colors.grey[300],
                ),
                Container(
                  width: 60,
                  height: 24,
                  color: Colors.grey[300],
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 110,
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: 6,
              itemBuilder: (context, index) {
                return Container(
                  width: 80,
                  height: 110,
                  margin: const EdgeInsets.only(right: 12),
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(8),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoriesSection(BuildContext context, HomeProvider provider) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Popular Categories',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                TextButton(
                  onPressed: () => context.go('/categories'),
                  style: TextButton.styleFrom(
                    foregroundColor: const Color(0xFFD4AF37),
                  ),
                  child: const Text('View All'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SizedBox(
            height: 110,
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: provider.categories.length,
              itemBuilder: (context, index) {
                final category = provider.categories[index];
                return CategoryCard(
                  category: category,
                  onTap: () => context.go('/products?category_id=${category.id}'),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBannerCarousel(Map<String, String> banners) {
    final bannerList = [
      banners['home-page-banner-1'],
      banners['home-page-banner-2'],
      banners['home-page-banner-3'],
    ].where((b) => b != null && b.isNotEmpty).toList();

    if (bannerList.isEmpty) return const SizedBox();

    return Container(
      height: 200,
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Stack(
        children: [
          CarouselSlider(
            carouselController: _carouselController,
            options: CarouselOptions(
              height: 200,
              autoPlay: true,
              autoPlayInterval: const Duration(seconds: 5),
              enlargeCenterPage: true,
              viewportFraction: 0.95,
              onPageChanged: (index, reason) {
                setState(() {
                  _currentBannerIndex = index;
                });
              },
            ),
            items: bannerList.map((banner) {
              return Builder(
                builder: (BuildContext context) {
                  return ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        CachedNetworkImage(
                          imageUrl: _getFullImageUrl(banner!),
                          fit: BoxFit.cover,
                          placeholder: (_, __) => Container(
                            color: Colors.grey[300],
                            child: const Center(
                              child: CircularProgressIndicator(),
                            ),
                          ),
                          errorWidget: (_, __, ___) => Container(
                            color: Colors.grey[300],
                            child: const Center(
                              child: Icon(Icons.error, color: Colors.red),
                            ),
                          ),
                        ),
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [
                                Colors.black.withValues(alpha: 0.6),
                                Colors.transparent,
                              ],
                            ),
                          ),
                        ),
                        Positioned(
                          bottom: 20,
                          left: 20,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'New Arrivals',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              ElevatedButton(
                                onPressed: () {
                                  context.go('/shops');
                                },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFD4AF37),
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 16,
                                    vertical: 8,
                                  ),
                                ),
                                child: const Text('Shop Now'),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                },
              );
            }).toList(),
          ),

          Positioned(
            bottom: 10,
            right: 10,
            child: Row(
              children: [
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.9),
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: const Icon(
                      Icons.chevron_left,
                      color: Color(0xFFD4AF37),
                    ),
                    onPressed: () => _carouselController.previousPage(),
                    iconSize: 20,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(
                      minWidth: 32,
                      minHeight: 32,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.9),
                    shape: BoxShape.circle,
                  ),
                  child: IconButton(
                    icon: const Icon(
                      Icons.chevron_right,
                      color: Color(0xFFD4AF37),
                    ),
                    onPressed: () => _carouselController.nextPage(),
                    iconSize: 20,
                    padding: EdgeInsets.zero,
                    constraints: const BoxConstraints(
                      minWidth: 32,
                      minHeight: 32,
                    ),
                  ),
                ),
              ],
            ),
          ),

          Positioned(
            bottom: 10,
            left: 10,
            child: Row(
              children: List.generate(bannerList.length, (index) {
                return Container(
                  width: 8,
                  height: 8,
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: _currentBannerIndex == index
                        ? const Color(0xFFD4AF37)
                        : Colors.white.withValues(alpha: 0.5),
                  ),
                );
              }),
            ),
          ),
        ],
      ),
    );
  }

  /// This copy was the only one that stripped leading slashes and backslashes.
  /// ImageUrl keeps that behaviour for every screen.
  String _getFullImageUrl(String path) => ImageUrl.resolve(path);

  Widget _buildPromoBanners() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Row(
        children: [
          Expanded(
            child: GestureDetector(
              onTap: () {
                // Navigate to promo
              },
              child: Container(
                height: 160,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFFD4AF37), Color(0xFFC9A12F)],
                  ),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Stack(
                  children: [
                    Positioned(
                      right: 0,
                      top: 0,
                      child: Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.1),
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.2),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.card_giftcard,
                              color: Colors.white,
                              size: 20,
                            ),
                          ),
                          const Spacer(),
                          const Text(
                            'Limited Time\nOffer',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Buy 1 Get 1 Free',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: GestureDetector(
              onTap: () {
                // Navigate to new customer deal
              },
              child: Container(
                height: 160,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Colors.black, Color(0xFF333333)],
                  ),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Stack(
                  children: [
                    Positioned(
                      right: 0,
                      bottom: 0,
                      child: Container(
                        width: 100,
                        height: 100,
                        decoration: BoxDecoration(
                          border: Border.all(
                            color: const Color(0xFFD4AF37).withValues(alpha: 0.2),
                            width: 2,
                          ),
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: const Color(0xFFD4AF37).withValues(alpha: 0.2),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.person_add,
                              color: Color(0xFFD4AF37),
                              size: 20,
                            ),
                          ),
                          const Spacer(),
                          const Text(
                            'New\nCustomer',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            '20% OFF',
                            style: TextStyle(
                              color: Color(0xFFD4AF37),
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title, {VoidCallback? onSeeAll}) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          if (onSeeAll != null)
            TextButton(
              onPressed: onSeeAll,
              style: TextButton.styleFrom(
                foregroundColor: const Color(0xFFD4AF37),
              ),
              child: const Text('See All'),
            ),
        ],
      ),
    );
  }

  /// Two columns fed alternately, with the card image height varying by index so
  /// the columns fall out of step — the offset, uneven look of the layout in the
  /// brief, where one column runs ahead of the other instead of both sitting in
  /// lockstep rows.
  ///
  /// Built by hand rather than with a staggered-grid package: adding a
  /// dependency here means a `pub get`, and a Row of two Columns is all a
  /// two-column masonry actually needs.
  ///
  /// The tall/short pattern comes from the index, never from a random number. A
  /// random height would redraw differently on every rebuild and make the page
  /// twitch as the user scrolls.
  Widget _buildProductsStaggered(List<Product> products) {
    final left = <Widget>[];
    final right = <Widget>[];

    for (var i = 0; i < products.length; i++) {
      final product = products[i];

      // 0 and 3 tall, 1 and 2 short, repeating: the left column (even indices)
      // opens tall while the right opens short, so the two never line up.
      final isTall = i % 4 == 0 || i % 4 == 3;

      final card = Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: ProductCard(
          product: UnifiedProduct.fromHomeProduct(product),
          inGrid: true,
          imageHeight: isTall ? 180 : 125,
          onTap: () {
            if (product.vendorProductId != null) {
              context.go('/product/${product.slug}/${product.vendorProductId}');
            }
          },
        ),
      );

      (i.isEven ? left : right).add(card);
    }

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: Column(children: left)),
          const SizedBox(width: 12),
          Expanded(child: Column(children: right)),
        ],
      ),
    );
  }

  Widget _buildProductsHorizontal(List<Product> products) {
    return SizedBox(
      height: 280,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: products.length,
        itemBuilder: (context, index) {
          final product = products[index];
          // Convert to UnifiedProduct
          final unifiedProduct = UnifiedProduct.fromHomeProduct(product);
          return ProductCard(
            product: unifiedProduct, // Now passing UnifiedProduct
            onTap: () {
              if (product.vendorProductId != null) {
                context.go('/product/${product.slug}/${product.vendorProductId}');
              }
            },
          );
        },
      ),
    );
  }

  Widget _buildVendorsHorizontal(List<Vendor> vendors) {
    return SizedBox(
      height: 200, // Increased height to accommodate button
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: vendors.length,
        itemBuilder: (context, index) {
          final vendor = vendors[index];
          return Container(
            width: 140,
            margin: const EdgeInsets.only(right: 12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withValues(alpha: 0.1),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // Logo
                Container(
                  height: 80,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                    image: vendor.logo != null && vendor.logo != 'logo'
                        ? DecorationImage(
                            image: CachedNetworkImageProvider(
                              ImageUrl.resolve(vendor.logo),
                            ),
                            fit: BoxFit.cover,
                          )
                        : null,
                  ),
                  child: vendor.logo == null || vendor.logo == 'logo'
                      ? Center(
                          child: Container(
                            width: 50,
                            height: 50,
                            decoration: BoxDecoration(
                              color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Center(
                              child: Text(
                                vendor.storeName.isNotEmpty ? vendor.storeName[0].toUpperCase() : '?',
                                style: const TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFFD4AF37),
                                ),
                              ),
                            ),
                          ),
                        )
                      : null,
                ),
                
                // Store Info
                Padding(
                  padding: const EdgeInsets.all(8),
                  child: Column(
                    children: [
                      // Store Name
                      Text(
                        vendor.storeName,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                        textAlign: TextAlign.center,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      
                      const SizedBox(height: 4),
                      
                      // Rating
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.star, color: Colors.amber, size: 12),
                          const SizedBox(width: 2),
                          Text(
                            vendor.rating?.toStringAsFixed(1) ?? '4.5',
                            style: TextStyle(
                              fontSize: 10,
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                      
                      const SizedBox(height: 8),
                      
                      // View Store Button
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: () {
                            context.go('/vendor/${vendor.slug}');
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFD4AF37),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            minimumSize: const Size(double.infinity, 24),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            elevation: 0,
                          ),
                          child: const Text(
                            'Voir boutique',
                            style: TextStyle(
                              fontSize: 9,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildServicesHorizontal(List<Service> services) {
    return SizedBox(
      height: 180,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: services.length,
        itemBuilder: (context, index) {
          final service = services[index];
          return ServiceCard(
            service: service,
            onTap: () => context.go('/service/${service.slug}'),
          );
        },
      ),
    );
  }

  Widget _buildScrollTopButton() {
    return GestureDetector(
      onTap: () {
        _scrollController.animateTo(
          0,
          duration: const Duration(milliseconds: 500),
          curve: Curves.easeInOut,
        );
      },
      child: Container(
        width: 50,
        height: 50,
        decoration: BoxDecoration(
          color: const Color(0xFFD4AF37),
          shape: BoxShape.circle,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.2),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: const Icon(
          Icons.arrow_upward,
          color: Colors.white,
        ),
      ),
    );
  }
}