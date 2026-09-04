import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../core/providers/navbar_provider.dart';
import '../core/providers/auth_provider.dart';

class AccountPopupMenu extends StatelessWidget {
  final Widget child;
  final VoidCallback? onLogout;

  const AccountPopupMenu({
    super.key,
    required this.child,
    this.onLogout,
  });

  @override
  Widget build(BuildContext context) {
    return Consumer2<NavbarProvider, AuthProvider>(
      builder: (context, navbarProvider, authProvider, child) {
        final user = navbarProvider.user;
        final isLoggedIn = authProvider.isAuthenticated;

        if (!isLoggedIn || user == null) {
          return GestureDetector(
            onTap: () => Navigator.pushNamed(context, '/login'),
            child: this.child,
          );
        }

        return PopupMenuButton<String>(
          offset: const Offset(0, 40),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          child: this.child,
          onSelected: (value) async {
            switch (value) {
              case 'profile':
                Navigator.pushNamed(context, '/profile');
                break;
              case 'addresses':
                Navigator.pushNamed(context, '/addresses');
                break;
              case 'orders':
                Navigator.pushNamed(context, '/orders');
                break;
              case 'chats':
                final managerId = navbarProvider.managerId;
                if (managerId != null) {
                  Navigator.pushNamed(context, '/chats/$managerId');
                }
                break;
              case 'logout':
                _showLogoutDialog(context, authProvider);
                break;
            }
          },
          itemBuilder: (context) => [
            PopupMenuItem(
              value: 'profile',
              child: Row(
                children: [
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: const Color(0xFFD4AF37).withOpacity(0.1),
                    ),
                    child: const Center(
                      child: Icon(
                        Icons.person,
                        size: 18,
                        color: Color(0xFFD4AF37),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user.name,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                        ),
                        Text(
                          user.role?.toUpperCase() ?? 'CUSTOMER',
                          style: TextStyle(
                            fontSize: 10,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const PopupMenuDivider(),
            const PopupMenuItem(
              value: 'orders',
              child: Row(
                children: [
                  Icon(Icons.shopping_bag_outlined, size: 20),
                  SizedBox(width: 12),
                  Text('My Orders'),
                ],
              ),
            ),
            const PopupMenuItem(
              value: 'addresses',
              child: Row(
                children: [
                  Icon(Icons.location_on_outlined, size: 20),
                  SizedBox(width: 12),
                  Text('My Addresses'),
                ],
              ),
            ),
            if (user.role == 'customer')
              const PopupMenuItem(
                value: 'chats',
                child: Row(
                  children: [
                    Icon(Icons.chat_outlined, size: 20),
                    SizedBox(width: 12),
                    Text('My Chats'),
                  ],
                ),
              ),
            if (user.role == 'vendor' || user.role == 'manager' || user.role == 'admin')
              PopupMenuItem(
                value: 'dashboard',
                child: Row(
                  children: [
                    Icon(
                      Icons.dashboard_outlined,
                      size: 20,
                      color: user.role == 'vendor' 
                          ? Colors.green 
                          : Colors.blue,
                    ),
                    const SizedBox(width: 12),
                    Text(
                      user.role == 'vendor' ? 'Vendor Dashboard' : 'Admin Dashboard',
                    ),
                  ],
                ),
              ),
            const PopupMenuDivider(),
            const PopupMenuItem(
              value: 'logout',
              child: Row(
                children: [
                  Icon(Icons.logout, size: 20, color: Colors.red),
                  SizedBox(width: 12),
                  Text(
                    'Logout',
                    style: TextStyle(color: Colors.red),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }

  void _showLogoutDialog(BuildContext context, AuthProvider authProvider) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              await authProvider.logout();
              if (context.mounted) {
                Navigator.pushReplacementNamed(context, '/');
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            child: const Text('Logout'),
          ),
        ],
      ),
    );
  }
}