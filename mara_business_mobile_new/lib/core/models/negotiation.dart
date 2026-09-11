/// Modèles de la négociation de prix.
///
/// Formes calquées sur NegotiationController::present() et ::thread(). Deux
/// choses viennent du serveur alors que le téléphone pourrait les calculer, et
/// c'est voulu : [hasLiveOffer] et [offerExpired]. La règle d'expiration vit dans
/// Order::hasLiveOffer(), l'acceptation la revérifie dans sa transaction, et une
/// horloge de téléphone qui avance de dix minutes ne doit pas afficher un bouton
/// que le serveur refusera.
class Negotiation {
  final int orderId;
  final String orderNumber;
  final int? rfqId;

  /// 'negotiating' tant que la discussion dure, puis le statut ordinaire de la
  /// commande ('new' une fois le prix accepté, 'cancelled' si abandonnée).
  final String status;

  /// 'open' | 'priced' | 'agreed', ou null une fois la commande annulée.
  final String? negotiationStatus;

  final int? vendorId;
  final String vendorName;
  final String? currency;
  final String? currencySymbol;

  /// Ce que le client a demandé au départ. Facultatif : la discussion peut
  /// s'ouvrir sans chiffre.
  final double? targetPrice;

  /// Le total avant négociation, conservé pour pouvoir montrer la remise.
  final double? originalTotal;

  /// Le prix proposé par la boutique, tant qu'il n'est pas accepté.
  final double? proposedTotal;

  final double grandTotal;
  final double shippingAmount;
  final DateTime? expiresAt;

  final bool hasLiveOffer;
  final bool offerExpired;

  final int itemsCount;
  final List<NegotiationItem> items;
  final DateTime? createdAt;

  const Negotiation({
    required this.orderId,
    required this.orderNumber,
    this.rfqId,
    required this.status,
    this.negotiationStatus,
    this.vendorId,
    required this.vendorName,
    this.currency,
    this.currencySymbol,
    this.targetPrice,
    this.originalTotal,
    this.proposedTotal,
    required this.grandTotal,
    this.shippingAmount = 0,
    this.expiresAt,
    this.hasLiveOffer = false,
    this.offerExpired = false,
    this.itemsCount = 0,
    this.items = const [],
    this.createdAt,
  });

  /// La discussion est-elle encore en cours ?
  bool get isOpen => status == 'negotiating';

  /// Le client attend une réponse de la boutique.
  bool get awaitingVendor => isOpen && negotiationStatus == 'open';

  /// Un prix est sur la table et n'a pas expiré : c'est au client de trancher.
  bool get awaitingBuyer => isOpen && hasLiveOffer;

  bool get isAgreed => negotiationStatus == 'agreed';
  bool get isCancelled => status == 'cancelled';

  /// La remise obtenue, une fois le prix accepté. Null tant qu'il n'y a rien à
  /// comparer, ou si le prix convenu est supérieur au prix de départ.
  double? get discount {
    final original = originalTotal;
    if (original == null || original <= grandTotal) return null;
    return original - grandTotal;
  }

  String get statusLabel {
    if (isCancelled) return 'Annulée';
    if (isAgreed) return 'Prix accepté';
    if (offerExpired) return 'Prix expiré';
    if (hasLiveOffer) return 'Prix proposé';
    if (isOpen) return 'En attente du vendeur';
    return 'Terminée';
  }

  static double _toDouble(dynamic value, [double fallback = 0]) {
    if (value is num) return value.toDouble();
    return double.tryParse('$value') ?? fallback;
  }

  static double? _toNullableDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    return double.tryParse('$value');
  }

  static int _toInt(dynamic value, [int fallback = 0]) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    return int.tryParse('$value') ?? fallback;
  }

  factory Negotiation.fromJson(Map<String, dynamic> json) {
    final vendor = json['vendor'];
    final rawItems = json['items'];

    return Negotiation(
      orderId: _toInt(json['order_id']),
      orderNumber: json['order_number']?.toString() ?? '',
      rfqId: json['rfq_id'] == null ? null : _toInt(json['rfq_id']),
      status: json['status']?.toString() ?? '',
      negotiationStatus: json['negotiation_status']?.toString(),
      vendorId: vendor is Map && vendor['id'] != null ? _toInt(vendor['id']) : null,
      vendorName: vendor is Map
          ? (vendor['store_name']?.toString() ?? 'Boutique')
          : 'Boutique',
      currency: json['currency']?.toString(),
      currencySymbol: json['currency_symbol']?.toString(),
      targetPrice: _toNullableDouble(json['target_price']),
      originalTotal: _toNullableDouble(json['original_total']),
      proposedTotal: _toNullableDouble(json['proposed_total']),
      grandTotal: _toDouble(json['grand_total']),
      shippingAmount: _toDouble(json['shipping_amount']),
      expiresAt: json['expires_at'] != null
          ? DateTime.tryParse(json['expires_at'].toString())
          : null,
      hasLiveOffer: json['has_live_offer'] == true,
      offerExpired: json['offer_expired'] == true,
      itemsCount: _toInt(json['items_count']),
      items: rawItems is List
          ? rawItems
              .whereType<Map>()
              .map((item) => NegotiationItem.fromJson(Map<String, dynamic>.from(item)))
              .toList()
          : const [],
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }
}

class NegotiationItem {
  final int? productId;
  final String name;
  final int quantity;
  final double unitAmount;
  final double totalAmount;

  const NegotiationItem({
    this.productId,
    required this.name,
    required this.quantity,
    required this.unitAmount,
    required this.totalAmount,
  });

  factory NegotiationItem.fromJson(Map<String, dynamic> json) {
    return NegotiationItem(
      productId: json['product_id'] == null ? null : Negotiation._toInt(json['product_id']),
      // Un produit retiré du catalogue laisse la ligne de commande en place.
      name: json['name']?.toString() ?? 'Article',
      quantity: Negotiation._toInt(json['quantity'], 1),
      unitAmount: Negotiation._toDouble(json['unit_amount']),
      totalAmount: Negotiation._toDouble(json['total_amount']),
    );
  }
}

class NegotiationMessage {
  final int id;

  /// Décidé côté serveur d'après sender_type : la boutique et le client vivent
  /// dans deux tables différentes, l'identifiant seul ne les distingue pas.
  final bool fromBuyer;

  final String message;
  final DateTime? sentAt;

  const NegotiationMessage({
    required this.id,
    required this.fromBuyer,
    required this.message,
    this.sentAt,
  });

  factory NegotiationMessage.fromJson(Map<String, dynamic> json) {
    return NegotiationMessage(
      id: Negotiation._toInt(json['id']),
      fromBuyer: json['from_buyer'] == true,
      message: json['message']?.toString() ?? '',
      sentAt: json['sent_at'] != null
          ? DateTime.tryParse(json['sent_at'].toString())
          : null,
    );
  }
}
