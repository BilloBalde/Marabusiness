// lib/utils/currency_formatter.dart

import 'package:flutter/material.dart';

class CurrencyFormatter {
  static const Map<String, String> _currencySymbols = {
    'USD': '\$',
    'EUR': '€',
    'GBP': '£',
    'GNF': 'FG',
    'XOF': 'CFA',
    'XAF': 'FCFA',
    'NGN': '₦',
    'ZAR': 'R',
    'MAD': 'DH',
    'TND': 'DT',
  };

  static const Map<String, int> _currencyDecimals = {
    'USD': 2,
    'EUR': 2,
    'GBP': 2,
    'GNF': 0,
    'XOF': 0,
    'XAF': 0,
    'NGN': 2,
    'ZAR': 2,
    'MAD': 2,
    'TND': 3,
  };

  static String getSymbol(String currencyCode) {
    return _currencySymbols[currencyCode.toUpperCase()] ?? currencyCode;
  }

  static int getDecimals(String currencyCode) {
    return _currencyDecimals[currencyCode.toUpperCase()] ?? 2;
  }

  static String format(
    double amount, 
    String currencyCode, {
    bool symbol = true,
    bool showCode = false,
  }) {
    final code = currencyCode.toUpperCase();
    final decimals = getDecimals(code);
    final formattedNumber = amount.toStringAsFixed(decimals);
    
    // Add thousand separators
    final parts = formattedNumber.split('.');
    final integerPart = parts[0];
    final decimalPart = parts.length > 1 ? '.${parts[1]}' : '';
    
    final buffer = StringBuffer();
    for (int i = 0; i < integerPart.length; i++) {
      if (i > 0 && (integerPart.length - i) % 3 == 0) {
        buffer.write(' ');
      }
      buffer.write(integerPart[i]);
    }
    
    final formatted = buffer.toString() + decimalPart;
    
    if (symbol) {
      final symbol = getSymbol(code);
      return '$symbol $formatted';
    } else if (showCode) {
      return '$formatted $code';
    } else {
      return formatted;
    }
  }

  static String formatCompact(
    double amount, 
    String currencyCode, {
    bool symbol = true,
  }) {
    final code = currencyCode.toUpperCase();
    double value = amount;
    String suffix = '';
    
    if (amount >= 1000000) {
      value = amount / 1000000;
      suffix = 'M';
    } else if (amount >= 1000) {
      value = amount / 1000;
      suffix = 'k';
    }
    
    final formatted = value.toStringAsFixed(1).replaceAll('.0', '') + suffix;
    
    if (symbol) {
      final symbol = getSymbol(code);
      return '$symbol $formatted';
    } else {
      return '$formatted $code';
    }
  }

  static String formatWithConversion(
    double amount,
    String fromCurrency,
    String toCurrency,
    double exchangeRate,
  ) {
    final converted = amount * exchangeRate;
    return format(converted, toCurrency);
  }

  static String getCurrencyName(String currencyCode) {
    switch (currencyCode.toUpperCase()) {
      case 'USD':
        return 'US Dollar';
      case 'EUR':
        return 'Euro';
      case 'GBP':
        return 'British Pound';
      case 'GNF':
        return 'Guinean Franc';
      case 'XOF':
        return 'West African CFA Franc';
      case 'XAF':
        return 'Central African CFA Franc';
      case 'NGN':
        return 'Nigerian Naira';
      case 'ZAR':
        return 'South African Rand';
      case 'MAD':
        return 'Moroccan Dirham';
      case 'TND':
        return 'Tunisian Dinar';
      default:
        return currencyCode;
    }
  }

  static String formatPriceRange(
    double minPrice,
    double maxPrice,
    String currencyCode,
  ) {
    if (minPrice == maxPrice) {
      return format(minPrice, currencyCode);
    }
    return '${format(minPrice, currencyCode)} - ${format(maxPrice, currencyCode)}';
  }

  static double parseFromString(String formattedString) {
    // Remove all non-numeric characters except decimal point
    final cleaned = formattedString.replaceAll(RegExp(r'[^\d.,]'), '');
    
    // Handle different decimal separators
    String normalized = cleaned.replaceAll(',', '.');
    
    // Remove thousand separators
    if (normalized.contains('.') && normalized.indexOf('.') != normalized.lastIndexOf('.')) {
      // Has multiple dots, treat first as thousand separator
      final lastDotIndex = normalized.lastIndexOf('.');
      normalized = '${normalized.replaceAll('.', '').substring(0, lastDotIndex - 1)}.${normalized.substring(lastDotIndex + 1)}';
    }
    
    return double.tryParse(normalized) ?? 0.0;
  }
}

extension CurrencyExtension on double {
  String toCurrency(String currencyCode, {bool symbol = true}) {
    return CurrencyFormatter.format(this, currencyCode, symbol: symbol);
  }
  
  String toCompactCurrency(String currencyCode, {bool symbol = true}) {
    return CurrencyFormatter.formatCompact(this, currencyCode, symbol: symbol);
  }
}

class CurrencyDropdownItem {
  final String code;
  final String name;
  final String symbol;

  CurrencyDropdownItem({
    required this.code,
    required this.name,
    required this.symbol,
  });

  String get displayName => '$symbol $code - $name';
}

// Predefined list of supported currencies
final List<CurrencyDropdownItem> supportedCurrencies = [
  CurrencyDropdownItem(code: 'GNF', name: 'Guinean Franc', symbol: 'FG'),
  CurrencyDropdownItem(code: 'USD', name: 'US Dollar', symbol: '\$'),
  CurrencyDropdownItem(code: 'EUR', name: 'Euro', symbol: '€'),
  CurrencyDropdownItem(code: 'XOF', name: 'CFA Franc', symbol: 'CFA'),
  CurrencyDropdownItem(code: 'GBP', name: 'British Pound', symbol: '£'),
  CurrencyDropdownItem(code: 'NGN', name: 'Nigerian Naira', symbol: '₦'),
];