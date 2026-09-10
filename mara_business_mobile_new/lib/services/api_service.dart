import '../utils/app_logger.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../core/models/api_response.dart';
import '../core/constants/api_endpoints.dart';
import 'dart:io';

class ApiService {
  late final Dio _dio;
  final String baseUrl;
  String? _token;
  String _currency = 'USD';

  /// Called when the server rejects the token (401). Nothing used to happen on a
  /// 401 at all: the error was printed and passed along, so an expired or revoked
  /// token left the app showing generic failures forever with no way back to the
  /// sign-in screen — and, because most screens ignore provider errors, those
  /// failures surfaced as empty lists ("you have no orders"). Wired in main.dart.
  void Function()? onUnauthorized;

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
      
      //logDebug('🌐 REQUEST: ${options.method} ${options.path}');
      //logDebug('📤 HEADERS: ${options.headers}');
      //logDebug('📦 DATA: ${options.data}');
      //logDebug('📦 EXTRA: ${options.extra}'); // Add this to debug
      
      return handler.next(options);
    },
    onResponse: (response, handler) {
      //logDebug('📥 RESPONSE: ${response.statusCode}');
      //logDebug('📥 RESPONSE HEADERS: ${response.headers}'); // Add this to see cookies
      //logDebug('📥 RESPONSE DATA: ${response.data}');
      return handler.next(response);
    },
    onError: (error, handler) {
      // These were print(), which reaches the device log in release builds too,
      // and the body of a failed response can carry account data — the mobile
      // counterpart of the DHL credentials that were being logged server-side.
      // logDebug() compiles away entirely outside debug builds.
      logDebug('❌ ERROR: ${error.message}');
      if (error.response != null) {
        logDebug('❌ RESPONSE DATA: ${error.response?.data}');
      }

      if (error.response?.statusCode == 401) {
        // Only a session that existed can expire.
        //
        // This fired on every 401, including the one a guest gets from /cart
        // just by opening the home page — so browsing anonymously bounced
        // straight to the sign-in screen, wiping storage on the way. Caught by
        // running the web build: the home page loaded and then jumped to login
        // on its own.
        //
        // With no token, a 401 means "this endpoint needs auth", which the
        // calling screen handles. With a token, it means the session died, and
        // that is what the redirect is for.
        final hadToken = _token != null;
        _token = null;

        if (hadToken) {
          onUnauthorized?.call();
        }
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
    logDebug('🔵 POST request to: $endpoint');
    logDebug('🔵 Data: $data');
    
    final response = await _dio.post(
      endpoint,
      data: data,
    );
    
    logDebug('🔵 Response status: ${response.statusCode}');
    logDebug('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    logDebug('🔴 POST error: ${e.message}');
    logDebug('🔴 Response: ${e.response?.data}');
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
      //logDebug('Requesting URL: $url');
      final response = await _dio.get(
        url,
        options: Options(
          responseType: ResponseType.json,
        )
      );

      //logDebug('Raw API Response from Service: ${response.data}');
      //logDebug('Response Status Code: ${response.statusCode}');
      
      // Since the API returns data directly, we wrap it in our ApiResponse
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      //logDebug('Error in request: ${e.message}');
      //logDebug('Error details: ${e.response?.data}');
      logDebug('❌ FULL ERROR: ${e.message}');
      logDebug('❌ ERROR TYPE: ${e.type}');
      logDebug('❌ RESPONSE: ${e.response?.data}');
      return _handleError(e);
    }
  }
 */
  Future<ApiResponse> getHomeData() async {
  try {
    final response = await _dio.get(ApiEndpoints.home);
    // Log the type and a preview (useful for debugging)
    logDebug('📦 Home response type: ${response.data.runtimeType}');
    logDebug('📦 Home response preview: ${response.data.toString().substring(0, 200)}');
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
      
      // The logo was picked, previewed to the applicant, and then dropped: this
      // block was commented out, as was the `logoFile:` argument at the call site
      // in vendor_apply_screen. The server has accepted it the whole time
      // (VendorController: 'logo' => 'nullable|image|max:2048', stored to the
      // vendors disk as logo_path), so a vendor watched their logo appear in the
      // form, submitted, and it silently never arrived.
      //
      // Size is not a concern here: the picker already downsizes to 1024x1024 at
      // quality 85 before this point, well inside the server's 2 MB limit.
      if (logoFile != null) {
        formData.files.add(MapEntry(
          'logo',
          await MultipartFile.fromFile(
            logoFile.path,
            filename: logoFile.path.split(RegExp(r'[/\\]')).last,
          ),
        ));
      }
      
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
    logDebug('🔐 TOGGLE FOLLOW API CALL');
    logDebug('🔐 URL: ${ApiEndpoints.toggleFollowVendor(vendorId)}');
    logDebug('🔐 Token present: ${_token != null}');
    logDebug('🔐 Headers: ${_dio.options.headers}');
    
    final response = await _dio.post(ApiEndpoints.toggleFollowVendor(vendorId));
    
    logDebug('🔐 Response status: ${response.statusCode}');
    logDebug('🔐 Response data: ${response.data}');
    
    return ApiResponse(
      success: true,
      message: 'Success',
      data: response.data,
    );
  } on DioException catch (e) {
    logDebug('🔐 DioError: ${e.message}');
    logDebug('🔐 Response: ${e.response?.data}');
    logDebug('🔐 Status code: ${e.response?.statusCode}');
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
    logDebug('🔵 Creating payment session - Order: $orderId, Method: $paymentMethod');
    logDebug('🔵 Token present: ${_token != null}');
    
    final response = await _dio.post(
      '${ApiEndpoints.baseUrl}/orders/$orderId/payment/session',
      data: {
        'payment_method': paymentMethod,
        'platform': 'mobile',
      },
    );
    
    logDebug('🔵 Response status: ${response.statusCode}');
    logDebug('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    logDebug('🔴 Error creating payment session: ${e.message}');
    logDebug('🔴 Response data: ${e.response?.data}');
    logDebug('🔴 Status code: ${e.response?.statusCode}');
    return _handleError(e);
  }
}

/// Declares an offline payment (Orange Money, or cash) on an order.
///
/// No screen calls this yet — the payment modal only offers cash on delivery
/// and LengoPay. It is kept because /payment/offline is a live endpoint, but
/// its signature was wrong in the same way the server was: it hardcoded 'om'
/// and demanded a proof image for every method. Proof is required for Orange
/// Money, where money moves and there is a receipt to screenshot, and optional
/// for cash, where there is nothing to photograph — matching
/// Paiement::METHODS_REQUIRING_PROOF. Wiring a screen to this must pass the
/// method the buyer actually chose.
Future<ApiResponse> submitOfflinePayment({
  required int orderId,
  required double amount,
  String paymentMethod = 'om',
  File? image,
}) async {
  try {
    final formData = FormData.fromMap({
      'payment_method': paymentMethod,
      'amount': amount,
      if (image != null)
        'image': await MultipartFile.fromFile(
          image.path,
          filename: image.path.split(RegExp(r'[/\\]')).last,
        ),
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
      logDebug('🔵🔵🔵 FULL API URL: $url');
      logDebug('🔵🔵🔵 Token being sent: ${_token != null ? 'Yes (length: ${_token!.length})' : 'No'}');
      
      final response = await _dio.get(url);
      
      logDebug('🔵 Response status: ${response.statusCode}');
      logDebug('🔵 Response data type: ${response.data.runtimeType}');
      logDebug('🔵 Response data: ${response.data}');
      
      // If the response is a List, wrap it in a Map structure
      if (response.data is List) {
        logDebug('🔵 Response is a list, wrapping in data object');
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
      logDebug('🔴 DioError: ${e.message}');
      logDebug('🔴 Response status: ${e.response?.statusCode}');
      logDebug('🔴 Response data: ${e.response?.data}');
      return _handleError(e);
    }
  }
// lib/services/api_service.dart

Future<ApiResponse> getSuccessPageOrders() async {
  try {
    logDebug('🔵 Fetching success page orders');
    logDebug('🔵 Token present: ${_token != null}');
    
    final response = await _dio.get(
      '${ApiEndpoints.baseUrl}/success/orders',
    );
    
    logDebug('🔵 Response status: ${response.statusCode}');
    logDebug('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    logDebug('🔴 Error fetching success page orders: ${e.message}');
    logDebug('🔴 Response data: ${e.response?.data}');
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
    logDebug('🔵 Getting user profile - Token: ${_token != null ? 'Present' : 'Missing'}');
    
    // Use the endpoint from ApiEndpoints
    final url = ApiEndpoints.editProfile;
    logDebug('🔵 Full URL: $url'); // This will show the correct URL
    
    final response = await _dio.get(url);
    logDebug('🔵 Response status: ${response.statusCode}');
    logDebug('🔵 Response data: ${response.data}');
    
    return _handleResponse(response);
  } on DioException catch (e) {
    logDebug('🔴 Error: ${e.message}');
    logDebug('🔴 Response: ${e.response?.data}');
    return _handleError(e);
  }
}

Future<ApiResponse> updateProfile(Map<String, dynamic> data) async {
  try {
    logDebug('🔵 Updating profile - URL: ${ApiEndpoints.updateProfile}');
    logDebug('🔵 Data: $data');
    
    final response = await _dio.put(ApiEndpoints.updateProfile, data: data);
    return _handleResponse(response);
  } on DioException catch (e) {
    return _handleError(e);
  }
}
  Future<ApiResponse> getCart() async {
  try {
    logDebug('🔵 Fetching cart from: ${ApiEndpoints.cart}');
    logDebug('🔵 Auth token present: ${_token != null}');
    
    final response = await _dio.get(ApiEndpoints.cart);
    
    logDebug('🔵 Cart response status: ${response.statusCode}');
    logDebug('🔵 Cart response type: ${response.data.runtimeType}');
    logDebug('🔵 Cart response data: ${response.data}');
    
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
    logDebug('🔴 Cart error: ${e.message}');
    logDebug('🔴 Response: ${e.response?.data}');
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
      
      //logDebug('🔵 Making request to: ${ApiEndpoints.wishlistAdd(vendorProductId)}');
      //logDebug('🔵 Request data: $data');
      //logDebug('🔵 Headers: ${_dio.options.headers}');
      
      final response = await _dio.post(
        ApiEndpoints.wishlistAdd(vendorProductId),
        data: data,
      );
      
      //logDebug('🔵 Response status: ${response.statusCode}');
      //logDebug('🔵 Response data: ${response.data}');
      
      return ApiResponse(
        success: true,
        message: 'Success',
        data: response.data,
      );
    } on DioException catch (e) {
      logDebug('🔴 DioError: ${e.message}');
      logDebug('🔴 Response status: ${e.response?.statusCode}');
      logDebug('🔴 Response data: ${e.response?.data}');
      logDebug('🔴 Error type: ${e.type}');
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
        logDebug('🔍 API - Adding search parameter: $search');
      }
      if (status != null && status.isNotEmpty && status != 'all') {
        queryParams['status'] = status;
        logDebug('🔍 API - Adding status parameter: $status');
      }
      
      logDebug('🔍 API - Full query params: $queryParams');
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
    
    logDebug('🔵 Fetching products page with params: $queryParams');
    
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
    logDebug('🔴 Error fetching products page: $e');
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

  // ---------------------------------------------------------------- chat
  //
  // ChatController has served these five routes all along and the app called
  // none of them. Who a buyer may talk to is decided server-side (a vendor they
  // have actually ordered from, or a manager), so the client only asks.

  Future<ApiResponse> getChatContacts() async {
    try {
      final response = await _dio.get(ApiEndpoints.chats);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  /// Loads a conversation. The server marks the other side's messages read as a
  /// side effect of this call, so there is no separate mark-read to make here.
  Future<ApiResponse> getChatMessages(int userId) async {
    try {
      final response = await _dio.get(ApiEndpoints.chatWith(userId));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> sendChatMessage(int receiverId, String message) async {
    try {
      final response = await _dio.post(
        ApiEndpoints.sendMessage,
        data: {'receiver_id': receiverId, 'message': message},
      );
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }

  Future<ApiResponse> getUnreadMessageCount() async {
    try {
      final response = await _dio.get(ApiEndpoints.unreadMessages);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleError(e);
    }
  }
}