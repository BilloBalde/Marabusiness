import 'package:flutter/material.dart';
import '../core/models/home_models.dart';

class ServiceCard extends StatelessWidget {
  final Service service;
  final VoidCallback onTap;

  const ServiceCard({
    super.key,
    required this.service,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 160,
        height: 180, // Fixed height
        margin: const EdgeInsets.only(right: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.1),
              spreadRadius: 1,
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Icon/Image Container - Fixed height
            Container(
              height: 100,
              width: double.infinity,
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [Color(0xFFD4AF37), Color(0xFFC9A12F)],
                ),
                borderRadius: BorderRadius.vertical(
                  top: Radius.circular(12),
                ),
              ),
              child: Center(
                child: service.icon != null
                    ? Icon(
                        _getIconData(service.icon!),
                        color: Colors.white,
                        size: 40,
                      )
                    : const Icon(
                        Icons.support_agent,
                        color: Colors.white,
                        size: 40,
                      ),
              ),
            ),
            
            // Text Container - Fixed height to prevent overflow
            Container(
              height: 70, // Fixed height for text area
              padding: const EdgeInsets.all(8),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title with max 1 line
                  Text(
                    service.name,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  // Description with max 2 lines
                  Expanded(
                    child: Text(
                      service.shortDescription ?? 'Premium service offering',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey[600],
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
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

  IconData _getIconData(String iconName) {
    // Handle Font Awesome style icons
    if (iconName.startsWith('fas') || iconName.startsWith('fa-')) {
      switch (iconName) {
        case 'fas fa-briefcase':
        case 'fa-briefcase':
          return Icons.business_center;
        case 'fas fa-shipping-fast':
        case 'fa-shipping-fast':
          return Icons.local_shipping;
        case 'fas fa-chart-line':
        case 'fa-chart-line':
          return Icons.show_chart;
        case 'fas fa-credit-card':
        case 'fa-credit-card':
          return Icons.credit_card;
        case 'fas fa-headset':
        case 'fa-headset':
          return Icons.headset;
        default:
          return Icons.support_agent;
      }
    }
    
    // Handle regular icon names
    switch (iconName) {
      case 'fa-truck':
        return Icons.local_shipping;
      case 'fa-shield':
        return Icons.shield;
      case 'fa-clock':
        return Icons.access_time;
      case 'fa-phone':
        return Icons.phone;
      default:
        return Icons.support_agent;
    }
  }
}