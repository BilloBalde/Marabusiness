import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/providers/home_provider.dart';
import '../../core/providers/vendor_provider.dart';
import '../../core/constants/app_constants.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/models/home_models.dart';
import 'package:go_router/go_router.dart';

class AllVendorsScreen extends StatefulWidget {
  const AllVendorsScreen({super.key});

  @override
  State<AllVendorsScreen> createState() => _AllVendorsScreenState();
}

class _AllVendorsScreenState extends State<AllVendorsScreen> {
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  List<Vendor> _filteredVendors = [];
  bool _isSearching = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final homeProvider = context.read<HomeProvider>();
      _filteredVendors = List.from(homeProvider.vendors);
      
      // Also load from vendor provider for more data
      context.read<VendorProvider>().loadVendors();
    });

    _searchController.addListener(_filterVendors);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _filterVendors() {
    final query = _searchController.text.toLowerCase().trim();
    final homeProvider = context.read<HomeProvider>();
    
    setState(() {
      _isSearching = query.isNotEmpty;
      if (query.isEmpty) {
        _filteredVendors = List.from(homeProvider.vendors);
      } else {
        _filteredVendors = homeProvider.vendors
            .where((vendor) => 
                vendor.storeName.toLowerCase().contains(query) ||
                (vendor.description?.toLowerCase().contains(query) ?? false))
            .toList();
      }
    });
  }

  void _clearSearch() {
    _searchController.clear();
    FocusScope.of(context).unfocus();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text(
          'Boutiques Officielles',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 20,
            color: Color(0xFF1F2937),
          ),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
        actions: [
          // Become a Vendor Button
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
      body: Consumer2<HomeProvider, VendorProvider>(
        builder: (context, homeProvider, vendorProvider, child) {
          // Use vendor provider's vendors if available, otherwise use home provider
          final displayVendors = vendorProvider.vendors.isNotEmpty 
              ? (_isSearching ? _filteredVendors : vendorProvider.vendors)
              : (_isSearching ? _filteredVendors : homeProvider.vendors);
          
          return Column(
            children: [
              // Search Bar
              Container(
                padding: const EdgeInsets.all(16),
                color: Colors.white,
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.grey[300]!),
                  ),
                  child: Row(
                    children: [
                      const SizedBox(width: 12),
                      const Icon(Icons.search, color: Color(0xFFD4AF37), size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: TextField(
                          controller: _searchController,
                          decoration: InputDecoration(
                            hintText: 'Rechercher une boutique...',
                            hintStyle: TextStyle(color: Colors.grey[500], fontSize: 14),
                            border: InputBorder.none,
                            isDense: true,
                          ),
                        ),
                      ),
                      if (_searchController.text.isNotEmpty)
                        IconButton(
                          icon: const Icon(Icons.clear, color: Colors.grey, size: 18),
                          onPressed: _clearSearch,
                        ),
                    ],
                  ),
                ),
              ),

              // Vendors Grid
              Expanded(
                child: vendorProvider.isLoading && displayVendors.isEmpty
                    ? const Center(child: CircularProgressIndicator(color: Color(0xFFD4AF37)))
                    : displayVendors.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.storefront, size: 80, color: Colors.grey[400]),
                                const SizedBox(height: 16),
                                Text(
                                  _isSearching 
                                      ? 'Aucune boutique trouvée'
                                      : 'Aucune boutique disponible',
                                  style: TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.grey[600],
                                  ),
                                ),
                                if (_isSearching) ...[
                                  const SizedBox(height: 8),
                                  Text(
                                    'Essayez avec d\'autres mots-clés',
                                    style: TextStyle(color: Colors.grey[500]),
                                  ),
                                  const SizedBox(height: 16),
                                  OutlinedButton(
                                    onPressed: _clearSearch,
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: const Color(0xFFD4AF37),
                                      side: const BorderSide(color: Color(0xFFD4AF37)),
                                    ),
                                    child: const Text('Effacer la recherche'),
                                  ),
                                ],
                              ],
                            ),
                          )
                        : GridView.builder(
                            controller: _scrollController,
                            padding: const EdgeInsets.all(16),
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              childAspectRatio: 0.85,
                              crossAxisSpacing: 12,
                              mainAxisSpacing: 12,
                            ),
                            itemCount: displayVendors.length,
                            itemBuilder: (context, index) {
                              final vendor = displayVendors[index];
                              return _buildVendorCard(vendor);
                            },
                          ),
              ),
            ],
          );
        },
      ),
    );
  }

  // In all_vendors_screen.dart, replace the _buildVendorCard method:

Widget _buildVendorCard(Vendor vendor) {
  return GestureDetector(
    onTap: () {
      context.go('/vendor/${vendor.slug}');
    },
    child: Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          // Logo
          ClipRRect(
            borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            child: Container(
              height: 100,
              width: double.infinity,
              color: Colors.grey[100],
              child: vendor.logo != null && vendor.logo != 'logo' && vendor.logo!.isNotEmpty
                  ? CachedNetworkImage(
                      imageUrl: vendor.logo!.startsWith('http')
                          ? vendor.logo!
                          : '${AppConstants.baseUrl}/uploads/${vendor.logo}',
                      fit: BoxFit.cover,
                      errorWidget: (context, error, stack) => Center(
                        child: Container(
                          width: 60,
                          height: 60,
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
                      ),
                    )
                  : Center(
                      child: Container(
                        width: 60,
                        height: 60,
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
                    ),
            ),
          ),
          
          // Store Info
          Padding(
            padding: const EdgeInsets.all(8.0), // Reduced from 12 to 8
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Store Name with Verified Badge
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Flexible(
                      child: Text(
                        vendor.storeName,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 13, // Reduced from 14
                        ),
                        textAlign: TextAlign.center,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (vendor.isVerified == true)
                      const Padding(
                        padding: EdgeInsets.only(left: 2),
                        child: Icon(
                          Icons.verified,
                          color: Colors.blue,
                          size: 12, // Reduced from 14
                        ),
                      ),
                  ],
                ),
                
                // Description
                if (vendor.description != null && vendor.description!.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      vendor.description!,
                      style: TextStyle(
                        fontSize: 9, // Reduced from 10
                        color: Colors.grey[600],
                      ),
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                
                const SizedBox(height: 4), // Reduced from 8
                
                // Stats Row
                Wrap(
                  alignment: WrapAlignment.center,
                  spacing: 6, // Reduced from 8
                  runSpacing: 2,
                  children: [
                    // Rating
                    IntrinsicHeight(
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.star, color: Colors.amber, size: 10), // Reduced from 12
                          const SizedBox(width: 1),
                          Text(
                            vendor.rating?.toStringAsFixed(1) ?? '0.0',
                            style: TextStyle(
                              fontSize: 9, // Reduced from 10
                              fontWeight: FontWeight.w600,
                              color: Colors.grey[800],
                            ),
                          ),
                        ],
                      ),
                    ),
                    
                    // Followers
                    IntrinsicHeight(
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.people_outline, color: Colors.grey[600], size: 9), // Reduced from 10
                          const SizedBox(width: 1),
                          Text(
                            '${vendor.followersCount ?? 0}',
                            style: TextStyle(
                              fontSize: 9, // Reduced from 10
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    ),
                    
                    // Products Count
                    IntrinsicHeight(
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.inventory_2_outlined, color: Colors.grey[600], size: 9), // Reduced from 10
                          const SizedBox(width: 1),
                          Text(
                            '${vendor.productsCount ?? 0}',
                            style: TextStyle(
                              fontSize: 9, // Reduced from 10
                              color: Colors.grey[600],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                
                const SizedBox(height: 6), // Reduced from 8
                
                // View Button
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 4), // Reduced from 6
                  decoration: BoxDecoration(
                    color: const Color(0xFFD4AF37),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'Voir Boutique',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10, // Reduced from 11
                      fontWeight: FontWeight.w600,
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

}