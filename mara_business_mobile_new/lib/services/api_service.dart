import 'package:dio/dio.dart';
import '../core/models/api_response.dart';
import '../core/constants/api_endpoints.dart';
import 'dart:io';

class ApiService {
  late final Dio _dio;
  final String baseUrl;
  String? _token;
  String _currency = 'USD';

  ApiService({required this.baseUrl}) {
  _dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    connectTimeout: const Duration(seconds: 30),
    receiveTimeout: const Duration(seconds: 30),
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    // This is crucial for web to send cookies
    extra: {'withCredentials': true},
  ));

  // Add an interceptor to ensure credentials are included
  _dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) {
      // Ensure credentials are included for web
      options.extra['withCredentials'] = true;
      
      if (_token != null) {
        options.headers['Authorization'] = 'Bearer $_token';
      }
      options.headers['Currency'] = _currency;
      
      //print('🌐 REQUEST: ${options.method} ${options.path}');
      //print('📤 HEADERS: ${options.headers}');
      //print('📦 DATA: ${options.data}');
      //print('📦 EXTRA: ${options.extra}'); // Add this to debug
      
      return handler.next(options);
    },
    onResponse: (response, handler) {
      //print('📥 RESPONSE: ${response.statusCode}');
      //print('📥 RESPONSE HEADERS: ${response.headers}'); // Add this to see cookies
      //print('📥 RESPONSE DATA: ${response.data}');
      return handler.next(response);
    },
    onError: (error, handler) {
      print('❌ ERROR: ${error.message}');
      if (error.response != null) {
        print('❌ RESPONSE DATA: ${error.response?.data}');
      }
      return handler.next(error);
    },
  ));
}
  void setToken(String token) {
    _token = token;
  }

  void clearToken() {
    _token = null;
  }

  void setCurrency(String currency) {
    _currency = currency;
  }

  // Helper method to handle responses
  // Helper method to handle responses
  ApiResponse _handleResponse(Response response) {
    try {
      if (response.statusCode == 200 || response.statusCode == 201) {
        // The API returns data directly, not wrapped in a 'data' field
        return ApiResponse(
          success: true,
          message: 'Success',
          data: response.data, // Pass the entire response data
        );
      }
      return ApiResponse.error('Unexpected response: ${response.statusCode}');
    } catch (e) {
      return ApiResponse.error('Error parsing response: $e');
    }
  }

  ApiResponse _handleError(DioException e) {
    if (e.response != null) {
      // Server responded with error
      try {
        final data = e.response?.data ?? {'message': 'Server error'};
        return ApiResponse(
          success: false,
          message: data['message'] ?? 'Server error',
          errors: data['errors'] as Map<String, dynamic>?,
        );
      } catch (parseError) {
        return ApiResponse.error('Server error: ${e.response?.statusCode}');
      }
    } else if (e.type == DioExceptionType.connectionTimeout) {
      return ApiResponse.error('Connection timeout. Please check your internet.');
    } else if (e.type == DioExceptionType.receiveTimeout) {
      return ApiResponse.error('Server timeout. Please try again.');
    } else if (e.type == DioExceptionType.connectionError) {
      return ApiResponse.error('No internet connection.');
    } else {
      return ApiResponse.error('Network error: ${e.message}');
    }
  }

  // Add to ApiService class
Future<ApiResponse> getBrands() async {
  try {
    final response = await _dio.get(ApiEndpoints.brands);
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> getFeaturedBrands() async {
  try {
    final response = await _dio.get(ApiEndpoints.featuredBrands);
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> getBrandDetail(int id) async {
  try {
    final response = await _dio.get(ApiEndpoints.brandDetail(id));
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

  // Add to ApiService class
  Future<ApiResponse> getNavbarData() async {
    try {
      final response = await _dio.get(ApiEndpoints.navbar);
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> switchCurrency(String currencyCode) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.switchCurrency,
        data: {'currency_code': currencyCode},
      );
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }
  Future<ApiResponse> switchLanguage(String languageCode) async {
  try {
    final response = await _dio.post(
      ApiEndpoints.switchLanguage,
      data: {'language_code': languageCode},
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

// In lib/services/api_service.dart, ensure you have this method:

Future<ApiResponse> post(String endpoint, {Map<String, dynamic>? data}) async {
  try {
    print('🔵 POST request to: $endpoint');
    print('🔵 Data: $data');
    
    final response = await _dio.post(
      endpoint,
      data: data,
    );
    
    print('🔵 Response status: ${response.statusCode}');
    print('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    print('🔴 POST error: ${e.message}');
    print('🔴 Response: ${e.response?.data}');
    return _handleError(e);
  }
}
  // AUTH METHODS
  Future<ApiResponse> login(String email, String password) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.login, 
        data: {
          'email': email,
          'password': password,
        },
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> register(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.register, 
        data: data,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> logout() async {
    try {
      final response = await _dio.post(ApiEndpoints.logout);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> forgotPassword(String email) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.forgotPassword,
        data: {'email': email},
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> resetPassword(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.resetPassword,
        data: data,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // HOME
  /* Future<ApiResponse> getHomeData() async {
    try {
      final url = ApiEndpoints.home;
      //print('Requesting URL: $url');
      final response = await _dio.get(
        url,
        options: Options(
          responseType: ResponseType.json,
        )
      );

      //print('Raw API Response from Service: ${response.data}');
      //print('Response Status Code: ${response.statusCode}');
      
      // Since the API returns data directly, we wrap it in our ApiResponse
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      //print('Error in request: ${e.message}');
      //print('Error details: ${e.response?.data}');
      print('❌ FULL ERROR: ${e.message}');
      print('❌ ERROR TYPE: ${e.type}');
      print('❌ RESPONSE: ${e.response?.data}');
      return _handleError(e);
    }
  }
 */
  Future<ApiResponse> getHomeData() async {
  try {
    final response = await _dio.get(ApiEndpoints.home);
    // Log the type and a preview (useful for debugging)
    print('📦 Home response type: ${response.data.runtimeType}');
    print('📦 Home response preview: ${response.data.toString().substring(0, 200)}');
    return _handleResponse(response);
  } on DioException catch (e) {
    return _handleError(e);
  }
}
  // PRODUCTS
  Future<ApiResponse> getProducts({
    int page = 1,
    int perPage = 15,
    String? search,
    int? categoryId,
    int? vendorId,
    String? sortBy,
    String? sortOrder,
  }) async {
    try {
      final queryParams = {
        'page': page,
        'per_page': perPage,
        if (search != null) 'search': search,
        if (categoryId != null) 'category_id': categoryId,
        if (vendorId != null) 'vendor_id': vendorId,
        if (sortBy != null) 'sort_by': sortBy,
        if (sortOrder != null) 'sort_order': sortOrder,
      };
      
      final response = await _dio.get(
        ApiEndpoints.products,
        queryParameters: queryParams,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // Add this NEW method - don't modify existing getProducts
  Future<ApiResponse> getVendorProducts(int vendorId, [Map<String, dynamic>? params]) async {
    try {
      final response = await _dio.get(
        ApiEndpoints.getVendorProducts(vendorId),
        queryParameters: params,
      );
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // Add to ApiService class
  Future<ApiResponse> searchProducts(String query) async {
    try {
      final response = await _dio.get(
        ApiEndpoints.searchProducts,
        queryParameters: {'search': query, 'per_page': 20},
      );
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> searchVendors(String query) async {
    try {
      final response = await _dio.get(
        ApiEndpoints.searchVendors,
        queryParameters: {'search': query, 'per_page': 10},
      );
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // Add to ApiService class
  Future<ApiResponse> getCurrencies() async {
    try {
      final response = await _dio.get(ApiEndpoints.currencies);
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> submitVendorApplication(Map<String, dynamic> data, {File? logoFile}) async {
    try {
      FormData formData = FormData.fromMap({});
      
      // Add all text fields
      data.forEach((key, value) {
        if (value != null) {
          formData.fields.add(MapEntry(key, value.toString()));
        }
      });
      
      // Add logo file if exists
      /* if (logoFile != null) {
        String fileName = logoFile.path.split('/').last;
        formData.files.add(MapEntry(
          'logo',
          await MultipartFile.fromFile(logoFile.path, filename: fileName),
        ));
      } */
      
      final response = await _dio.post(
        ApiEndpoints.submitVendorApplication, // Your application endpoint
        data: formData,
        options: Options(
          headers: {
            'Content-Type': 'multipart/form-data',
            'Accept': 'application/json',
          },
        ),
      );
      
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // Add to ApiService class
Future<ApiResponse> getVendorDetail(String slug, [Map<String, dynamic>? params]) async {
  try {
    final response = await _dio.get(
      ApiEndpoints.vendorDetail(slug),
      queryParameters: params,
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> getVendorReviews(int vendorId, [Map<String, dynamic>? params]) async {
  try {
    final response = await _dio.get(
      ApiEndpoints.getVendorReviews(vendorId),
      queryParameters: params,
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> toggleFollowVendor(int vendorId) async {
  try {
    print('🔐 TOGGLE FOLLOW API CALL');
    print('🔐 URL: ${ApiEndpoints.toggleFollowVendor(vendorId)}');
    print('🔐 Token present: ${_token != null}');
    print('🔐 Headers: ${_dio.options.headers}');
    
    final response = await _dio.post(ApiEndpoints.toggleFollowVendor(vendorId));
    
    print('🔐 Response status: ${response.statusCode}');
    print('🔐 Response data: ${response.data}');
    
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    print('🔐 DioError: ${e.message}');
    print('🔐 Response: ${e.response?.data}');
    print('🔐 Status code: ${e.response?.statusCode}');
    return _handleError(e);
  }
}

Future<ApiResponse> submitVendorReview(int vendorId, int rating, String comment) async {
  try {
    final response = await _dio.post(
      ApiEndpoints.submitVendorReview(vendorId),
      data: {
        'rating': rating,
        'comment': comment,
      },
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> deleteVendorReview(int vendorId) async {
  try {
    final response = await _dio.delete(ApiEndpoints.deleteVendorReview(vendorId));
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

  Future<ApiResponse> getUserVendorStatus() async {
    try {
      final response = await _dio.get(ApiEndpoints.checkVendorStatus);
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> uploadVendorLogo(File logoFile) async {
    try {
      String fileName = logoFile.path.split('/').last;
      FormData formData = FormData.fromMap({
        'logo': await MultipartFile.fromFile(logoFile.path, filename: fileName),
      });
      
      // Use the correct endpoint that matches your Laravel route
      // Based on your structure, it should be something like:
      final response = await _dio.post(
        '$baseUrl/public/uploads/vendors', // Adjust this to match your actual route
        data: formData,
        options: Options(
          headers: {
            'Content-Type': 'multipart/form-data',
            'Accept': 'application/json',
          },
        ),
      );
      
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // Add this NEW method - keeps the existing parameterless method intact
  Future<ApiResponse> getProductsWithParams(Map<String, dynamic> params) async {
    try {
      final response = await _dio.get(
        ApiEndpoints.products,
        queryParameters: params,
      );
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // In api_service.dart
// lib/services/api_service.dart

// Add these methods to your ApiService class

Future<ApiResponse> createPaymentSession(int orderId, String paymentMethod) async {
  try {
    print('🔵 Creating payment session - Order: $orderId, Method: $paymentMethod');
    print('🔵 Token present: ${_token != null}');
    
    final response = await _dio.post(
      '${ApiEndpoints.baseUrl}/orders/$orderId/payment/session',
      data: {
        'payment_method': paymentMethod,
        'platform': 'mobile',
      },
    );
    
    print('🔵 Response status: ${response.statusCode}');
    print('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    print('🔴 Error creating payment session: ${e.message}');
    print('🔴 Response data: ${e.response?.data}');
    print('🔴 Status code: ${e.response?.statusCode}');
    return _handleError(e);
  }
}

Future<ApiResponse> submitOfflinePayment({
  required int orderId,
  required double amount,
  required File image,
}) async {
  try {
    String fileName = image.path.split('/').last;
    FormData formData = FormData.fromMap({
      'payment_method': 'om',
      'amount': amount,
      'image': await MultipartFile.fromFile(image.path, filename: fileName),
    });

    final response = await _dio.post(
      '${ApiEndpoints.baseUrl}/orders/$orderId/payment/offline',
      data: formData,
      options: Options(
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      ),
    );
    return _handleResponse(response);
  } on DioException catch (e) {
    return _handleError(e);
  }
}
  Future<ApiResponse> getProduct(String slug, int vendorProductId) async {
    try {
      final url = ApiEndpoints.productDetail(slug, vendorProductId);
      print('🔵🔵🔵 FULL API URL: $url');
      print('🔵🔵🔵 Token being sent: ${_token != null ? 'Yes (length: ${_token!.length})' : 'No'}');
      
      final response = await _dio.get(url);
      
      print('🔵 Response status: ${response.statusCode}');
      print('🔵 Response data type: ${response.data.runtimeType}');
      print('🔵 Response data: ${response.data}');
      
      // If the response is a List, wrap it in a Map structure
      if (response.data is List) {
        print('🔵 Response is a list, wrapping in data object');
        return ApiResponse(
          success: true,
          message: 'Success',
          data: {
            'data': response.data,
            'success': true,
          },
        );
      }
      
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      print('🔴 DioError: ${e.message}');
      print('🔴 Response status: ${e.response?.statusCode}');
      print('🔴 Response data: ${e.response?.data}');
      return _handleError(e);
    }
  }
// lib/services/api_service.dart

Future<ApiResponse> getSuccessPageOrders() async {
  try {
    print('🔵 Fetching success page orders');
    print('🔵 Token present: ${_token != null}');
    
    final response = await _dio.get(
      '${ApiEndpoints.baseUrl}/success/orders',
    );
    
    print('🔵 Response status: ${response.statusCode}');
    print('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    print('🔴 Error fetching success page orders: ${e.message}');
    print('🔴 Response data: ${e.response?.data}');
    return _handleError(e);
  }
}
  // CART
  // In api_service.dart - Update cart methods with better logging
// In api_service.dart - Update getCart method

// In api_service.dart - Update getCart method
// Add this method to ApiService class

  Future<ApiResponse> getUserProfile() async {
  try {
    print('🔵 Getting user profile - Token: ${_token != null ? 'Present' : 'Missing'}');
    
    // Use the endpoint from ApiEndpoints
    final url = ApiEndpoints.editProfile;
    print('🔵 Full URL: $url'); // This will show the correct URL
    
    final response = await _dio.get(url);
    print('🔵 Response status: ${response.statusCode}');
    print('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    print('🔴 Error: ${e.message}');
    print('🔴 Response: ${e.response?.data}');
    return _handleError(e);
  }
}

Future<ApiResponse> updateProfile(Map<String, dynamic> data) async {
  try {
    print('🔵 Updating profile - URL: ${ApiEndpoints.updateProfile}');
    print('🔵 Data: $data');
    
    final response = await _dio.put(ApiEndpoints.updateProfile, data: data);
    return _handleResponse(response);
  } on DioException catch (e) {
    return _handleError(e);
  }
}
  Future<ApiResponse> getCart() async {
  try {
    print('🔵 Fetching cart from: ${ApiEndpoints.cart}');
    print('🔵 Auth token present: ${_token != null}');
    
    final response = await _dio.get(ApiEndpoints.cart);
    
    print('🔵 Cart response status: ${response.statusCode}');
    print('🔵 Cart response type: ${response.data.runtimeType}');
    print('🔵 Cart response data: ${response.data}');
    
    if (response.data is Map) {
      final data = response.data as Map<String, dynamic>;
      
      // Check if it's wrapped in a cart object
      if (data.containsKey('cart')) {
        return ApiResponse(
          success: data['success'] ?? true,
          message: data['message'] ?? 'Cart retrieved successfully',
          data: data, // Return the whole response, provider will handle
        );
      }
      
      return ApiResponse(
        success: true,
        message: 'Success',
        data: data,
      );
    }
    
    return ApiResponse(
      success: false,
      message: 'Unexpected response type',
      data: response.data,
    );
  } on DioException catch (e) {
    print('🔴 Cart error: ${e.message}');
    print('🔴 Response: ${e.response?.data}');
    return _handleError(e);
  }
}
  
  Future<ApiResponse> addToCart(
    int vendorProductId,
    int quantity, {
    int? variationId,
    Map<String, dynamic>? selectedVariations,
    String? customNote,
  }) async {
    try {
      final Map<String, dynamic> data = {
        'vendor_product_id': vendorProductId,
        'quantity': quantity,
      };
      
      if (variationId != null) {
        data['variation_id'] = variationId;
      }
      
      if (selectedVariations != null && selectedVariations.isNotEmpty) {
        data['selected_variations'] = selectedVariations;
      }
      
      if (customNote != null && customNote.isNotEmpty) {
        data['custom_note'] = customNote;
      }
      
      final response = await _dio.post(
        ApiEndpoints.cartAdd,
        data: data,
      );
      
      if (response.data is Map && response.data['success'] == true) {
        return ApiResponse(
          success: true,
          message: response.data['message'] ?? 'Item added to cart',
          data: response.data,
        );
      }
      
      return ApiResponse(
        success: false,
        message: response.data['message'] ?? 'Failed to add item',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> removeFromCart(String cartKey) async {
    try {
      final response = await _dio.delete(ApiEndpoints.cartRemove(cartKey));
      
      if (response.data is Map && response.data['success'] == true) {
        return ApiResponse(
          success: true,
          message: response.data['message'] ?? 'Item removed',
          data: response.data,
        );
      }
      
      return ApiResponse(
        success: false,
        message: response.data['message'] ?? 'Failed to remove item',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> updateCartItem(String cartKey, int quantity) async {
    try {
      final response = await _dio.put(
        ApiEndpoints.cartUpdate(cartKey),
        data: {'quantity': quantity},
      );
      
      if (response.data is Map && response.data['success'] == true) {
        return ApiResponse(
          success: true,
          message: response.data['message'] ?? 'Cart updated',
          data: response.data,
        );
      }
      
      return ApiResponse(
        success: false,
        message: response.data['message'] ?? 'Failed to update cart',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> clearCart() async {
    try {
      final response = await _dio.post(ApiEndpoints.cartClear);
      
      if (response.data is Map && response.data['success'] == true) {
        return ApiResponse(
          success: true,
          message: response.data['message'] ?? 'Cart cleared',
          data: response.data,
        );
      }
      
      return ApiResponse(
        success: false,
        message: response.data['message'] ?? 'Failed to clear cart',
        data: response.data,
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }
  // WISHLIST METHODS
  Future<ApiResponse> getWishlist() async {
    try {
      final response = await _dio.get(ApiEndpoints.wishlist);
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data, // Keep as dynamic, let the provider handle casting
      );
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // In api_service.dart
  Future<ApiResponse> addToWishlist(
    int vendorProductId, {
    int? variationId,
    Map<String, dynamic>? selectedAttributes,
  }) async {
    try {
      final Map<String, dynamic> data = {};
      if (variationId != null) {
        data['variation_id'] = variationId;
      }
      if (selectedAttributes != null && selectedAttributes.isNotEmpty) {
        data['selected_attributes'] = selectedAttributes;
      }
      
      //print('🔵 Making request to: ${ApiEndpoints.wishlistAdd(vendorProductId)}');
      //print('🔵 Request data: $data');
      //print('🔵 Headers: ${_dio.options.headers}');
      
      final response = await _dio.post(
        ApiEndpoints.wishlistAdd(vendorProductId),
        data: data,
      );
      
      //print('🔵 Response status: ${response.statusCode}');
      //print('🔵 Response data: ${response.data}');
      
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      print('🔴 DioError: ${e.message}');
      print('🔴 Response status: ${e.response?.statusCode}');
      print('🔴 Response data: ${e.response?.data}');
      print('🔴 Error type: ${e.type}');
      return _handleError(e);
    }
  }

Future<ApiResponse> removeFromWishlist(int vendorProductId) async {
  try {
    final response = await _dio.delete(ApiEndpoints.wishlistRemove(vendorProductId));
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> removeFromWishlistByKey(String wishlistKey) async {
  try {
    final response = await _dio.delete(ApiEndpoints.wishlistRemoveByKey(wishlistKey));
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> clearWishlist() async {
  try {
    final response = await _dio.post(ApiEndpoints.wishlistClear);
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

Future<ApiResponse> moveWishlistToCart(List<String> wishlistKeys) async {
  try {
    final response = await _dio.post(
      ApiEndpoints.wishlistMoveToCart,
      data: {
        'wishlist_keys': wishlistKeys,
      },
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}
  // CATEGORIES
  Future<ApiResponse> getCategories() async {
    try {
      final response = await _dio.get(ApiEndpoints.categories);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // VENDORS
  Future<ApiResponse> getVendors() async {
    try {
      final response = await _dio.get(ApiEndpoints.vendors);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> getVendor(String slug) async {
    try {
      final response = await _dio.get(ApiEndpoints.vendorDetail(slug));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // ORDERS
  Future<ApiResponse> getOrders({
    int page = 1,
    int perPage = 15,
    String? search,
    String? status,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'per_page': perPage,
      };
      if (search != null && search.isNotEmpty) {
        queryParams['search'] = search;
        print('🔍 API - Adding search parameter: $search');
      }
      if (status != null && status.isNotEmpty && status != 'all') {
        queryParams['status'] = status;
        print('🔍 API - Adding status parameter: $status');
      }
      
      print('🔍 API - Full query params: $queryParams');
      final response = await _dio.get(
        ApiEndpoints.orders,
        queryParameters: queryParams,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> getOrderDetails(int orderId) async {
    try {
      final response = await _dio.get(ApiEndpoints.orderDetail(orderId));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> cancelOrder(int orderId, {String? reason}) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.cancel(orderId),
        data: reason != null ? {'reason': reason} : null,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> trackOrder(int orderId) async {
    try {
      final response = await _dio.get(ApiEndpoints.track(orderId));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /* Future<ApiResponse> requestRefund(int orderId, {required String reason, String? details}) async {
    try {
      final response = await _dio.post(
        '${AppConstants.baseUrl}/api/v1/orders/$orderId/refund',
        data: {
          'reason': reason,
          if (details != null) 'details': details,
        },
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }
 */
  /* Future<ApiResponse> reorder(int orderId) async {
    try {
      final response = await _dio.post('${AppConstants.baseUrl}/api/v1/orders/$orderId/reorder');
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  } */

  Future<ApiResponse> downloadInvoice(int orderId) async {
    try {
      final response = await _dio.get(
        ApiEndpoints.downloadInvoice(orderId),
        options: Options(responseType: ResponseType.json),
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // CHECKOUT
  Future<ApiResponse> calculateShipping(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.calculateShipping,
        data: data,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> placeOrder(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.placeOrder,
        data: data,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // ADDRESSES
  Future<ApiResponse> getAddresses() async {
    try {
      final response = await _dio.get(ApiEndpoints.addresses);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> createAddress(Map<String, dynamic> data) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.addresses,
        data: data,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> getAddressDetail(int addressId) async {
    try {
      final response = await _dio.get(ApiEndpoints.addressDetail(addressId));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  // lib/services/api_service.dart

// Add this method to your ApiService class

Future<ApiResponse> getProductsPage({
  int page = 1,
  int perPage = 12,
  List<int>? categories,
  List<int>? brands,
  bool? featured,
  bool? onSale,
  double? priceRange,
  String? sort,
  String? search,
}) async {
  try {
    final Map<String, dynamic> queryParams = {
      'page': page,
      'per_page': perPage,
    };
    
    if (categories != null && categories.isNotEmpty) {
      queryParams['categories'] = categories;
    }
    
    if (brands != null && brands.isNotEmpty) {
      queryParams['brands'] = brands;
    }
    
    if (featured == true) {
      queryParams['featured'] = true;
    }
    
    if (onSale == true) {
      queryParams['on_sale'] = true;
    }
    
    if (priceRange != null && priceRange > 0) {
      queryParams['price_range'] = priceRange;
    }
    
    if (sort != null) {
      queryParams['sort'] = sort;
    }
    
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }
    
    print('🔵 Fetching products page with params: $queryParams');
    
    final response = await _dio.get(
      ApiEndpoints.productsPage,
      queryParameters: queryParams,
    );
    
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    print('🔴 Error fetching products page: $e');
    return _handleError(e);
  }
}
// In api_service.dart, add these methods:

// Get product reviews with pagination
Future<ApiResponse> getProductReviews(int vendorProductId, {int page = 1}) async {
  try {
    final response = await _dio.get(
      ApiEndpoints.productReviews(vendorProductId),
      queryParameters: {'page': page},
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

// Submit a product review
Future<ApiResponse> submitProductReview(int vendorProductId, int rating, String comment) async {
  try {
    final response = await _dio.post(
      ApiEndpoints.productReviews(vendorProductId),
      data: {
        'rating': rating,
        'comment': comment,
      },
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

// Update a product review
Future<ApiResponse> updateProductReview(int reviewId, int rating, String comment) async {
  try {
    final response = await _dio.put(
      ApiEndpoints.productReview(reviewId),
      data: {
        'rating': rating,
        'comment': comment,
      },
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}

// Delete a product review
Future<ApiResponse> deleteProductReview(int reviewId) async {
  try {
    final response = await _dio.delete(
      ApiEndpoints.productReview(reviewId),
    );
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    return _handleError(e);
  }
}
  Future<ApiResponse> updateAddress(int addressId, Map<String, dynamic> data) async {
    try {
      final response = await _dio.put(
        ApiEndpoints.addressDetail(addressId), // Using addressDetail endpoint
        data: data,
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> deleteAddress(int addressId) async {
    try {
      final response = await _dio.delete(ApiEndpoints.addressDetail(addressId)); // Using addressDetail endpoint
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> setDefaultAddress(int addressId) async {
    try {
      final response = await _dio.post(ApiEndpoints.setDefaultAddress(addressId));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }
}