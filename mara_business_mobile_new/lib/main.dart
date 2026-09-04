import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:app_links/app_links.dart';

// Core
import 'core/constants/app_constants.dart';
import 'services/api_service.dart';
import 'services/storage_service.dart';
import 'core/models/user.dart';

// Providers
import 'core/providers/home_provider.dart';
import 'core/providers/auth_provider.dart';
import 'core/providers/cart_provider.dart';
import 'core/providers/currency_provider.dart';
import 'core/providers/product_provider.dart';
import 'core/providers/navbar_provider.dart';
import 'core/providers/vendor_provider.dart';
import 'core/providers/brand_provider.dart';
import 'core/providers/search_provider.dart';
import 'core/providers/unified_search_provider.dart';
import 'core/providers/vendor_detail_provider.dart';
import 'core/providers/products_page_provider.dart';
import 'core/providers/checkout_provider.dart';
import 'core/providers/order_provider.dart';
import 'core/providers/address_provider.dart';
import 'core/providers/contact_provider.dart';

// Screens
import 'screens/home/home_screen.dart';
import 'screens/auth/login_screen.dart';
import 'screens/auth/register_screen.dart';
import 'screens/auth/forgot_password_screen.dart';
import 'screens/product/product_detail_screen.dart';
import 'screens/products/products_screen.dart';
import 'screens/cart/cart_screen.dart';
import 'screens/checkout/checkout_screen.dart';
import 'screens/orders/orders_screen.dart';
import 'screens/order_detail/order_detail_screen.dart';
import 'screens/profile/profile_screen.dart';
import 'screens/categories/all_categories_screen.dart';
import 'screens/vendors/all_vendors_screen.dart';
import 'screens/vendor_detail/vendor_detail_screen.dart';
import 'screens/services/all_services_screen.dart';
import 'screens/search/search_screen.dart';
import 'screens/addresses/addresses_screen.dart';
import 'screens/vendors/shops_screen.dart';
import 'screens/vendors/vendor_apply_screen.dart';
import 'screens/addresses/add_edit_address_screen.dart';
import 'screens/profile/edit_profile_screen.dart';
import 'screens/terms_screen.dart';
import 'screens/faq_screen.dart';
import 'screens/privacy_screen.dart';
import '../screens/contact/contact_screen.dart';
import 'screens/payment/cancel_page.dart';
import 'screens/payment/success_page.dart';

// Widgets
import 'widgets/bottom_nav_bar.dart';

// Global instance of AppLinks to keep it alive
// final AppLinks _appLinks = AppLinks();

// Router configuration
final GoRouter _router = GoRouter(
  initialLocation: '/',
  // Deep links are handled by app_links, not by router redirect.
  routes: [
    // ShellRoute for screens with bottom navigation
    ShellRoute(
      builder: (context, state, child) {
        int currentIndex = 0;
        final String location = state.uri.path;
        
        if (location == '/categories' || location.startsWith('/categories')) {
          currentIndex = 1;
        } else if (location == '/shops' || location.startsWith('/shops')) {
          currentIndex = 2;
        } else if (location.startsWith('/vendor') || 
                   location.startsWith('/checkout') ||
                   location.startsWith('/product')) {
          currentIndex = 2;
        } else if (location == '/cart' || location.startsWith('/cart')) {
          currentIndex = 3;
        } else if (location == '/profile' || 
                   location == '/addresses' || 
                   location.startsWith('/profile') || 
                   location.startsWith('/addresses')) {
          currentIndex = 4;
        } else if (location == '/login' || 
                  location == '/register' || 
                  location == '/forgot-password') {
          currentIndex = 4;
        }
        
        return MainScaffold(
          currentIndex: currentIndex,
          child: child,
        );
      },
      routes: [
        GoRoute(
          path: '/',
          name: 'home',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: HomeScreen(),
          ),
        ),
        GoRoute(
          path: '/categories',
          name: 'categories',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: AllCategoriesScreen(),
          ),
        ),
        GoRoute(
          path: '/product/:slug/:vendorProductId',
          name: 'product-detail',
          pageBuilder: (context, state) {
            final slug = state.pathParameters['slug']!;
            final vendorProductId = int.parse(state.pathParameters['vendorProductId']!);
            return NoTransitionPage(
              child: ProductDetailScreen(
                slug: slug,
                vendorProductId: vendorProductId,
              ),
            );
          },
        ),
        GoRoute(
          path: '/products',
          name: 'products',
          pageBuilder: (context, state) {
            final categoryId = state.uri.queryParameters['category_id'] != null
                ? int.parse(state.uri.queryParameters['category_id']!)
                : null;
            final vendorId = state.uri.queryParameters['vendor_id'] != null
                ? int.parse(state.uri.queryParameters['vendor_id']!)
                : null;
            final featured = state.uri.queryParameters['featured'] == 'true';
            final sale = state.uri.queryParameters['sale'] == 'true';
            return NoTransitionPage(
              child: ProductsScreen(
                categoryId: categoryId,
                vendorId: vendorId,
                featured: featured,
                sale: sale,
              ),
            );
          },
        ),
        GoRoute(
          path: '/vendor/apply',
          name: 'vendor-apply',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: VendorApplyScreen(),
          ),
        ),
        GoRoute(
          path: '/vendor/:slug',
          name: 'vendor-detail',
          pageBuilder: (context, state) {
            final slug = state.pathParameters['slug']!;
            return NoTransitionPage(
              child: VendorDetailScreen(slug: slug),
            );
          },
        ),
        GoRoute(
          path: '/checkout',
          name: 'checkout',
          pageBuilder: (context, state) {
            final selectedIds = (state.uri.queryParameters['selected_ids'] ?? '')
                .split(',')
                .where((id) => id.isNotEmpty)
                .map((id) => int.parse(id))
                .toList();
            return NoTransitionPage(
              child: CheckoutScreen(selectedIds: selectedIds),
            );
          },
        ),
        GoRoute(
          path: '/vendors',
          name: 'vendors',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: AllVendorsScreen(),
          ),
        ),
        GoRoute(
          path: '/shops',
          name: 'shops',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: ShopsScreen(),
          ),
        ),
        GoRoute(
          path: '/cart',
          name: 'cart',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: CartScreen(),
          ),
        ),
        GoRoute(
          path: '/profile',
          name: 'profile',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: ProfileScreen(),
          ),
        ),
        GoRoute(
          path: '/addresses/add',
          name: 'add-address',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: AddEditAddressScreen(),
          ),
        ),
        GoRoute(
          path: '/addresses/edit/:id',
          name: 'edit-address',
          pageBuilder: (context, state) {
            final id = int.parse(state.pathParameters['id']!);
            return NoTransitionPage(
              child: AddEditAddressScreen(addressId: id),
            );
          },
        ),
        GoRoute(
          path: '/addresses',
          name: 'addresses',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: AddressesScreen(),
          ),
        ),
        GoRoute(
          path: '/login',
          name: 'login',
          pageBuilder: (context, state) {
            final redirect = state.uri.queryParameters['redirect'];
            return NoTransitionPage(
              child: LoginScreen(redirect: redirect),
            );
          },
        ),
        GoRoute(
          path: '/edit-profile',
          name: 'edit-profile',
          pageBuilder: (context, state) {
            final user = state.extra;
            if (user == null || user is! User) {
              return const NoTransitionPage(child: ProfileScreen());
            }
            return NoTransitionPage(
              child: EditProfileScreen(user: user),
            );
          },
        ),
        GoRoute(
          path: '/help',
          name: 'help',
          builder: (context, state) => const FaqScreen(),
        ),
        GoRoute(
          path: '/terms',
          name: 'terms',
          builder: (context, state) => const TermsScreen(),
        ),
        GoRoute(
          path: '/privacy',
          name: 'privacy',
          builder: (context, state) => const PrivacyScreen(),
        ),
        GoRoute(
          path: '/register',
          name: 'register',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: RegisterScreen(),
          ),
        ),
        GoRoute(
          path: '/forgot-password',
          name: 'forgot-password',
          pageBuilder: (context, state) => const NoTransitionPage(
            child: ForgotPasswordScreen(),
          ),
        ),
        GoRoute(
          path: '/contact',
          name: 'contact',
          builder: (context, state) => const ContactScreen(),
        ),
      ],
    ),
    
    // Routes without bottom navigation
    GoRoute(
      path: '/orders',
      name: 'orders',
      pageBuilder: (context, state) => const NoTransitionPage(
        child: OrdersScreen(),
      ),
    ),
    GoRoute(
      path: '/orders/:orderId',
      name: 'order-detail',
      pageBuilder: (context, state) {
        final orderId = int.parse(state.pathParameters['orderId']!);
        return NoTransitionPage(
          child: OrderDetailScreen(orderId: orderId),
        );
      },
    ),
    GoRoute(
      path: '/services',
      name: 'services',
      pageBuilder: (context, state) => const NoTransitionPage(
        child: AllServicesScreen(),
      ),
    ),
    GoRoute(
      path: '/service/:slug',
      name: 'service-detail',
      pageBuilder: (context, state) {
        final slug = state.pathParameters['slug']!;
        return NoTransitionPage(
          child: Scaffold(
            appBar: AppBar(title: Text('Service: $slug')),
            body: const Center(child: Text('Service Detail Screen - Coming Soon')),
          ),
        );
      },
    ),
    GoRoute(
      path: '/search',
      name: 'search',
      pageBuilder: (context, state) => const NoTransitionPage(
        child: SearchScreen(),
      ),
    ),
    GoRoute(
      path: '/payment/success',
      name: 'payment-success',
      pageBuilder: (context, state) {
        final orderId = state.uri.queryParameters['order_id'];
        final payId = state.uri.queryParameters['pay_id'];
        return NoTransitionPage(
          child: SuccessPage(
            orderId: orderId,
            payId: payId,
          ),
        );
      },
    ),
    GoRoute(
      path: '/payment/cancel',
      name: 'payment-cancel',
      pageBuilder: (context, state) => const NoTransitionPage(
        child: CancelPage(),
      ),
    ),
  ],
);

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize storage and API
  final storageService = await StorageService.init();
  final apiService = ApiService(baseUrl: AppConstants.baseUrl);
  
  final token = await storageService.getAuthToken();
  if (token != null) {
    apiService.setToken(token);
  }
  apiService.setCurrency(storageService.getCurrency());

  // Set up deep link listeners before runApp
  _setupDeepLinkListeners();

  runApp(MyApp(
    storageService: storageService,
    apiService: apiService,
  ));
}
void _setupDeepLinkListeners() {
  final appLinks = AppLinks();

  // Listen for links while the app is running (non-null Uri now)
  appLinks.uriLinkStream.listen((Uri uri) {
    if (uri.host == 'afrobridgeinnov.com' && uri.path == '/payment/success') {
      final orderId = uri.queryParameters['order_id'];
      _router.go('/payment/success?order_id=$orderId');
    }
  }, onError: (error) {
    debugPrint('Deep link stream error: $error');
  });

  // Handle the link that started the app (returns Future<Uri?>)
  appLinks.getInitialLink().then((Uri? initialUri) {
    if (initialUri == null) return;
    if (initialUri.host == 'afrobridgeinnov.com' && initialUri.path == '/payment/success') {
      final orderId = initialUri.queryParameters['order_id'];
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _router.go('/payment/success?order_id=$orderId');
      });
    }
  }).catchError((e) {
    debugPrint('Error getting initial link: $e');
  });
}
class MyApp extends StatelessWidget {
  final StorageService storageService;
  final ApiService apiService;

  const MyApp({
    super.key,
    required this.storageService,
    required this.apiService,
  });

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<ApiService>.value(value: apiService),
        ChangeNotifierProvider(create: (_) => NavbarProvider(apiService)),
        ChangeNotifierProvider(create: (context) => ContactProvider(context.read<ApiService>())),
        ChangeNotifierProvider(create: (_) => VendorDetailProvider(apiService)),
        ChangeNotifierProvider(create: (_) => UnifiedSearchProvider(apiService)),
        ChangeNotifierProvider(create: (_) => VendorProvider(apiService)),
        ChangeNotifierProvider(create: (_) => SearchProvider(apiService)),
        ChangeNotifierProvider(create: (_) => BrandProvider(apiService)),
        ChangeNotifierProvider(create: (context) => OrderProvider(context.read<ApiService>())),
        ChangeNotifierProvider(create: (context) => AddressProvider(context.read<ApiService>())),
        ChangeNotifierProvider(create: (_) => AuthProvider(apiService, storageService)),
        ChangeNotifierProvider(create: (_) => CurrencyProvider(apiService, storageService)),
        ChangeNotifierProvider(create: (_) => CartProvider(apiService)),
        ChangeNotifierProvider(create: (_) => HomeProvider(apiService)),
        ChangeNotifierProvider(create: (_) => ProductProvider(apiService)),
        ChangeNotifierProvider(
          create: (context) => ProductsPageProvider(context.read<ApiService>()),
        ),
        ChangeNotifierProvider(
          create: (context) => CheckoutProvider(
            apiService: context.read<ApiService>(),
            authProvider: context.read<AuthProvider>(),
            cartProvider: context.read<CartProvider>(),
          ),
        ),
      ],
      child: Consumer<AuthProvider>(
        builder: (context, authProvider, child) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            AuthProvider.setContext(context);
          });
          return MaterialApp.router(
            title: AppConstants.appName,
            debugShowCheckedModeBanner: false,
            routerConfig: _router,
            theme: ThemeData(
              primarySwatch: Colors.blue,
              colorScheme: const ColorScheme.light(
                primary: Color(0xFFD4AF37),
                secondary: Color(0xFFD4AF37),
              ),
              appBarTheme: const AppBarTheme(
                elevation: 0,
                centerTitle: true,
                backgroundColor: Colors.white,
                foregroundColor: Colors.black,
              ),
              elevatedButtonTheme: ElevatedButtonThemeData(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              ),
              textButtonTheme: TextButtonThemeData(
                style: TextButton.styleFrom(
                  foregroundColor: const Color(0xFFD4AF37),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class MainScaffold extends StatefulWidget {
  final Widget child;
  final int currentIndex;

  const MainScaffold({
    super.key,
    required this.child,
    required this.currentIndex,
  });

  @override
  State<MainScaffold> createState() => _MainScaffoldState();
}

class _MainScaffoldState extends State<MainScaffold> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NavbarProvider>().loadNavbarData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: widget.child,
      bottomNavigationBar: BottomNavBar(
        currentIndex: widget.currentIndex,
        onTap: (index) {
          switch (index) {
            case 0:
              context.go('/');
              break;
            case 1:
              context.go('/categories');
              break;
            case 2:
              context.go('/shops');
              break;
            case 3:
              context.go('/cart');
              break;
            case 4:
              final authProvider = context.read<AuthProvider>();
              if (authProvider.isAuthenticated) {
                context.go('/profile');
              } else {
                context.go('/login');
              }
              break;
          }
        },
      ),
    );
  }
}