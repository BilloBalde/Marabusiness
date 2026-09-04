import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/providers/home_provider.dart';

class AllServicesScreen extends StatelessWidget {
  const AllServicesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final homeProvider = context.watch<HomeProvider>();
    
    return Scaffold(
      appBar: AppBar(
        title: const Text('All Services'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
      ),
      body: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          childAspectRatio: 0.9,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
        ),
        itemCount: homeProvider.services.length,
        itemBuilder: (context, index) {
          final service = homeProvider.services[index];
          return GestureDetector(
            onTap: () => Navigator.pushNamed(
              context,
              '/service/${service.slug}',
            ),
            child: Container(
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
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 60,
                    height: 60,
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [Color(0xFFD4AF37), Color(0xFFC9A12F)],
                      ),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      service.icon != null
                          ? _getIconData(service.icon!)
                          : Icons.support_agent, // Changed from Icons.concierge to Icons.support_agent
                      color: Colors.white,
                      size: 30,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    service.name,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 4),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    child: Text(
                      service.shortDescription ?? 'Premium service offering',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
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
        },
      ),
    );
  }

  // Helper method to convert FontAwesome icon names to Flutter Icons
  IconData _getIconData(String iconName) {
    switch (iconName) {
      case 'fa-truck':
        return Icons.local_shipping;
      case 'fa-shield':
        return Icons.shield;
      case 'fa-clock':
        return Icons.access_time;
      case 'fa-phone':
        return Icons.phone;
      case 'fa-gift':
        return Icons.card_giftcard;
      case 'fa-star':
        return Icons.star;
      case 'fa-heart':
        return Icons.favorite;
      case 'fa-shopping-cart':
        return Icons.shopping_cart;
      case 'fa-user':
        return Icons.person;
      case 'fa-store':
        return Icons.store;
      case 'fa-tag':
        return Icons.local_offer;
      case 'fa-percent':
        return Icons.percent;
      case 'fa-money-bill':
        return Icons.money;
      case 'fa-credit-card':
        return Icons.credit_card;
      case 'fa-wallet':
        return Icons.account_balance_wallet;
      case 'fa-map-marker':
        return Icons.location_on;
      case 'fa-envelope':
        return Icons.email;
      case 'fa-lock':
        return Icons.lock;
      case 'fa-globe':
        return Icons.public;
      case 'fa-cog':
      case 'fa-gear':
        return Icons.settings;
      case 'fa-bell':
        return Icons.notifications;
      case 'fa-search':
        return Icons.search;
      case 'fa-filter':
        return Icons.filter_list;
      case 'fa-sort':
        return Icons.sort;
      case 'fa-print':
        return Icons.print;
      case 'fa-download':
        return Icons.download;
      case 'fa-upload':
        return Icons.upload;
      case 'fa-trash':
        return Icons.delete;
      case 'fa-edit':
        return Icons.edit;
      case 'fa-plus':
        return Icons.add;
      case 'fa-minus':
        return Icons.remove;
      case 'fa-check':
        return Icons.check;
      case 'fa-times':
        return Icons.close;
      case 'fa-info':
        return Icons.info;
      case 'fa-question':
        return Icons.help;
      case 'fa-exclamation':
        return Icons.warning;
      case 'fa-home':
        return Icons.home;
      case 'fa-building':
        return Icons.business;
      case 'fa-company':
        return Icons.business_center;
      case 'fa-briefcase':
        return Icons.work;
      case 'fa-file':
        return Icons.description;
      case 'fa-folder':
        return Icons.folder;
      case 'fa-image':
        return Icons.image;
      case 'fa-camera':
        return Icons.camera_alt;
      case 'fa-video':
        return Icons.videocam;
      case 'fa-music':
        return Icons.music_note;
      case 'fa-gamepad':
        return Icons.sports_esports;
      case 'fa-book':
        return Icons.book;
      case 'fa-graduation-cap':
        return Icons.school;
      case 'fa-chart-line':
        return Icons.show_chart;
      case 'fa-chart-bar':
        return Icons.bar_chart;
      case 'fa-chart-pie':
        return Icons.pie_chart;
      default:
        return Icons.support_agent; // Changed from Icons.concierge to Icons.support_agent
    }
  }
}