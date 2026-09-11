import '../utils/image_url.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart'; // Add this import
import 'package:cached_network_image/cached_network_image.dart';
import '../core/providers/unified_search_provider.dart';
import '../core/models/home_models.dart';

class UnifiedSearchBar extends StatefulWidget {
  final VoidCallback? onClose;
  final bool autoFocus;

  const UnifiedSearchBar({
    super.key,
    this.onClose,
    this.autoFocus = false,
  });

  @override
  State<UnifiedSearchBar> createState() => _UnifiedSearchBarState();
}

class _UnifiedSearchBarState extends State<UnifiedSearchBar> {
  final TextEditingController _controller = TextEditingController();
  final FocusNode _focusNode = FocusNode();
  bool _showResults = false;
  final LayerLink _layerLink = LayerLink();
  OverlayEntry? _overlayEntry;

  @override
  void initState() {
    super.initState();
    if (widget.autoFocus) {
      _focusNode.requestFocus();
    }
    _focusNode.addListener(_onFocusChange);
  }

  @override
  void dispose() {
    _controller.dispose();
    _focusNode.removeListener(_onFocusChange);
    _focusNode.dispose();
    _removeOverlay();
    super.dispose();
  }

  void _onFocusChange() {
    if (_focusNode.hasFocus && _controller.text.length >= 2) {
      _showResults = true;
      _showOverlay();
    } else if (!_focusNode.hasFocus) {
      _showResults = false;
      _removeOverlay();
    }
  }

  void _removeOverlay() {
    _overlayEntry?.remove();
    _overlayEntry = null;
  }

  void _showOverlay() {
    _removeOverlay();
    
    final renderBox = context.findRenderObject() as RenderBox;
    final size = renderBox.size;
    final offset = renderBox.localToGlobal(Offset.zero);
    
    _overlayEntry = OverlayEntry(
      builder: (context) => Positioned(
        left: offset.dx,
        top: offset.dy + size.height + 4,
        width: size.width,
        child: Consumer<UnifiedSearchProvider>(
          builder: (context, searchProvider, child) {
            return Material(
              elevation: 8,
              borderRadius: BorderRadius.circular(12),
              child: Container(
                constraints: BoxConstraints(
                  maxHeight: MediaQuery.of(context).size.height * 0.5,
                ),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.grey.withValues(alpha: 0.3),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: _buildResultsContent(searchProvider),
              ),
            );
          },
        ),
      ),
    );

    Overlay.of(context).insert(_overlayEntry!);
  }

  Widget _buildResultsContent(UnifiedSearchProvider searchProvider) {
    if (searchProvider.isLoading) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(16),
          child: CircularProgressIndicator(color: Color(0xFFD4AF37)),
        ),
      );
    }

    if (searchProvider.results.isEmpty) {
      return const Padding(
        padding: EdgeInsets.all(16),
        child: Center(
          child: Text('Aucun résultat trouvé'),
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          // Categories
          if (searchProvider.results.categories.isNotEmpty) ...[
            const Text(
              'Catégories',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 8),
            ...searchProvider.results.categories.map((category) => 
              _buildCategoryTile(category)
            ),
            const Divider(height: 24),
          ],

          // Vendors
          if (searchProvider.results.vendors.isNotEmpty) ...[
            const Text(
              'Boutiques',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 8),
            ...searchProvider.results.vendors.map((vendor) => 
              _buildVendorTile(vendor)
            ),
            const Divider(height: 24),
          ],

          // Products
          if (searchProvider.results.products.isNotEmpty) ...[
            const Text(
              'Produits',
              style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 8),
            ...searchProvider.results.products.take(5).map((product) => 
              _buildProductTile(product)
            ),
          ],

          // View all results button
          if (searchProvider.results.products.length > 5 ||
              searchProvider.results.vendors.length > 5 ||
              searchProvider.results.categories.length > 5)
            Padding(
              padding: const EdgeInsets.only(top: 16),
              child: Center(
                child: TextButton(
                  onPressed: () {
                    context.push('/search', extra: {'query': _controller.text});
                    _focusNode.unfocus();
                  },
                  child: const Text('Voir tous les résultats'),
                ),
              ),
            ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return CompositedTransformTarget(
      link: _layerLink,
      child: Consumer<UnifiedSearchProvider>(
        builder: (context, searchProvider, child) {
          return SizedBox(
            height: 48, // Fixed height
            child: TextField(
              controller: _controller,
              focusNode: _focusNode,
              autofocus: widget.autoFocus,
              decoration: InputDecoration(
                hintText: 'Rechercher boutiques, catégories, produits...',
                hintStyle: TextStyle(color: Colors.grey[400]),
                prefixIcon: const Icon(Icons.search, color: Color(0xFFD4AF37)),
                suffixIcon: _controller.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.close, color: Colors.grey),
                        onPressed: () {
                          _controller.clear();
                          searchProvider.clear();
                          _focusNode.unfocus();
                          widget.onClose?.call();
                        },
                      )
                    : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
                filled: true,
                fillColor: Colors.grey[50],
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
              ),
              onChanged: (value) {
                setState(() {});
                if (value.length >= 2) {
                  searchProvider.search(value);
                  if (_focusNode.hasFocus) {
                    _showOverlay();
                  }
                } else {
                  searchProvider.clear();
                  _removeOverlay();
                }
              },
              onTap: () {
                if (_controller.text.length >= 2) {
                  _showOverlay();
                }
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildCategoryTile(Category category) {
    return ListTile(
      dense: true,
      leading: Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(
          color: Colors.grey[100],
          borderRadius: BorderRadius.circular(6),
          image: category.image != null
              ? DecorationImage(
                  image: CachedNetworkImageProvider(
                    ImageUrl.resolve(category.image),
                  ),
                  fit: BoxFit.cover,
                )
              : null,
        ),
        child: category.image == null
            ? const Icon(Icons.category, color: Color(0xFFD4AF37), size: 16)
            : null,
      ),
      title: Text(
        category.name,
        style: const TextStyle(fontSize: 13),
      ),
      subtitle: const Text('Catégorie', style: TextStyle(fontSize: 11)),
      onTap: () {
        context.push('/products', extra: {'category_id': category.id});
        _focusNode.unfocus();
        _removeOverlay();
      },
    );
  }

  Widget _buildVendorTile(Vendor vendor) {
    return ListTile(
      dense: true,
      leading: CircleAvatar(
        radius: 16,
        backgroundImage: vendor.logo != null && vendor.logo != 'logo'
            ? CachedNetworkImageProvider(
                ImageUrl.resolve(vendor.logo),
              )
            : null,
        child: vendor.logo == null || vendor.logo == 'logo'
            ? const Icon(Icons.store, color: Color(0xFFD4AF37), size: 14)
            : null,
      ),
      title: Text(
        vendor.storeName,
        style: const TextStyle(fontSize: 13),
      ),
      subtitle: Text(
        '${vendor.productsCount ?? 0} produits',
        style: const TextStyle(fontSize: 11),
      ),
      onTap: () {
        context.push('/vendor/${vendor.slug}');
        _focusNode.unfocus();
        _removeOverlay();
      },
    );
  }

  Widget _buildProductTile(Product product) {
    return ListTile(
      dense: true,
      leading: Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(
          color: Colors.grey[100],
          borderRadius: BorderRadius.circular(6),
          image: product.imageUrl != null
              ? DecorationImage(
                  image: CachedNetworkImageProvider(
                    ImageUrl.resolve(product.imageUrl),
                  ),
                  fit: BoxFit.cover,
                )
              : null,
        ),
        child: product.imageUrl == null
            ? const Icon(Icons.image, color: Color(0xFFD4AF37), size: 16)
            : null,
      ),
      title: Text(
        product.name,
        style: const TextStyle(fontSize: 13),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: Text(
        '${product.vendorName} • ${product.formattedPrice}',
        style: const TextStyle(fontSize: 11),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      onTap: () {
        if (product.vendorProductId != null) {
          context.push('/product/${product.slug}/${product.vendorProductId}');
          _focusNode.unfocus();
          _removeOverlay();
        }
      },
    );
  }
}