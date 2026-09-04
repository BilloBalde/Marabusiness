import 'package:flutter/material.dart';

class RatingBar extends StatelessWidget {
  final double rating;
  final double size;
  final Color activeColor;
  final Color inactiveColor;
  final bool showNumber;

  const RatingBar({
    super.key,
    required this.rating,
    this.size = 16,
    this.activeColor = Colors.amber,
    this.inactiveColor = Colors.grey,
    this.showNumber = false,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        ...List.generate(5, (index) {
          final starIndex = index + 1;
          if (starIndex <= rating) {
            // Full star
            return Icon(
              Icons.star,
              size: size,
              color: activeColor,
            );
          } else if (starIndex - rating < 1 && starIndex - rating > 0) {
            // Half star (if rating has decimal)
            return Icon(
              Icons.star_half,
              size: size,
              color: activeColor,
            );
          } else {
            // Empty star
            return Icon(
              Icons.star_border,
              size: size,
              color: inactiveColor,
            );
          }
        }),
        if (showNumber) ...[
          const SizedBox(width: 4),
          Text(
            rating.toStringAsFixed(1),
            style: TextStyle(
              fontSize: size * 0.8,
              fontWeight: FontWeight.bold,
              color: Colors.grey[700],
            ),
          ),
        ],
      ],
    );
  }
}

// Interactive rating bar for user input
class InteractiveRatingBar extends StatefulWidget {
  final double initialRating;
  final double size;
  final ValueChanged<double> onRatingChanged;

  const InteractiveRatingBar({
    super.key,
    this.initialRating = 0,
    this.size = 32,
    required this.onRatingChanged,
  });

  @override
  State<InteractiveRatingBar> createState() => _InteractiveRatingBarState();
}

class _InteractiveRatingBarState extends State<InteractiveRatingBar> {
  late double _rating;

  @override
  void initState() {
    super.initState();
    _rating = widget.initialRating;
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (index) {
        final starIndex = index + 1;
        return GestureDetector(
          onTap: () {
            setState(() {
              _rating = starIndex.toDouble();
              widget.onRatingChanged(_rating);
            });
          },
          child: Padding(
            padding: const EdgeInsets.only(right: 4),
            child: Icon(
              starIndex <= _rating ? Icons.star : Icons.star_border,
              color: starIndex <= _rating ? Colors.amber : Colors.grey,
              size: widget.size,
            ),
          ),
        );
      }),
    );
  }
}

// Compact rating display with count
class RatingDisplay extends StatelessWidget {
  final double rating;
  final int reviewCount;
  final double size;

  const RatingDisplay({
    super.key,
    required this.rating,
    required this.reviewCount,
    this.size = 14,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        RatingBar(
          rating: rating,
          size: size,
        ),
        const SizedBox(width: 8),
        Text(
          '$rating',
          style: TextStyle(
            fontSize: size,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(
          ' ($reviewCount reviews)',
          style: TextStyle(
            fontSize: size * 0.9,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }
}

// Average rating with progress bars
class DetailedRatingBar extends StatelessWidget {
  final double averageRating;
  final Map<int, int> ratingCounts; // Map of star count to number of reviews
  final int totalReviews;

  const DetailedRatingBar({
    super.key,
    required this.averageRating,
    required this.ratingCounts,
    required this.totalReviews,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              averageRating.toStringAsFixed(1),
              style: const TextStyle(
                fontSize: 48,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                RatingBar(
                  rating: averageRating,
                  size: 20,
                ),
                const SizedBox(height: 4),
                Text(
                  '$totalReviews reviews',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
          ],
        ),
        const SizedBox(height: 16),
        ...List.generate(5, (index) {
          final starCount = 5 - index;
          final count = ratingCounts[starCount] ?? 0;
          final percentage = totalReviews > 0 ? (count / totalReviews) : 0.0;

          return Padding(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Row(
              children: [
                SizedBox(
                  width: 40,
                  child: Text(
                    '$starCount ★',
                    style: const TextStyle(fontSize: 12),
                  ),
                ),
                Expanded(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: percentage,
                      backgroundColor: Colors.grey[300],
                      valueColor: const AlwaysStoppedAnimation<Color>(Colors.amber),
                      minHeight: 8,
                    ),
                  ),
                ),
                SizedBox(
                  width: 40,
                  child: Text(
                    '$count',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.right,
                  ),
                ),
              ],
            ),
          );
        }),
      ],
    );
  }
}