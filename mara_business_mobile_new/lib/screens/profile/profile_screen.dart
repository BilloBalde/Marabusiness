// lib/screens/profile/profile_screen.dart

import '../../utils/app_logger.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../core/providers/auth_provider.dart';
import '../../core/providers/order_provider.dart';
import '../../core/providers/address_provider.dart';
import '../../core/models/order.dart';
import '../../widgets/common/loading_widget.dart';
import '../../services/api_service.dart';
import '../../utils/currency_formatter.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  String? _error;
  
  // Stats
  int _ordersCount = 0;
  double _totalSpent = 0;
  int _reviewsCount = 0;

  /// Drives the badge on the Messages row. Loaded separately from the profile
  /// so a chat endpoint that fails never keeps the rest of the page from
  /// rendering — an unread count is the least important thing here.
  int _unreadMessages = 0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadUserData();
      _loadUnreadMessages();
    });
  }

  Future<void> _loadUnreadMessages() async {
    try {
      final response = await context.read<ApiService>().getUnreadMessageCount();
      if (!mounted || !response.success) return;

      final payload = response.data is Map ? response.data['data'] : null;
      final count = payload is Map ? payload['unread_count'] : null;

      setState(() {
        _unreadMessages = count is int ? count : int.tryParse('$count') ?? 0;
      });
    } catch (e) {
      // Silent on purpose: a missing badge is not worth an error on a screen
      // whose job is showing the profile.
      logDebug('🔴 Unread message count failed: $e');
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadUserData() async {
    setState(() => _isLoading = true);

    try {
      final authProvider = context.read<AuthProvider>();
      final orderProvider = context.read<OrderProvider>();
      final addressProvider = context.read<AddressProvider>();

      if (authProvider.isAuthenticated) {
        // Load orders
        await orderProvider.loadOrders();
        _ordersCount = orderProvider.orders.length;
        _totalSpent = orderProvider.totalSpent;
        //_totalSpent = orderProvider.orders.fold(0, (sum, order) => sum + order.grandTotalUsd);
        
         // Count total items across all orders (sum of quantities)
        int totalItems = 0;
        for (var order in orderProvider.orders) {
          totalItems += order.items.fold(0, (sum, item) => sum + item.quantity);
        }
        
        // Count reviews from orders
        _reviewsCount = orderProvider.orders.fold(0, (count, order) {
          return count + order.items.where((item) => item.hasReview).length;
        });

        // Load addresses
        addressProvider.loadAddresses();
      }

      setState(() => _isLoading = false);
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = context.watch<AuthProvider>();
    final user = authProvider.user;

    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('Mon Profil'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        // Removed settings icon
        bottom: user != null ? TabBar(
          controller: _tabController,
          indicatorColor: const Color(0xFFD4AF37),
          labelColor: const Color(0xFFD4AF37),
          unselectedLabelColor: Colors.grey,
          tabs: const [
            Tab(text: 'Profil', icon: Icon(Icons.person_outline)),
            Tab(text: 'Commandes', icon: Icon(Icons.shopping_bag_outlined)),
            Tab(text: 'Adresses', icon: Icon(Icons.location_on_outlined)),
          ],
        ) : null,
      ),
      body: user == null
          ? _buildGuestView()
          : TabBarView(
              controller: _tabController,
              children: [
                _buildProfileTab(user),
                _buildOrdersTab(),
                _buildAddressesTab(),
              ],
            ),
    );
  }

  Widget _buildGuestView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.person_outline,
                size: 60,
                color: Color(0xFFD4AF37),
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Bienvenue sur MARA BUSINESS',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F2937),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Connectez-vous pour voir votre profil et vos commandes',
              style: TextStyle(
                color: Color(0xFF6B7280),
                fontSize: 14,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => context.go('/login'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text(
                  'Se Connecter',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: () => context.go('/register'),
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFFD4AF37),
                side: const BorderSide(color: Color(0xFFD4AF37)),
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: const Text(
                'Créer un Compte',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProfileTab(dynamic user) {
    final canEdit = !user.roles.contains('admin') && !user.roles.contains('vendor');
    
    return RefreshIndicator(
      onRefresh: _loadUserData,
      color: const Color(0xFFD4AF37),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Profile Header Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFFD4AF37), Color(0xFFC9A12F)],
              ),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFFD4AF37).withValues(alpha: 0.3),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    // Profile Avatar
                    Container(
                      width: 70,
                      height: 70,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        border: Border.all(color: Colors.white, width: 3),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.1),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: user.avatar != null
                          ? ClipRRect(
                              borderRadius: BorderRadius.circular(35),
                              child: Image.network(
                                user.avatar!,
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stack) => Center(
                                  child: Text(
                                    user.name[0].toUpperCase(),
                                    style: const TextStyle(
                                      fontSize: 28,
                                      fontWeight: FontWeight.bold,
                                      color: Color(0xFFD4AF37),
                                    ),
                                  ),
                                ),
                              ),
                            )
                          : Center(
                              child: Text(
                                user.name[0].toUpperCase(),
                                style: const TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFFD4AF37),
                                ),
                              ),
                            ),
                    ),
                    const SizedBox(width: 16),
                    // User Info
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            user.name,
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            user.email,
                            style: const TextStyle(
                              fontSize: 14,
                              color: Colors.white70,
                            ),
                          ),
                          if (user.phone != null) ...[
                            const SizedBox(height: 4),
                            Text(
                              user.phone!,
                              style: const TextStyle(
                                fontSize: 14,
                                color: Colors.white70,
                              ),
                            ),
                          ],
                          const SizedBox(height: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  user.roles.contains('vendor') ? Icons.store : 
                                  user.roles.contains('admin') ? Icons.admin_panel_settings : 
                                  Icons.person,
                                  size: 14,
                                  color: Colors.white,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  user.roles.contains('vendor') ? 'Vendeur' : 
                                  user.roles.contains('admin') ? 'Administrateur' : 
                                  user.roles.contains('manager') ? 'Manager' :
                                  'Client',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    // Edit Button (only visible if canEdit)
                    if (canEdit)
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.2),
                          shape: BoxShape.circle,
                        ),
                        child: IconButton(
                          icon: const Icon(Icons.edit, color: Colors.white, size: 20),
                          onPressed: () => _navigateToEditProfile(user),
                        ),
                      ),
                  ],
                ),
                
                // Stats Row
                /* const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _buildStatItem(
                      icon: Icons.shopping_bag_outlined,
                      value: _ordersCount.toString(),
                      label: 'Commandes',
                      color: Colors.white,
                    ),
                    Container(
                      height: 30,
                      width: 1,
                      color: Colors.white.withValues(alpha: 0.3),
                    ),
                    _buildStatItem(
                      icon: Icons.monetization_on_outlined,
                      value: CurrencyFormatter.format(_totalSpent, 'GNF', symbol: false),
                      label: 'Dépensé',
                      color: Colors.white,
                    ),
                    Container(
                      height: 30,
                      width: 1,
                      color: Colors.white.withValues(alpha: 0.3),
                    ),/* 
                    _buildStatItem(
                      icon: Icons.reviews_outlined,
                      value: _reviewsCount.toString(),
                      label: 'Avis',
                      color: Colors.white,
                    ), */
                  ],
                ),
               */
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Quick Actions Grid
          Container(
            padding: const EdgeInsets.all(16),
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
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Actions Rapides',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2937),
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _buildQuickAction(
                      icon: Icons.shopping_bag_outlined,
                      label: 'Acheter',
                      color: Colors.blue,
                      onTap: () => context.push('/shops'),
                    ),
                    _buildQuickAction(
                      icon: Icons.history,
                      label: 'Historique',
                      color: Colors.green,
                      onTap: () => _tabController.animateTo(1),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Profile Menu Section - Added Informations Personnelles here
          Container(
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
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                  child: Text(
                    'Mon Compte',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1F2937),
                    ),
                  ),
                ),
                // Informations Personnelles - Always visible, but disabled for admin/vendor
                _buildMenuItem(
                  icon: Icons.person_outline,
                  title: 'Informations Personnelles',
                  subtitle: canEdit ? 'Nom, email, téléphone' : 'Profil protégé - Contactez l\'administrateur',
                  route: canEdit ? null : null, // Don't use route, use onTap
                  enabled: canEdit,
                  onTap: canEdit ? () => _navigateToEditProfile(user) : null, // Add this
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Support Section
          Container(
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
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 16, 16, 8),
                  child: Text(
                    'Support',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF1F2937),
                    ),
                  ),
                ),
                _buildMenuItem(
                  icon: Icons.forum_outlined,
                  title: 'Messages',
                  subtitle: 'Échangez avec vos vendeurs',
                  // The badge is only painted when there is something unread, so
                  // an empty inbox shows a plain row rather than a "0".
                  badge: _unreadMessages > 0 ? '$_unreadMessages' : null,
                  onTap: () => context.push('/messages'),
                ),
                // Sans cette entrée, une négociation ouverte depuis le checkout
                // n'était joignable que par l'écran vers lequel on venait d'être
                // envoyé : quitter l'application, c'était la perdre.
                _buildMenuItem(
                  icon: Icons.handshake_outlined,
                  title: 'Mes négociations',
                  subtitle: 'Vos discussions de prix',
                  onTap: () => context.push('/negotiations'),
                ),
                _buildMenuItem(
                  icon: Icons.help_outline,
                  title: 'Centre d\'Aide',
                  subtitle: 'FAQ et assistance',
                  route: '/help',
                ),
                _buildMenuItem(
                  icon: Icons.description_outlined,
                  title: 'Conditions Générales',
                  subtitle: 'Lire les CGV',
                  route: '/terms',
                ),
                _buildMenuItem(
                  icon: Icons.privacy_tip_outlined,
                  title: 'Politique de Confidentialité',
                  subtitle: 'Protection des données',
                  route: '/privacy',
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Logout Button
          _buildLogoutButton(),
        ],
      ),
    );
  }

  // In profile_screen.dart, update the _navigateToEditProfile method:

  void _navigateToEditProfile(dynamic user) {
    logDebug('🔵 Navigating to edit profile with user: ${user?.name}');
    logDebug('🔵 User ID: ${user?.id}');
    logDebug('🔵 User roles: ${user?.roles}');
    
    if (user == null) {
      logDebug('❌ User is null, cannot navigate to edit profile');
      return;
    }
    
    // Pass the user object as extra
    context.push('/edit-profile', extra: user).then((_) {
      logDebug('🔵 Returning from edit profile screen');
      // Refresh user data when returning from edit screen
      context.read<AuthProvider>().refreshUser();
    });
  }

  Widget _buildStatItem({
    required IconData icon,
    required String value,
    required String label,
    required Color color,
  }) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, color: color.withValues(alpha: 0.9), size: 22),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            label,
            style: TextStyle(
              color: color.withValues(alpha: 0.8),
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickAction({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
    String? badge,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        children: [
          Stack(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color, size: 24),
              ),
              if (badge != null)
                Positioned(
                  top: 0,
                  right: 0,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: const BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                    ),
                    constraints: const BoxConstraints(
                      minWidth: 16,
                      minHeight: 16,
                    ),
                    child: Text(
                      badge,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 8,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }

  Widget _buildVendorSection() {
    return Container(
      padding: const EdgeInsets.all(16),
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Ma Boutique',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1F2937),
                ),
              ),
              TextButton(
                onPressed: () => context.push('/vendor/dashboard'),
                child: const Text('Voir tout'),
              ),
            ],
          ),
          const Divider(),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _buildVendorStat(
                  icon: Icons.inventory_2_outlined,
                  value: '0',
                  label: 'Produits',
                  color: Colors.blue,
                ),
              ),
              Expanded(
                child: _buildVendorStat(
                  icon: Icons.shopping_cart_outlined,
                  value: '0',
                  label: 'Ventes',
                  color: Colors.green,
                ),
              ),
              Expanded(
                child: _buildVendorStat(
                  icon: Icons.star_outline,
                  value: '0.0',
                  label: 'Note',
                  color: Colors.amber,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () => context.push('/vendor/products/add'),
              icon: const Icon(Icons.add, size: 16),
              label: const Text('Ajouter un produit'),
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFFD4AF37),
                side: const BorderSide(color: Color(0xFFD4AF37)),
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildVendorStat({
    required IconData icon,
    required String value,
    required String label,
    required Color color,
  }) {
    return Column(
      children: [
        Icon(icon, color: color, size: 20),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 14,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }

  Widget _buildMenuItem({
  required IconData icon,
  required String title,
  String? subtitle,
  String? route,
  Widget? trailing,
  String? badge,
  bool enabled = true,
  VoidCallback? onTap, // Add this parameter
}) {
  return ListTile(
    leading: Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Icon(icon, color: const Color(0xFFD4AF37), size: 20),
    ),
    title: Text(
      title,
      style: TextStyle(
        fontWeight: FontWeight.w600,
        fontSize: 14,
        color: enabled ? Colors.black87 : Colors.grey,
      ),
    ),
    subtitle: subtitle != null
        ? Text(
            subtitle,
            style: TextStyle(
              fontSize: 12,
              color: enabled ? Colors.grey[600] : Colors.grey[400],
            ),
          )
        : null,
    trailing: badge != null
        ? Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
            decoration: BoxDecoration(
              color: Colors.red,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              badge,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 11,
                fontWeight: FontWeight.bold,
              ),
            ),
          )
        : (trailing ?? (enabled ? const Icon(Icons.chevron_right, color: Colors.grey) : null)),
    onTap: enabled 
        ? (onTap ?? (route != null ? () => context.push(route) : null))
        : null,
  );
}
  Widget _buildLogoutButton() {
    return Container(
      margin: const EdgeInsets.all(16),
      child: ElevatedButton(
        onPressed: _showLogoutDialog,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.white,
          foregroundColor: Colors.red,
          padding: const EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: const BorderSide(color: Colors.red, width: 1),
          ),
        ),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.logout, color: Colors.red),
            SizedBox(width: 8),
            Text(
              'Se Déconnecter',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // In profile_screen.dart, update _buildOrdersTab()

Widget _buildOrdersTab() {
  final orderProvider = context.watch<OrderProvider>();
  
  if (orderProvider.isLoading && orderProvider.orders.isEmpty) {
    return const LoadingWidget(message: 'Chargement des commandes...');
  }

  if (orderProvider.orders.isEmpty) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.shopping_bag_outlined,
            size: 80,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          const Text(
            'Aucune commande',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Vos commandes apparaîtront ici',
            style: TextStyle(color: Colors.grey),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () => context.push('/shops'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFD4AF37),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            ),
            child: const Text('Commencer vos achats'),
          ),
        ],
      ),
    );
  }

  return RefreshIndicator(
    onRefresh: () => orderProvider.loadLatestOrders(),
    color: const Color(0xFFD4AF37),
    child: Column(
      children: [
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: orderProvider.orders.length,
            itemBuilder: (context, index) {
              final order = orderProvider.orders[index];
              return _buildOrderCard(order);
            },
          ),
        ),
        // Voir tout button
        Padding(
          padding: const EdgeInsets.all(16),
          child: OutlinedButton(
            onPressed: () => context.push('/orders'),
            style: OutlinedButton.styleFrom(
              foregroundColor: const Color(0xFFD4AF37),
              side: const BorderSide(color: Color(0xFFD4AF37)),
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text('Voir toutes mes commandes'),
                SizedBox(width: 8),
                Icon(Icons.arrow_forward, size: 16),
              ],
            ),
          ),
        ),
      ],
    ),
  );
}
  Widget _buildOrderCard(Order order) {
    int totalQuantity = order.items.fold(0, (sum, item) => sum + item.quantity);
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        onTap: () => context.push('/orders/${order.id}'),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Commande #${order.orderNumber.length > 8 ? order.orderNumber.substring(0, 8) : order.orderNumber}...',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _getStatusColor(order.paymentStatus).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      _getStatusText(order.paymentStatus),
                      style: TextStyle(
                        color: _getStatusColor(order.paymentStatus),
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                order.vendorName ?? 'Vendeur',
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    '$totalQuantity article${totalQuantity > 1 ? 's' : ''}',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
                  ),
                  Text(
                    CurrencyFormatter.format(order.grandTotal, order.currency),
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFD4AF37),
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 12, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text(
                        _formatDate(order.createdAt),
                        style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                      ),
                    ],
                  ),
                  TextButton(
                    onPressed: () => context.push('/orders/${order.id}'),
                    style: TextButton.styleFrom(
                      minimumSize: Size.zero,
                      padding: const EdgeInsets.symmetric(horizontal: 8),
                    ),
                    child: const Text('Détails'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAddressesTab() {
    final addressProvider = context.watch<AddressProvider>();
    
    if (addressProvider.isLoading) {
      return const LoadingWidget(message: 'Chargement des adresses...');
    }

    if (addressProvider.addresses.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.location_off_outlined,
              size: 80,
              color: Colors.grey[400],
            ),
            const SizedBox(height: 16),
            const Text(
              'Aucune adresse enregistrée',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.grey,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Ajoutez votre première adresse de livraison',
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () => _navigateToAddressForm(),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFD4AF37),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              ),
              child: const Text('Ajouter une adresse'),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => addressProvider.loadAddresses(),
      color: const Color(0xFFD4AF37),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ...addressProvider.addresses.map((address) => _buildAddressCard(address)),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: () => _navigateToAddressForm(),
            icon: const Icon(Icons.add),
            label: const Text('Ajouter une adresse'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFD4AF37),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _navigateToAddressForm([Address? address]) {
    if (address == null) {
      context.push('/addresses/add').then((_) {
        context.read<AddressProvider>().loadAddresses();
      });
    } else {
      context.push('/addresses/edit/${address.id}').then((_) {
        context.read<AddressProvider>().loadAddresses();
      });
    }
  }

  Widget _buildAddressCard(Address address) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
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
                        color: const Color(0xFFD4AF37).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(
                        Icons.location_on,
                        color: Color(0xFFD4AF37),
                        size: 18,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      address.fullName,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                if (address.isDefault)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.green.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Text(
                      'Défaut',
                      style: TextStyle(
                        color: Colors.green,
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              address.streetAddress,
              style: const TextStyle(fontSize: 13),
            ),
            Text(
              '${address.city}, ${address.state} ${address.zipCode}',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
            Text(
              address.country,
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.phone, size: 12, color: Colors.grey),
                const SizedBox(width: 4),
                Text(
                  address.phone,
                  style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                ),
              ],
            ),
            const Divider(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (!address.isDefault)
                  TextButton(
                    onPressed: () {
                      final addressProvider = context.read<AddressProvider>();
                      addressProvider.setDefaultAddress(address.id!).then((_) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Adresse par défaut mise à jour'),
                            backgroundColor: Colors.green,
                          ),
                        );
                      });
                    },
                    child: const Text('Par défaut'),
                  ),
                const SizedBox(width: 8),
                TextButton(
                  onPressed: () => _navigateToAddressForm(address),
                  child: const Text('Modifier'),
                ),
                const SizedBox(width: 8),
                TextButton(
                  onPressed: () => _showDeleteAddressDialog(address.id!),
                  style: TextButton.styleFrom(foregroundColor: Colors.red),
                  child: const Text('Supprimer'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showLogoutDialog() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Êtes-vous sûr de vouloir vous déconnecter ?'),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            style: TextButton.styleFrom(
              foregroundColor: Colors.grey[600],
            ),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: const Text('Déconnexion'),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      await context.read<AuthProvider>().logout();
      if (mounted) {
        context.go('/');
      }
    }
  }

  Future<void> _showDeleteAddressDialog(int addressId) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Supprimer l\'adresse'),
        content: const Text('Êtes-vous sûr de vouloir supprimer cette adresse ?'),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );

    if (confirm == true && mounted) {
      await context.read<AddressProvider>().deleteAddress(addressId);
    }
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'paid':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'partial':
        return Colors.blue;
      case 'failed':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _getStatusText(String status) {
    switch (status) {
      case 'paid':
        return 'Payée';
      case 'pending':
        return 'En attente';
      case 'partial':
        return 'Partielle';
      case 'failed':
        return 'Échouée';
      default:
        return status;
    }
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);

    if (difference.inDays == 0) {
      return 'Aujourd\'hui';
    } else if (difference.inDays == 1) {
      return 'Hier';
    } else if (difference.inDays < 7) {
      return 'Il y a ${difference.inDays} jours';
    } else {
      return '${date.day}/${date.month}/${date.year}';
    }
  }
}