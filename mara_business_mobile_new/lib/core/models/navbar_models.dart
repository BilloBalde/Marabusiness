import 'home_models.dart';

class NavbarData {
  final User? user;
  final int? managerId;
  final List<Currency> currencies;
  final String currentCurrency;
  final int cartCount;
  final int wishlistCount;
  final List<String> families;
  final Map<String, List<CategoryItem>> menuData;
  final List<Language> languages;
  final String currentLanguage;
  final Map<String, String> socialLinks;

  NavbarData({
    this.user,
    this.managerId,
    required this.currencies,
    required this.currentCurrency,
    required this.cartCount,
    required this.wishlistCount,
    required this.families,
    required this.menuData,
    required this.languages,
    required this.currentLanguage,
    required this.socialLinks,
  });

  factory NavbarData.fromJson(Map<String, dynamic> json) {
    final data = json['data'] ?? json;
    
    return NavbarData(
      user: data['user'] != null ? User.fromJson(data['user']) : null,
      managerId: data['manager_id'],
      currencies: (data['currencies'] as List?)
              ?.map((e) => Currency.fromJson(e))
              .toList() ??
          [],
      currentCurrency: data['current_currency'] ?? 'USD',
      cartCount: data['cart_count'] ?? 0,
      wishlistCount: data['wishlist_count'] ?? 0,
      families: List<String>.from(data['families'] ?? []),
      menuData: (data['menu_data'] as Map<String, dynamic>?)?.map(
            (key, value) => MapEntry(
              key,
              (value as List).map((e) => CategoryItem.fromJson(e)).toList(),
            ),
          ) ??
          {},
      languages: (data['languages'] as List?)
              ?.map((e) => Language.fromJson(e))
              .toList() ??
          [],
      currentLanguage: data['current_language'] ?? 'en',
      socialLinks: Map<String, String>.from(data['social_links'] ?? {}),
    );
  }
}

class User {
  final int id;
  final String name;
  final String email;
  final String? avatar;
  final String? role;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.avatar,
    this.role,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      avatar: json['avatar'],
      role: json['role'],
    );
  }

  String get initials {
    return name.isNotEmpty ? name[0].toUpperCase() : '?';
  }
}

class CategoryItem {
  final int id;
  final String name;
  final String slug;

  CategoryItem({
    required this.id,
    required this.name,
    required this.slug,
  });

  factory CategoryItem.fromJson(Map<String, dynamic> json) {
    return CategoryItem(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
    );
  }
}

class Language {
  final String code;
  final String name;
  final String flag;
  final String label;

  Language({
    required this.code,
    required this.name,
    required this.flag,
    required this.label,
  });

  factory Language.fromJson(Map<String, dynamic> json) {
    return Language(
      code: json['code'],
      name: json['name'],
      flag: json['flag'],
      label: json['label'],
    );
  }

  String get flagEmoji {
    switch (code) {
      case 'en':
        return '🇺🇸';
      case 'fr':
        return '🇫🇷';
      case 'zh':
        return '🇨🇳';
      default:
        return '🌐';
    }
  }
}