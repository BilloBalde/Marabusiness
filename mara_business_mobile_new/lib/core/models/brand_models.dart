class Brand {
  final int id;
  final String name;
  final String slug;
  final String? image;
  final bool isActive;
  final int? createdBy;
  final List<BrandTranslation> translations;

  Brand({
    required this.id,
    required this.name,
    required this.slug,
    this.image,
    required this.isActive,
    this.createdBy,
    required this.translations,
  });

  factory Brand.fromJson(Map<String, dynamic> json) {
    // Handle is_active which might come as 1/0 from API instead of true/false
    dynamic isActiveValue = json['is_active'];
    bool isActive;
    
    if (isActiveValue is bool) {
      isActive = isActiveValue;
    } else if (isActiveValue is int) {
      isActive = isActiveValue == 1; // Convert 1 to true, 0 to false
    } else if (isActiveValue is String) {
      isActive = isActiveValue.toLowerCase() == 'true' || isActiveValue == '1';
    } else {
      isActive = true; // Default value
    }

    return Brand(
      id: json['id'],
      name: json['name'],
      slug: json['slug'],
      image: json['image'],
      isActive: isActive, // Use the converted value
      createdBy: json['created_by'],
      translations: (json['translations'] as List?)
              ?.map((e) => BrandTranslation.fromJson(e))
              .toList() ??
          [],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'slug': slug,
      'image': image,
      'is_active': isActive,
      'created_by': createdBy,
      'translations': translations.map((t) => t.toJson()).toList(),
    };
  }

  // Get translated name based on locale
  String getTranslatedName(String locale) {
    final translation = translations.firstWhere(
      (t) => t.locale == locale,
      orElse: () => translations.firstWhere(
        (t) => t.locale == 'en',
        orElse: () => BrandTranslation(id: id, brandId: id, locale: 'en', name: name),
      ),
    );
    return translation.name;
  }
}

class BrandTranslation {
  final int id;
  final int brandId;
  final String locale;
  final String name;

  BrandTranslation({
    required this.id,
    required this.brandId,
    required this.locale,
    required this.name,
  });

  factory BrandTranslation.fromJson(Map<String, dynamic> json) {
    return BrandTranslation(
      id: json['id'],
      brandId: json['brand_id'],
      locale: json['locale'],
      name: json['name'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'brand_id': brandId,
      'locale': locale,
      'name': name,
    };
  }
}