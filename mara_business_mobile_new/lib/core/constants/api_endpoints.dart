import 'app_constants.dart';

class ApiEndpoints {
  // Use the apiBaseUrl from AppConstants
  static String get baseUrl => AppConstants.apiBaseUrl;
  
  //Navbar
  static String get navbar => '$baseUrl/navbar';
  static String get switchCurrency => '$baseUrl/navbar/switch-currency';
  static String get switchLanguage => '$baseUrl/navbar/switch-language';
  // Auth
  static String get login => '$baseUrl/auth/login';
  static String get register => '$baseUrl/auth/register';
  static String get logout => '$baseUrl/auth/logout';
  static String get forgotPassword => '$baseUrl/auth/forgot-password';
  static String get resetPassword => '$baseUrl/auth/reset-password';
  static String get editProfile => '$baseUrl/user/profile';
  static String get updateProfile => '$baseUrl/user/profile';
  static String get contact => '$baseUrl/contact';
  // Home
  static String get home => '$baseUrl/home';
  
  // Products
  static String get products => '$baseUrl/products';
  static String productDetail(String slug, int vendorProductId) => 
      '$baseUrl/products/$slug/$vendorProductId';
  static String get searchProducts => '$baseUrl/products';
  static String get productsPage => '$baseUrl/products-page';
  static String productReviews(int vendorProductId) => 
    '$baseUrl/products/reviews/$vendorProductId';
  static String productReview(int reviewId) => 
    '$baseUrl/products/review/$reviewId';
  
  // Categories
  static String get categories => '$baseUrl/categories';

  static String get brands => '$baseUrl/brands';
  static String get featuredBrands => '$baseUrl/brands/featured';
  static String brandDetail(int id) => '$baseUrl/brands/$id';
  static String get searchVendors => '$baseUrl/vendors';
  
  // Vendors
  static String get vendors => '$baseUrl/vendors';
  static String vendorDetail(String slug) => '$baseUrl/vendors/$slug';
  static String getVendorProducts(int vendorId) => '$baseUrl/vendors/$vendorId/products';
  static String getVendorReviews(int vendorId) => '$baseUrl/vendors/$vendorId/reviews';
  static String getVendorStats(int vendorId) => '$baseUrl/vendors/$vendorId/stats';
  static String get featuredVendors => '$baseUrl/vendors/featured';
  static String toggleFollowVendor(int vendorId) => '$baseUrl/vendors/$vendorId/follow';
  static String submitVendorReview(int vendorId) => '$baseUrl/vendors/$vendorId/reviews';
  static String deleteVendorReview(int vendorId) => '$baseUrl/vendors/$vendorId/reviews';
  static String checkVendorFollow(int vendorId) => '$baseUrl/vendors/$vendorId/follow/check';
  static String get currencies => '$baseUrl/currencies';
  static String get submitVendorApplication => '$baseUrl/vendor/apply';
  static String get checkVendorStatus => '$baseUrl/user/vendor-status';
  
  // Cart
  static String get cart => '$baseUrl/cart';
  static String get cartAdd => '$baseUrl/cart/add';
  static String cartUpdate(String cartKey) => '$baseUrl/cart/update/$cartKey';
  static String cartRemove(String cartKey) => '$baseUrl/cart/remove/$cartKey';
  static String get cartClear => '$baseUrl/cart/clear';
  
  // Wishlist
  static String get wishlist => '$baseUrl/wishlist';
  static String wishlistAdd(int vendorProductId) => '$baseUrl/wishlist/add/$vendorProductId';
  static String wishlistRemove(int vendorProductId) => '$baseUrl/wishlist/remove/$vendorProductId';
  static String wishlistRemoveByKey(String wishlistKey) => '$baseUrl/wishlist/remove-key/$wishlistKey';
  static String get wishlistClear => '$baseUrl/wishlist/clear';
  static String get wishlistMoveToCart => '$baseUrl/wishlist/move-to-cart';
  
  // Checkout
  static String get calculateShipping => '$baseUrl/checkout/calculate-shipping';
  static String get placeOrder => '$baseUrl/checkout/place-order';
  
  // Orders
  static String get orders => '$baseUrl/orders';
  static String orderDetail(int orderId) => '$baseUrl/orders/$orderId';
  static String cancel(int orderId) => '$baseUrl/orders/$orderId/cancel';
  static String track(int orderId) => '$baseUrl/orders/$orderId/track';
  static String downloadInvoice(int orderId) => '$baseUrl/orders/$orderId/invoice';
  
  // Addresses
  static String get addresses => '$baseUrl/addresses';
  static String addressDetail(int addressId) => '$baseUrl/addresses/$addressId';
  static String setDefaultAddress(int addressId) => '$baseUrl/addresses/$addressId/default';
  
  // Négociation de prix. Le serveur les adresse par order_id, pas par rfq_id :
  // le prix convenu vit sur la commande, qui est la source de vérité pour le
  // paiement, et le fil de discussion n'en est qu'un accessoire.
  static String get negotiations => '$baseUrl/negotiations';
  static String negotiationDetail(int orderId) => '$baseUrl/negotiations/$orderId';
  static String negotiationMessages(int orderId) => '$baseUrl/negotiations/$orderId/messages';
  static String negotiationAccept(int orderId) => '$baseUrl/negotiations/$orderId/accept';
  static String negotiationRefuse(int orderId) => '$baseUrl/negotiations/$orderId/refuse';
  static String negotiationCancel(int orderId) => '$baseUrl/negotiations/$orderId/cancel';

  // Chat
  static String get chats => '$baseUrl/chats';
  static String chatWith(int userId) => '$baseUrl/chats/$userId';
  static String get sendMessage => '$baseUrl/chats/send';
  // The API has served these two since the chat routes were added; only the
  // first three were ever declared here, and none of the five were called.
  static String get unreadMessages => '$baseUrl/chats/unread/count';
  static String get markMessagesRead => '$baseUrl/chats/mark-read';
}