// In products_screen.dart - COMPLETE REWRITE with bigger text

import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/providers/products_page_provider.dart';
import '../../widgets/common_product_card.dart';
import 'package:go_router/go_router.dart';

class ProductsScreen extends StatefulWidget {
  final int? categoryId;
  final int? brandId;
  final int? vendorId;
  final bool featured;
  final bool sale;

  const ProductsScreen({
    super.key,
    this.categoryId,
    this.brandId,
    this.vendorId,
    this.featured = false,
    this.sale = false,
  });

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  final ScrollController _scrollController = ScrollController();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    logDebug('🔵 ProductsScreen initState with categoryId: ${widget.categoryId}');
    _scrollController.addListener(_onScroll);
    
    // Force reset and load on every entry
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _forceReload();
    });
  }

  @override
  void didUpdateWidget(ProductsScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    // If the parameters changed, reload
    if (oldWidget.categoryId != widget.categoryId || 
        oldWidget.brandId != widget.brandId) {
      logDebug('🔵 Widget parameters changed, reloading');
      _forceReload();
    }
  }

  @override
  void dispose() {
    logDebug('🔵 ProductsScreen disposing');
     // Reset the provider when leaving the screen
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.read<ProductsPageProvider>().forceReset();
      }
    });
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      final provider = context.read<ProductsPageProvider>();
      if (provider.hasMorePages && !provider.isLoading && !provider.isLoadingMore) {
        logDebug('🔵 Loading more products');
        provider.loadMoreProducts();
      }
    }
  }

  Future<void> _forceReload() async {
    if (_isLoading) return;
    
    setState(() => _isLoading = true);
    
    final provider = context.read<ProductsPageProvider>();
    
    // 1. Force reset everything
    provider.forceReset();
    
    // 2. Small delay to ensure reset completes
    await Future.delayed(const Duration(milliseconds: 100));
    
    // 3. Apply filters based on parameters
    if (widget.categoryId != null) {
      logDebug('🔵 Loading products for category: ${widget.categoryId}');
      provider.toggleCategory(widget.categoryId!);
    } else if (widget.brandId != null) {
      logDebug('🔵 Loading products for brand: ${widget.brandId}');
      provider.toggleBrand(widget.brandId!);
    } else if (widget.vendorId != null) {  // ADD THIS
      logDebug('🔵 Loading products for vendor: ${widget.vendorId}');
      // You'll need to add vendor filtering to your provider
      // For now, just load all products
      await provider.loadProducts(refresh: true);
    } else if (widget.featured) {
      logDebug('🔵 Loading featured products');
      provider.applyFilters(featured: true);
    } else if (widget.sale) {
      logDebug('🔵 Loading sale products');
      provider.applyFilters(onSale: true);
    } else {
      logDebug('🔵 Loading all products');
      await provider.loadProducts(refresh: true);
    }
    
    setState(() => _isLoading = false);
}
  Future<void> _refresh() async {
    await _forceReload();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: Text(
          _getTitle(),
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 22, // INCREASED from 20 to 22
          ),
        ),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () {
            if (context.canPop()) {
              context.pop(); // Use GoRouter pop
            } else {
              context.go('/'); // Go to home if can't pop
            }
          },/* => Navigator.pop(context), */
        ),
      ),
      body: Consumer<ProductsPageProvider>(
        builder: (context, provider, child) {
          // Debug prints
          logDebug('🔵 BUILD - Products count: ${provider.products.length}');
          logDebug('🔵 BUILD - Selected categories: ${provider.selectedCategories}');
          logDebug('🔵 BUILD - Selected brands: ${provider.selectedBrands}');
          logDebug('🔵 BUILD - Is loading: ${provider.isLoading}');
          
          if (provider.isLoading && provider.products.isEmpty) {
            return const Center(
              child: CircularProgressIndicator(color: Color(0xFFD4AF37)),
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
                    style: TextStyle(
                      fontSize: 20, // INCREASED from 18 to 20
                      fontWeight: FontWeight.bold,
                      color: Colors.grey[800],
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    provider.error!,
                    style: TextStyle(
                      fontSize: 16, // INCREASED from default to 16
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _forceReload,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD4AF37),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    ),
                    child: const Text(
                      'Réessayer',
                      style: TextStyle(fontSize: 16), // INCREASED
                    ),
                  ),
                ],
              ),
            );
          }

          if (provider.products.isEmpty && !provider.isLoading) {
            String message = 'Aucun produit trouvé';
            if (widget.categoryId != null) {
              message = 'Aucun produit dans cette catégorie';
            } else if (widget.brandId != null) {
              message = 'Aucun produit pour cette marque';
            } else if (widget.featured) {
              message = 'Aucun produit en vedette';
            } else if (widget.sale) {
              message = 'Aucun produit en promotion';
            }
            
            return RefreshIndicator(
              onRefresh: _refresh,
              color: const Color(0xFFD4AF37),
              child: ListView(
                children: [
                  SizedBox(
                    height: MediaQuery.of(context).size.height * 0.3,
                  ),
                  Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.inventory_2, size: 100, color: Colors.grey[400]), // INCREASED from 80 to 100
                        const SizedBox(height: 16),
                        Text(
                          message,
                          style: TextStyle(
                            fontSize: 20, // INCREASED from 18 to 20
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Tirez vers le bas pour actualiser',
                          style: TextStyle(
                            fontSize: 16, // INCREASED
                            color: Colors.grey[500],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: _refresh,
            color: const Color(0xFFD4AF37),
            child: GridView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(16),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: 0.7,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              itemCount: provider.products.length + (provider.hasMorePages ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == provider.products.length) {
                  return const Center(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(color: Color(0xFFD4AF37)),
                    ),
                  );
                }

                final product = provider.products[index];
                return CommonProductCard(
                  product: product,
                  onTap: () {
                    context.push('/product/${product.slug}/${product.id}');
                  },
                );
              },
            ),
          );
        },
      ),
    );
  }

  String _getTitle() {
    if (widget.categoryId != null) {
      return 'Produits par catégorie';
    } else if (widget.brandId != null) {
      return 'Produits par marque';
    } else if (widget.vendorId != null) {
      return 'Produits du vendeur';
    } else if (widget.featured) {
      return 'Produits en vedette';
    } else if (widget.sale) {
      return 'Produits en promotion';
    }
    return 'Tous les Produits';
  }
}