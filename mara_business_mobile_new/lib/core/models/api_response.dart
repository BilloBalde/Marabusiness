class ApiResponse<T> {
  final bool success;
  final String? message;
  final T? data;
  final Map<String, dynamic>? errors;

  ApiResponse({
    required this.success,
    this.message,
    this.data,
    this.errors,
  });

  factory ApiResponse.fromJson(Map<String, dynamic> json, {required bool success}) {
    return ApiResponse(
      success: success,
      message: json['message'] as String?,
      data: json['data'] as T?,
      errors: json['errors'] as Map<String, dynamic>?,
    );
  }

  factory ApiResponse.error(String message) {
    return ApiResponse(
      success: false,
      message: message,
      data: null,
      errors: null,
    );
  }
}