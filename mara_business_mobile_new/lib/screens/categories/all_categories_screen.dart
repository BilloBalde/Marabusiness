import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/models/home_models.dart'; // For Category
import '../../core/models/brand_models.dart'; // For Brand
import '../../core/providers/home_provider.dart';
import '../../core/providers/brand_provider.dart';
import '../../core/constants/app_constants.dart';
import '../products/products_screen.dart';

class AllCategoriesScreen extends StatefulWidget {
  const AllCategoriesScreen({super.key});

  @override
  State<AllCategoriesScreen> createState() => _AllCategoriesScreenState();
}

class _AllCategoriesScreenState extends State<AllCategoriesScreen> {
  final TextEditingController _categorySearchController = TextEditingController();
  final TextEditingController _brandSearchController = TextEditingController();
  final FocusNode _categoryFocusNode = FocusNode();
  final FocusNode _brandFocusNode = FocusNode();
  
  bool _showCategorySearch = false;
  bool _showBrandSearch = false;

  // Filtered lists
  List<Category> _filteredCategories = [];
  List<Brand> _filteredBrands = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final homeProvider = context.read<HomeProvider>();
      final brandProvider = context.read<BrandProvider>();
      
      _filteredCategories = List.from(homeProvider.categories);
      brandProvider.loadBrands().then((_) {
        setState(() {
          _filteredBrands = List.from(brandProvider.brands);
        });
      });
    });
  }

  @override
  void dispose() {
    _categorySearchController.dispose();
    _brandSearchController.dispose();
    _categoryFocusNode.dispose();
    _brandFocusNode.dispose();
    super.dispose();
  }

  void _filterCategories(String query) {
    final homeProvider = context.read<HomeProvider>();
    if (query.isEmpty) {
      _filteredCategories = List.from(homeProvider.categories);
    } else {
      _filteredCategories = homeProvider.categories
          .where((category) =>
              category.name.toLowerCase().contains(query.toLowerCase()))
          .toList();
    }
    setState(() {});
  }

  void _filterBrands(String query) {
    final brandProvider = context.read<BrandProvider>();
    brandProvider.search(query);
    setState(() {
      _filteredBrands = brandProvider.brands;
    });
  }

  @override
  Widget build(BuildContext context) {
    final homeProvider = context.watch<HomeProvider>();
    final brandProvider = context.watch<BrandProvider>();
    
    // Update filtered lists if needed
    if (_filteredCategories.isEmpty && homeProvider.categories.isNotEmpty) {
      _filteredCategories = List.from(homeProvider.categories);
    }
    
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Explorer',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 24,
            color: Color(0xFF1F2937),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        /* actions: [
          IconButton(
            icon: const Icon(Icons.filter_list, color: Color(0xFFD4AF37)),
            onPressed: () {
              // Show filter options
            },
          ),
        ], */
      ),
      body: CustomScrollView(
        slivers: [
          // Categories Section Header
          SliverToBoxAdapter(
            child: _buildSectionHeader(
              title: 'Toutes les Catégories',
              icon: Icons.category_outlined,
              searchController: _categorySearchController,
              focusNode: _categoryFocusNode,
              showSearch: _showCategorySearch,
              onSearchToggle: () {
                setState(() {
                  _showCategorySearch = !_showCategorySearch;
                  if (!_showCategorySearch) {
                    _categorySearchController.clear();
                    _filterCategories('');
                  }
                });
              },
              onSearchChanged: (value) {
                _filterCategories(value);
              },
            ),
          ),

          // Categories Grid
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                childAspectRatio: 0.9,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  if (_filteredCategories.isEmpty) {
                    return const SizedBox();
                  }
                  final category = _filteredCategories[index];
                  return _buildCategoryCard(category);
                },
                childCount: _filteredCategories.length,
              ),
            ),
          ),

          // Empty state for categories
          if (_filteredCategories.isEmpty && _showCategorySearch)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Center(
                  child: Column(
                    children: [
                      Icon(Icons.search_off, size: 48, color: Colors.grey[400]),
                      const SizedBox(height: 8),
                      Text(
                        'Aucune catégorie trouvée',
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),
              ),
            ),

          // Divider
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
              child: Row(
                children: [
                  Container(
                    height: 2,
                    width: 50,
                    decoration: BoxDecoration(
                      color: const Color(0xFFD4AF37),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Marques Populaires',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[600],
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Brands Section Header
          SliverToBoxAdapter(
            child: _buildSectionHeader(
              title: 'Toutes les Marques',
              icon: Icons.store_outlined,
              searchController: _brandSearchController,
              focusNode: _brandFocusNode,
              showSearch: _showBrandSearch,
              onSearchToggle: () {
                setState(() {
                  _showBrandSearch = !_showBrandSearch;
                  if (!_showBrandSearch) {
                    _brandSearchController.clear();
                    brandProvider.clearSearch();
                    _filteredBrands = brandProvider.brands;
                  }
                });
              },
              onSearchChanged: (value) {
                _filterBrands(value);
              },
            ),
          ),

          // Brands Grid - Loading state
          if (brandProvider.isLoading)
            const SliverToBoxAdapter(
              child: Center(
                child: Padding(
                  padding: EdgeInsets.all(32),
                  child: CircularProgressIndicator(color: Color(0xFFD4AF37)),
                ),
              ),
            ),

          // Brands Grid
          if (!brandProvider.isLoading)
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              sliver: SliverGrid(
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  childAspectRatio: 1.6,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                ),
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final brands = _showBrandSearch ? _filteredBrands : brandProvider.brands;
                    if (brands.isEmpty) {
                      return const SizedBox();
                    }
                    final brand = brands[index];
                    return _buildBrandCard(brand);
                  },
                  childCount: _showBrandSearch ? _filteredBrands.length : brandProvider.brands.length,
                ),
              ),
            ),

          // Empty state for brands
          if (!brandProvider.isLoading && brandProvider.brands.isEmpty)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Center(
                  child: Column(
                    children: [
                      Icon(Icons.storefront, size: 48, color: Colors.grey[400]),
                      const SizedBox(height: 8),
                      Text(
                        'Aucune marque disponible',
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),
              ),
            ),

          if (_showBrandSearch && _filteredBrands.isEmpty && brandProvider.brands.isNotEmpty)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Center(
                  child: Column(
                    children: [
                      Icon(Icons.search_off, size: 48, color: Colors.grey[400]),
                      const SizedBox(height: 8),
                      Text(
                        'Aucune marque trouvée',
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),
              ),
            ),

          // Bottom padding
          const SliverToBoxAdapter(
            child: SizedBox(height: 20),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionHeader({
    required String title,
    required IconData icon,
    required TextEditingController searchController,
    required FocusNode focusNode,
    required bool showSearch,
    required VoidCallback onSearchToggle,
    required Function(String) onSearchChanged,
  }) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: const Color(0xFFD4AF37).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(icon, color: const Color(0xFFD4AF37), size: 20),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1F2937),
                    ),
                  ),
                ],
              ),
              Container(
                decoration: BoxDecoration(
                  color: showSearch ? const Color(0xFFD4AF37) : Colors.grey[100],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: IconButton(
                  icon: Icon(
                    showSearch ? Icons.close : Icons.search,
                    color: showSearch ? Colors.white : const Color(0xFFD4AF37),
                    size: 20,
                  ),
                  onPressed: onSearchToggle,
                ),
              ),
            ],
          ),
          if (showSearch) ...[
            const SizedBox(height: 12),
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey[200]!),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.1),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: TextField(
                controller: searchController,
                focusNode: focusNode,
                decoration: InputDecoration(
                  hintText: 'Rechercher...',
                  hintStyle: TextStyle(color: Colors.grey[400]),
                  prefixIcon: const Icon(Icons.search, color: Color(0xFFD4AF37)),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                ),
                onChanged: onSearchChanged,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildCategoryCard(Category category) {
    return GestureDetector(
      onTap: () {
        // Use GoRouter to navigate with query parameters
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => ProductsScreen(
              categoryId: category.id,
            ),
          ),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 60,
              height: 60,
              decoration: BoxDecoration(
                color: Colors.grey[50],
                borderRadius: BorderRadius.circular(30),
                border: Border.all(
                  color: const Color(0xFFD4AF37).withOpacity(0.3),
                  width: 2,
                ),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(30),
                child: category.image != null
                    ? CachedNetworkImage(
                        imageUrl: category.image!.startsWith('http')
                            ? category.image!
                            : '${AppConstants.baseUrl}/uploads/${category.image}',
                        fit: BoxFit.cover,
                        placeholder: (_, __) => const Center(
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                        errorWidget: (_, __, ___) => const Icon(
                          Icons.category,
                          color: Color(0xFFD4AF37),
                          size: 30,
                        ),
                      )
                    : const Icon(
                        Icons.category,
                        color: Color(0xFFD4AF37),
                        size: 30,
                      ),
              ),
            ),
            const SizedBox(height: 8),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              child: Text(
                category.name,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF1F2937),
                ),
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBrandCard(Brand brand) {
    return GestureDetector(
      onTap: () {
        // Navigate to brand products page
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (_) => ProductsScreen(
              brandId: brand.id,
            ),
          ),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            // Logo
            Container(
              width: 60,
              height: 60,
              margin: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.grey[100],
                borderRadius: BorderRadius.circular(12),
                image: brand.image != null
                    ? DecorationImage(
                        image: CachedNetworkImageProvider(
                          brand.image!.startsWith('http')
                              ? brand.image!
                              : '${AppConstants.baseUrl}/uploads/${brand.image}',
                        ),
                        fit: BoxFit.cover,
                      )
                    : null,
              ),
              child: brand.image == null
                  ? const Icon(
                      Icons.store,
                      color: Color(0xFFD4AF37),
                      size: 30,
                    )
                  : null,
            ),
            
            // Info
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    brand.name,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1F2937),
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFD4AF37).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          'Voir produits',
                          style: const TextStyle(
                            fontSize: 10,
                            color: Color(0xFFD4AF37),
                          ),
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