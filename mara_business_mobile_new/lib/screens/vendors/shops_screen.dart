// shops_screen.dart - SIMPLIFIED VERSION with ONE CARD

import '../../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/models/home_models.dart'; 
import '../../core/providers/vendor_provider.dart';
import '../../core/providers/home_provider.dart';
import '../../core/providers/products_page_provider.dart';
import '../../core/providers/auth_provider.dart';
import '../../widgets/products_page_card.dart'; // ONLY this card
import '../../core/models/products_page_models.dart'; // Add this import
import '../../widgets/unified_search_bar.dart';
import 'package:go_router/go_router.dart';

class ShopsScreen extends StatefulWidget {
  const ShopsScreen({super.key});

  @override
  State<ShopsScreen> createState() => _ShopsScreenState();
}

class _ShopsScreenState extends State<ShopsScreen> with SingleTickerProviderStateMixin {
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _vendorsScrollController = ScrollController();
  final ScrollController _productsScrollController = ScrollController();
  
  bool _showFilters = false;
  int? _selectedCategoryId;
  double _selectedMaxPrice = 0;
  String _selectedSort = 'latest';

  // In shops_screen.dart, update initState:

  @override
  void initState() {
    super.initState();
    
    WidgetsBinding.instance.addPostFrameCallback((_) {
      // Force reset products provider FIRST
      context.read<ProductsPageProvider>().forceReset();
      
      final vendorProvider = context.read<VendorProvider>();
      final productsProvider = context.read<ProductsPageProvider>();
      
      // Load vendors
      vendorProvider.loadVendors();
      
      // Load ALL products initially (no vendor selected)
      productsProvider.loadProducts();
    });

    _productsScrollController.addListener(() {
      if (_productsScrollController.position.pixels >= 
          _productsScrollController.position.maxScrollExtent - 200) {
        
        final productsProvider = context.read<ProductsPageProvider>();
        productsProvider.loadMoreProducts();
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _vendorsScrollController.dispose();
    _productsScrollController.dispose();
    super.dispose();
  }

  @override
Widget build(BuildContext context) {
  return Scaffold(
    backgroundColor: Colors.grey[50],
    appBar: AppBar(
      title: const Text(
        'Boutiques',
        style: TextStyle(
          fontWeight: FontWeight.bold,
          fontSize: 24,
          color: Color(0xFF1F2937),
        ),
      ),
      backgroundColor: Colors.white,
      elevation: 0,
      centerTitle: false,
      actions: [
        Container(
          margin: const EdgeInsets.only(right: 16),
          child: ElevatedButton(
            onPressed: () {
              final user = context.read<AuthProvider>().user;
              final isAdmin = user?.roles.contains('manager') ?? false;
              final isVendor = user?.roles.contains('vendor') ?? false;
              
              if (isAdmin || isVendor) {
                // Show message that they can't apply
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(isAdmin 
                        ? 'Les administrateurs ne peuvent pas devenir vendeurs'
                        : 'Vous êtes déjà vendeur'),
                    backgroundColor: Colors.orange,
                  ),
                );
              } else {
                // Navigate to vendor apply screen
                context.push('/vendor/apply');
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFD4AF37),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              elevation: 0,
            ),
            child: const Text(
              'Devenir Vendeur',
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
      ],
    ),
    body: Consumer2<VendorProvider, ProductsPageProvider>(
      builder: (context, vendorProvider, productsProvider, child) {
        
        // Determine which products to show
        final bool isVendorSelected = vendorProvider.selectedVendor != null;
        final List<ProductsPageProduct> displayProducts = isVendorSelected
            ? _getVendorProducts(productsProvider.products, vendorProvider.selectedVendor!.id)
            : productsProvider.products;
        
        final bool isLoading = productsProvider.isLoading && displayProducts.isEmpty;
        final bool hasMorePages = productsProvider.hasMorePages;
        final int itemCount = displayProducts.length + (hasMorePages ? 1 : 0);
        final bool isEmpty = displayProducts.isEmpty && !isLoading;

        return Column(
          children: [
            // Search Bar (fixed height)
            Container(
              padding: const EdgeInsets.all(16),
              color: Colors.white,
              child: const UnifiedSearchBar(),
            ),

            // Vendors Carousel (fixed height)
            if (vendorProvider.vendors.isNotEmpty)
              Container(
                height: 180,
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: ListView.builder(
                  controller: _vendorsScrollController,
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: vendorProvider.vendors.length,
                  itemBuilder: (context, index) {
                    final vendor = vendorProvider.vendors[index];
                    return _buildVendorCarouselItem(vendor, vendorProvider);
                  },
                ),
              ),
            
            // Selected Vendor Chip (variable height)
            if (vendorProvider.selectedVendor != null)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(30),
                    border: Border.all(color: const Color(0xFFD4AF37)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircleAvatar(
                        radius: 12,
                        backgroundImage: vendorProvider.selectedVendor!.logo != null
                            ? CachedNetworkImageProvider(
                                ImageUrl.resolve(vendorProvider.selectedVendor!.logo),
                              )
                            : null,
                        child: vendorProvider.selectedVendor!.logo == null
                            ? const Icon(Icons.store, size: 14, color: Color(0xFFD4AF37))
                            : null,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        'Boutique: ${vendorProvider.selectedVendor!.storeName}',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1F2937),
                        ),
                      ),
                      const SizedBox(width: 8),
                      GestureDetector(
                        onTap: () {
                          vendorProvider.clearSelectedVendor();
                        },
                        child: const Icon(Icons.close, size: 18, color: Colors.grey),
                      ),
                    ],
                  ),
                ),
              ),
            
            // Filters Toggle (fixed height)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    isVendorSelected ? 'Produits du vendeur' : 'Tous les produits',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.grey[800],
                    ),
                  ),
                  TextButton.icon(
                    onPressed: () {
                      setState(() {
                        _showFilters = !_showFilters;
                      });
                    },
                    icon: Icon(
                      _showFilters ? Icons.filter_list_off : Icons.filter_list,
                      color: const Color(0xFFD4AF37),
                      size: 20,
                    ),
                    label: Text(
                      _showFilters ? 'Masquer filtres' : 'Filtres',
                      style: const TextStyle(color: Color(0xFFD4AF37)),
                    ),
                  ),
                ],
              ),
            ),
            
            // Filters Panel (if visible, scrollable)
            if (_showFilters)
              Container(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(context).size.height * 0.35,
                ),
                child: SingleChildScrollView(
                  child: _buildFiltersPanel(productsProvider),
                ),
              ),

            // Products Grid (takes remaining space)
            Expanded(
              child: isLoading
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFFD4AF37)))
                  : isEmpty
                      ? _buildEmptyState(isVendorSelected)
                      : GridView.builder(
                          controller: _productsScrollController,
                          padding: const EdgeInsets.all(16),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            childAspectRatio: 0.65,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                          ),
                          itemCount: itemCount,
                          itemBuilder: (context, index) {
                            if (index == displayProducts.length) {
                              return const Center(
                                child: Padding(
                                  padding: EdgeInsets.all(16),
                                  child: CircularProgressIndicator(color: Color(0xFFD4AF37)),
                                ),
                              );
                            }
                            
                            final product = displayProducts[index];
                            return ProductsPageCard(
                              product: product,
                              onTap: () {
                                context.go('/product/${product.slug}/${product.id}');
                              },
                            );
                          },
                        ),
            ),
          ],
        );
      },
    ),
  );
}

  // Helper method to filter products by vendor
  List<ProductsPageProduct> _getVendorProducts(List<ProductsPageProduct> allProducts, int vendorId) {
    return allProducts.where((product) => product.vendorId == vendorId).toList();
  }

  Widget _buildFiltersPanel(ProductsPageProvider productsProvider) {
  return Container(
    constraints: BoxConstraints(
      maxHeight: MediaQuery.of(context).size.height * 0.7, // Limit height to 70% of screen
    ),
    child: SingleChildScrollView( // Make it scrollable
      child: Container(
        padding: const EdgeInsets.all(16),
        color: Colors.white,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min, // Take minimum space
          children: [
            const Text(
              'Filtres',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            
            // Category Filter
            Consumer<HomeProvider>(
              builder: (context, homeProvider, child) {
                return DropdownButtonFormField<int?>(
                  initialValue: _selectedCategoryId,
                  decoration: InputDecoration(
                    labelText: 'Catégorie',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  items: [
                    const DropdownMenuItem<int?>(
                      value: null,
                      child: Text('Toutes les catégories'),
                    ),
                    ...homeProvider.categories.map((category) {
                      return DropdownMenuItem<int?>(
                        value: category.id,
                        child: Text(category.name),
                      );
                    }),
                  ],
                  onChanged: (value) {
                    setState(() {
                      _selectedCategoryId = value;
                    });
                    
                    // Apply filter
                    productsProvider.applyFilters(
                      categories: value != null ? [value] : null,
                    );
                  },
                );
              },
            ),
            
            const SizedBox(height: 12),
            
            // Price Range
            Text('Prix maximum: ${_selectedMaxPrice.toStringAsFixed(0)} ${_getCurrencySymbol('USD')}'),
            Slider(
              value: _selectedMaxPrice,
              min: 0,
              max: 2000,
              divisions: 20,
              activeColor: const Color(0xFFD4AF37),
              onChanged: (value) {
                setState(() {
                  _selectedMaxPrice = value;
                });
              },
            ),
            
            const SizedBox(height: 12),
            
            // Sort Options
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                ChoiceChip(
                  label: const Text('Nouveautés'),
                  selected: _selectedSort == 'latest',
                  onSelected: (selected) {
                    setState(() {
                      _selectedSort = 'latest';
                    });
                  },
                  selectedColor: const Color(0xFFD4AF37).withValues(alpha: 0.2),
                ),
                ChoiceChip(
                  label: const Text('Prix croissant'),
                  selected: _selectedSort == 'price_asc',
                  onSelected: (selected) {
                    setState(() {
                      _selectedSort = 'price_asc';
                    });
                  },
                  selectedColor: const Color(0xFFD4AF37).withValues(alpha: 0.2),
                ),
                ChoiceChip(
                  label: const Text('Prix décroissant'),
                  selected: _selectedSort == 'price_desc',
                  onSelected: (selected) {
                    setState(() {
                      _selectedSort = 'price_desc';
                    });
                  },
                  selectedColor: const Color(0xFFD4AF37).withValues(alpha: 0.2),
                ),
              ],
            ),
            
            const SizedBox(height: 12),
            
            // Apply Filters Button
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () {
                      setState(() {
                        _selectedCategoryId = null;
                        _selectedMaxPrice = 0;
                        _selectedSort = 'latest';
                        _showFilters = false;
                      });
                      
                      productsProvider.clearFilters();
                    },
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.grey,
                      side: const BorderSide(color: Colors.grey),
                    ),
                    child: const Text('Réinitialiser'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      productsProvider.applyFilters(
                        categories: _selectedCategoryId != null ? [_selectedCategoryId!] : null,
                        priceRange: _selectedMaxPrice > 0 ? _selectedMaxPrice : null,
                        sort: _selectedSort,
                      );
                      setState(() {
                        _showFilters = false; // Close filters
                      });
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD4AF37),
                      foregroundColor: Colors.white,
                    ),
                    child: const Text('Appliquer'),
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

  Widget _buildEmptyState(bool isVendorSelected) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.inventory_2, size: 80, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            isVendorSelected ? 'Aucun produit trouvé pour ce vendeur' : 'Aucun produit trouvé',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Essayez de modifier vos filtres',
            style: TextStyle(color: Colors.grey[500]),
          ),
        ],
      ),
    );
  }

  Widget _buildVendorCarouselItem(Vendor vendor, VendorProvider provider) {
    final isSelected = provider.selectedVendor?.id == vendor.id;
    
    return GestureDetector(
      onTap: () {
        if (isSelected) {
          provider.clearSelectedVendor();
        } else {
          provider.selectVendor(vendor);
        }
      },
      child: Container(
        width: 120,
        margin: const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFD4AF37).withValues(alpha: 0.1) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isSelected ? const Color(0xFFD4AF37) : Colors.grey[300]!,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Logo
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.grey[100],
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
                  ? const Icon(Icons.store, color: Color(0xFFD4AF37), size: 25)
                  : null,
            ),
            const SizedBox(height: 8),
            
            // Store name
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              child: Text(
                vendor.storeName,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  color: isSelected ? const Color(0xFFD4AF37) : Colors.black87,
                ),
                maxLines: 2,
                textAlign: TextAlign.center,
                overflow: TextOverflow.ellipsis,
              ),
            ),
            
            // Rating
            Container(
              constraints: const BoxConstraints(maxWidth: 100),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.star, color: Colors.amber, size: 10),
                  const SizedBox(width: 2),
                  Flexible(
                    child: Text(
                      vendor.rating?.toStringAsFixed(1) ?? '4.5',
                      style: TextStyle(
                        fontSize: 9,
                        color: Colors.grey[600],
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 8),
            
            // View Store Button
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
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
    );
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
}