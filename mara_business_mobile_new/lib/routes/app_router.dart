import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../screens/home/home_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/auth/register_screen.dart';
import '../screens/auth/forgot_password_screen.dart';
import '../screens/product/product_detail_screen.dart';
import '../screens/products/products_screen.dart';
import '../screens/cart/cart_screen.dart';
import '../screens/checkout/checkout_screen.dart';
import '../screens/orders/orders_screen.dart';
import '../screens/order_detail/order_detail_screen.dart';
import '../screens/profile/profile_screen.dart';
import '../screens/categories/all_categories_screen.dart';
import '../screens/vendors/all_vendors_screen.dart';
import '../screens/vendor_detail/vendor_detail_screen.dart';
import '../screens/wishlist/wishlist_screen.dart';
import '../screens/search/search_screen.dart';
import '../screens/vendors/shops_screen.dart';
import '../screens/payment/success_page.dart';
import '../screens/payment/cancel_page.dart';
import '../screens/addresses/add_edit_address_screen.dart';
import '../screens/profile/edit_profile_screen.dart';
import '../core/models/user.dart';
import '../screens/faq_screen.dart';
import '../screens/privacy_screen.dart';
import '../screens/terms_screen.dart';
import '../screens/contact/contact_screen.dart';

class AppRouter {
  static final GoRouter router = GoRouter(
    initialLocation: '/',
    routes: [
      GoRoute(
        path: '/',
        name: 'home',
        builder: (context, state) => const HomeScreen(),
      ),
      GoRoute(
        path: '/contact',
        name: 'contact',
        builder: (context, state) => const ContactScreen(),
      ),
      // FAQ
      GoRoute(
        path: '/help',
        name: 'help',
        builder: (context, state) => const FaqScreen(),
      ),

      // Terms & Conditions
      GoRoute(
        path: '/terms',
        name: 'terms',
        builder: (context, state) => const TermsScreen(),
      ),

      // Privacy Policy
      GoRoute(
        path: '/privacy',
        name: 'privacy',
        builder: (context, state) => const PrivacyScreen(),
      ),
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) {
          final redirect = state.uri.queryParameters['redirect'];
          return LoginScreen(redirect: redirect);
        },
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/forgot-password',
        name: 'forgot-password',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/shops',
        name: 'shops',
        builder: (context, state) => ShopsScreen(key: UniqueKey()), // Add UniqueKey
      ),
      GoRoute(
        path: '/products',
        name: 'products',
        builder: (context, state) => ProductsScreen(
          categoryId: int.tryParse(state.uri.queryParameters['category_id'] ?? ''),
          vendorId: int.tryParse(state.uri.queryParameters['vendor_id'] ?? ''),
          featured: state.uri.queryParameters['featured'] == 'true',
          sale: state.uri.queryParameters['sale'] == 'true',
        ),
      ),
      GoRoute(
        path: '/product/:slug/:vendorProductId',
        name: 'product-detail',
        builder: (context, state) {
          final slug = state.pathParameters['slug']!;
          final vendorProductId = int.parse(state.pathParameters['vendorProductId']!);
          return ProductDetailScreen(
            slug: slug,
            vendorProductId: vendorProductId,
          );
        },
      ),
      GoRoute(
        path: '/cart',
        name: 'cart',
        builder: (context, state) => const CartScreen(),
      ),
      GoRoute(
        path: '/checkout',
        name: 'checkout',
        builder: (context, state) {
          final selectedIds = (state.uri.queryParameters['selected_ids'] ?? '')
              .split(',')
              .where((id) => id.isNotEmpty)
              .map((id) => int.parse(id))
              .toList();
          return CheckoutScreen(selectedIds: selectedIds);
        },
      ),
      GoRoute(
        path: '/payment/success',
        name: 'success',
        builder: (context, state) {
          // ✅ FIX: Use uri.queryParameters instead of queryParams
          final orderId = state.uri.queryParameters['order_id'];
          final payId = state.uri.queryParameters['pay_id'];
          return SuccessPage(
            orderId: orderId,
            payId: payId,
          );
        },
      ),
      GoRoute(
        path: '/payment/cancel',
        name: 'cancel',
        builder: (context, state) => const CancelPage(),
      ),
      GoRoute(
        path: '/orders',
        name: 'orders',
        builder: (context, state) => const OrdersScreen(),
      ),
      GoRoute(
        path: '/orders/:orderId',
        name: 'order-detail',
        builder: (context, state) {
          final orderId = int.parse(state.pathParameters['orderId']!);
          return OrderDetailScreen(orderId: orderId);
        },
      ),
      GoRoute(
        path: '/addresses/add',
        name: 'add-address',
        builder: (context, state) => const AddEditAddressScreen(),
      ),
      GoRoute(
        path: '/addresses/edit/:id',
        name: 'edit-address',
        builder: (context, state) {
          final id = int.parse(state.pathParameters['id']!);
          return AddEditAddressScreen(addressId: id);
        },
      ),
      GoRoute(
        path: '/profile',
        name: 'profile',
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: '/categories',
        name: 'categories',
        builder: (context, state) => const AllCategoriesScreen(),
      ),
      GoRoute(
        path: '/vendors',
        name: 'vendors',
        builder: (context, state) => const AllVendorsScreen(),
      ),
      GoRoute(
        path: '/vendor/:slug',
        name: 'vendor-detail',
        builder: (context, state) {
          final slug = state.pathParameters['slug']!;
          return VendorDetailScreen(slug: slug);
        },
      ),
      GoRoute(
        path: '/wishlist',
        name: 'wishlist',
        builder: (context, state) => const WishlistScreen(),
      ),
      GoRoute(
        path: '/search',
        name: 'search',
        builder: (context, state) => const SearchScreen(),
      ),
      GoRoute(
  path: '/edit-profile',
  name: 'edit-profile',
  builder: (context, state) {
    print('🔵 EditProfile route called');
    print('🔵 state.extra: ${state.extra}');
    print('🔵 state.extra type: ${state.extra.runtimeType}');
    
    final user = state.extra;
    if (user == null) {
      print('❌ User is null in route, redirecting to profile');
      return const ProfileScreen();
    }
    
    // Check if it's the right type
    if (user is! User) {
      print('❌ state.extra is not a User, it\'s a ${user.runtimeType}');
      return const ProfileScreen();
    }
    
    print('✅ User found: ${user.name}');
    return EditProfileScreen(user: user);
  },
),
    ],
  );
}