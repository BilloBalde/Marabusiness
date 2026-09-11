// lib/widgets/review_list_screen.dart - FIXED

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/constants/app_constants.dart';
import '../../core/models/product_detail_models.dart'; // IMPORT THE REVIEW MODEL
import '../../widgets/rating_bar.dart';

class ReviewListScreen extends StatefulWidget {
  final int productId;
  final String productName;
  final List<Review> initialReviews; // Change from List<dynamic> to List<Review>

  const ReviewListScreen({
    super.key,
    required this.productId,
    required this.productName,
    required this.initialReviews,
  });

  @override
  State<ReviewListScreen> createState() => _ReviewListScreenState();
}

class _ReviewListScreenState extends State<ReviewListScreen> {
  List<Review> _reviews = []; // Change from List<dynamic> to List<Review>
  bool _isLoading = false;
  bool _hasMore = true;
  final int _page = 1;
  final int _perPage = 10;

  // Filters
  String _selectedFilter = 'recent'; // recent, highest, lowest, with_photos
  bool _showFilterMenu = false;

  // Statistics
  double _averageRating = 0;
  int _totalReviews = 0;
  Map<int, int> _ratingCounts = {
    5: 0,
    4: 0,
    3: 0,
    2: 0,
    1: 0,
  };

  @override
  void initState() {
    super.initState();
    _initializeReviews();
  }

  void _initializeReviews() {
    _reviews = List.from(widget.initialReviews);
    _calculateStats();
  }

  void _calculateStats() {
    if (_reviews.isEmpty) return;

    double sum = 0;
    final counts = {5: 0, 4: 0, 3: 0, 2: 0, 1: 0};

    for (var review in _reviews) {
      final rating = review.rating; // FIXED: Use property, not Map indexing
      sum += rating;
      counts[rating] = (counts[rating] ?? 0) + 1;
    }

    setState(() {
      _averageRating = sum / _reviews.length;
      _totalReviews = _reviews.length;
      _ratingCounts = counts;
    });
  }

  Future<void> _loadMoreReviews() async {
    if (_isLoading || !_hasMore) return;

    setState(() => _isLoading = true);

    // TODO: Implement actual API call to load more reviews
    // Simulating API delay
    await Future.delayed(const Duration(seconds: 1));

    // Mock data - replace with actual API call
    final mockReviews = List.generate(5, (index) {
      final i = _reviews.length + index + 1;
      return Review(
        id: i,
        userId: i,
        userName: 'User $i',
        userAvatar: null,
        rating: (i % 5) + 1,
        comment: 'This is a sample review number $i. The product quality is excellent and shipping was fast.',
        createdAt: DateTime.now().subtract(Duration(days: i)).toIso8601String(),
        createdAtHuman: '$i days ago',
        verifiedPurchase: i % 2 == 0,
      );
    });

    setState(() {
      _reviews.addAll(mockReviews);
      _isLoading = false;
      _hasMore = _reviews.length < 30; // Just for demo
      _calculateStats();
    });
  }

  void _applyFilter(String filter) {
    setState(() {
      _selectedFilter = filter;
      _showFilterMenu = false;
      // TODO: Implement actual filter logic with API call
      // For now, just resort the existing reviews
      switch (filter) {
        case 'recent':
          _reviews.sort((a, b) => b.createdAt!.compareTo(a.createdAt!));
          break;
        case 'highest':
          _reviews.sort((a, b) => b.rating.compareTo(a.rating));
          break;
        case 'lowest':
          _reviews.sort((a, b) => a.rating.compareTo(b.rating));
          break;
        case 'with_photos':
          // For now, just show all since we don't have photos in the model
          break;
      }
    });
  }

  String _getFilterLabel(String filter) {
    switch (filter) {
      case 'recent': return 'Plus récents';
      case 'highest': return 'Mieux notés';
      case 'lowest': return 'Moins bien notés';
      case 'with_photos': return 'Avec photos';
      default: return 'Filtrer';
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      final now = DateTime.now();
      final difference = now.difference(date);

      if (difference.inDays > 365) {
        return 'il y a ${(difference.inDays / 365).floor()} ans';
      } else if (difference.inDays > 30) {
        return 'il y a ${(difference.inDays / 30).floor()} mois';
      } else if (difference.inDays > 0) {
        return 'il y a ${difference.inDays} jours';
      } else if (difference.inHours > 0) {
        return 'il y a ${difference.inHours} heures';
      } else if (difference.inMinutes > 0) {
        return 'il y a ${difference.inMinutes} minutes';
      } else {
        return 'À l\'instant';
      }
    } catch (e) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final isLoggedIn = authProvider.isAuthenticated;

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: Text(
          'Avis - ${widget.productName}',
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          // Filter button
          Stack(
            children: [
              IconButton(
                icon: const Icon(Icons.filter_list, color: Color(0xFFD4AF37)),
                onPressed: () {
                  setState(() => _showFilterMenu = !_showFilterMenu);
                },
              ),
              if (_selectedFilter != 'recent')
                Positioned(
                  right: 8,
                  top: 8,
                  child: Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          // Filter menu dropdown
          if (_showFilterMenu)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withValues(alpha: 0.1),
                    blurRadius: 8,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                children: [
                  _buildFilterOption('recent', 'Plus récents', Icons.access_time),
                  _buildFilterOption('highest', 'Mieux notés', Icons.star),
                  _buildFilterOption('lowest', 'Moins bien notés', Icons.star_border),
                  _buildFilterOption('with_photos', 'Avec photos', Icons.photo_camera),
                ],
              ),
            ),

          // Statistics summary
          Container(
            margin: const EdgeInsets.all(16),
            padding: const EdgeInsets.all(20),
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
            child: Row(
              children: [
                // Average rating
                Expanded(
                  flex: 2,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Text(
                        _averageRating.toStringAsFixed(1),
                        style: const TextStyle(
                          fontSize: 48,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFFD4AF37),
                        ),
                      ),
                      const SizedBox(height: 4),
                      RatingBar(
                        rating: _averageRating,
                        size: 20,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '$_totalReviews avis',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 16),
                // Rating distribution
                Expanded(
                  flex: 3,
                  child: Column(
                    children: List.generate(5, (index) {
                      final star = 5 - index;
                      final count = _ratingCounts[star] ?? 0;
                      final percentage = _totalReviews > 0 ? (count / _totalReviews * 100) : 0;
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 4),
                        child: Row(
                          children: [
                            SizedBox(
                              width: 40,
                              child: Text(
                                '$star étoile',
                                style: const TextStyle(fontSize: 12),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(2),
                                child: LinearProgressIndicator(
                                  value: percentage / 100,
                                  backgroundColor: Colors.grey[200],
                                  valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFFD4AF37)),
                                  minHeight: 6,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            SizedBox(
                              width: 30,
                              child: Text(
                                count.toString(),
                                style: const TextStyle(fontSize: 12),
                              ),
                            ),
                          ],
                        ),
                      );
                    }),
                  ),
                ),
              ],
            ),
          ),

          // Review form for logged in users (optional)
          if (isLoggedIn)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.blue[200]!),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.blue[100],
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.edit,
                      color: Colors.blue,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Vous avez acheté ce produit ?',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Partagez votre expérience',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.pop(context);
                      // Scroll to review form on product detail page
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFD4AF37),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(20),
                      ),
                    ),
                    child: const Text('Écrire un avis'),
                  ),
                ],
              ),
            ),

          const SizedBox(height: 8),

          // Current filter indicator
          if (_selectedFilter != 'recent')
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  Chip(
                    label: Text('Filtre: ${_getFilterLabel(_selectedFilter)}'),
                    deleteIcon: const Icon(Icons.close, size: 16),
                    onDeleted: () => _applyFilter('recent'),
                    backgroundColor: const Color(0xFFD4AF37).withValues(alpha: 0.1),
                    deleteIconColor: const Color(0xFFD4AF37),
                  ),
                ],
              ),
            ),

          const SizedBox(height: 8),

          // Reviews list
          Expanded(
            child: _reviews.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.rate_review, size: 64, color: Colors.grey[400]),
                        const SizedBox(height: 16),
                        Text(
                          'Aucun avis pour le moment',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Soyez le premier à donner votre avis !',
                          style: TextStyle(color: Colors.grey[500]),
                        ),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _reviews.length + (_hasMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index == _reviews.length) {
                        return _buildLoadMoreIndicator();
                      }
                      final review = _reviews[index];
                      return _buildReviewCard(review);
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterOption(String value, String label, IconData icon) {
    return InkWell(
      onTap: () => _applyFilter(value),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          border: Border(
            bottom: BorderSide(color: Colors.grey[200]!),
          ),
        ),
        child: Row(
          children: [
            Icon(
              icon,
              size: 20,
              color: _selectedFilter == value ? const Color(0xFFD4AF37) : Colors.grey[600],
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                label,
                style: TextStyle(
                  color: _selectedFilter == value ? const Color(0xFFD4AF37) : Colors.black87,
                  fontWeight: _selectedFilter == value ? FontWeight.bold : FontWeight.normal,
                ),
              ),
            ),
            if (_selectedFilter == value)
              const Icon(
                Icons.check,
                color: Color(0xFFD4AF37),
                size: 20,
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildReviewCard(Review review) {
    final rating = review.rating;
    final userName = review.userName;
    final comment = review.comment;
    final createdAt = review.createdAtHuman ?? _formatDate(review.createdAt ?? '');
    final userAvatar = review.userAvatar;
    final verifiedPurchase = review.verifiedPurchase;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withValues(alpha: 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // User info and rating
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Avatar
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: Colors.grey[200],
                  shape: BoxShape.circle,
                  image: userAvatar != null
                      ? DecorationImage(
                          image: CachedNetworkImageProvider(
                            userAvatar.startsWith('http')
                                ? userAvatar
                                : '${AppConstants.baseUrl}/uploads/$userAvatar',
                          ),
                          fit: BoxFit.cover,
                        )
                      : null,
                ),
                child: userAvatar == null
                    ? Center(
                        child: Text(
                          userName.isNotEmpty ? userName[0].toUpperCase() : '?',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                      )
                    : null,
              ),
              const SizedBox(width: 12),
              // Name and date
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      userName,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Text(
                          createdAt,
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey[500],
                          ),
                        ),
                        if (verifiedPurchase) ...[
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.green[50],
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.check_circle,
                                  size: 10,
                                  color: Colors.green[600],
                                ),
                                const SizedBox(width: 2),
                                Text(
                                  'Achat vérifié',
                                  style: TextStyle(
                                    fontSize: 8,
                                    color: Colors.green[600],
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              // Rating
              Row(
                children: List.generate(5, (index) {
                  return Icon(
                    index < rating ? Icons.star : Icons.star_border,
                    color: Colors.amber,
                    size: 14,
                  );
                }),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Comment
          Text(
            comment,
            style: const TextStyle(fontSize: 13, height: 1.4),
          ),
        ],
      ),
    );
  }

  Widget _buildLoadMoreIndicator() {
    if (!_hasMore) return const SizedBox();

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      child: Center(
        child: _isLoading
            ? const CircularProgressIndicator(color: Color(0xFFD4AF37))
            : TextButton(
                onPressed: _loadMoreReviews,
                child: const Text('Charger plus d\'avis'),
              ),
      ),
    );
  }
}