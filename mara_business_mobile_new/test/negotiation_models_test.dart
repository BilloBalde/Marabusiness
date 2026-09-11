import 'package:flutter_test/flutter_test.dart';
import 'package:mara_business_mobile_new/core/models/negotiation.dart';
import 'package:mara_business_mobile_new/core/models/order.dart';

/// Le décodage d'une négociation, et l'état que l'écran en tire.
///
/// Les cas d'expiration comptent double : la règle vit sur le serveur
/// (Order::hasLiveOffer(), revérifiée dans la transaction d'acceptation), et
/// l'application doit s'y tenir même quand la date d'expiration qu'elle a sous
/// les yeux dit autre chose — l'horloge d'un téléphone n'est pas fiable.
Map<String, dynamic> payload({
  String status = 'negotiating',
  String? negotiationStatus = 'open',
  double? proposed,
  double? original = 220000,
  double grandTotal = 220000,
  bool hasLiveOffer = false,
  bool offerExpired = false,
  String? expiresAt,
}) {
  return {
    'order_id': 12,
    'order_number': 'ORD-2026-0012',
    'rfq_id': 4,
    'status': status,
    'negotiation_status': negotiationStatus,
    'vendor': {'id': 3, 'store_name': 'Boutique Kaloum'},
    'currency': 'GNF',
    'currency_symbol': 'FG',
    'target_price': 150000,
    'original_total': original,
    'proposed_total': proposed,
    'grand_total': grandTotal,
    'shipping_amount': 20000,
    'expires_at': expiresAt,
    'has_live_offer': hasLiveOffer,
    'offer_expired': offerExpired,
    'items_count': 1,
    'items': [
      {
        'product_id': 7,
        'name': 'Article',
        'quantity': 2,
        'unit_amount': 100000,
        'total_amount': 200000,
      }
    ],
    'created_at': '2026-09-11T08:00:00+00:00',
  };
}

void main() {
  group('Negotiation.fromJson', () {
    test('lit la forme que le serveur envoie', () {
      final negotiation = Negotiation.fromJson(payload());

      expect(negotiation.orderId, 12);
      expect(negotiation.orderNumber, 'ORD-2026-0012');
      expect(negotiation.vendorName, 'Boutique Kaloum');
      expect(negotiation.currencySymbol, 'FG');
      expect(negotiation.targetPrice, 150000);
      expect(negotiation.items.single.name, 'Article');
      expect(negotiation.items.single.quantity, 2);
    });

    test('accepte des montants envoyés en chaîne', () {
      // decimal:2 de Laravel sérialise parfois en "180000.00".
      final negotiation = Negotiation.fromJson(payload()
        ..['grand_total'] = '220000.00'
        ..['proposed_total'] = '180000.00');

      expect(negotiation.grandTotal, 220000);
      expect(negotiation.proposedTotal, 180000);
    });

    test('survit à un vendeur ou des articles absents', () {
      final negotiation = Negotiation.fromJson({
        'order_id': 1,
        'order_number': 'ORD-1',
        'status': 'negotiating',
        'grand_total': 0,
      });

      expect(negotiation.vendorName, 'Boutique');
      expect(negotiation.items, isEmpty);
      expect(negotiation.proposedTotal, isNull);
    });
  });

  group('quel bandeau montrer', () {
    test('sans réponse, le client attend la boutique', () {
      final negotiation = Negotiation.fromJson(payload());

      expect(negotiation.awaitingVendor, isTrue);
      expect(negotiation.awaitingBuyer, isFalse);
      expect(negotiation.statusLabel, 'En attente du vendeur');
    });

    test('un prix vivant appelle une décision du client', () {
      final negotiation = Negotiation.fromJson(payload(
        negotiationStatus: 'priced',
        proposed: 180000,
        hasLiveOffer: true,
        expiresAt: '2026-09-14T08:00:00+00:00',
      ));

      expect(negotiation.awaitingBuyer, isTrue);
      expect(negotiation.statusLabel, 'Prix proposé');
    });

    test('un prix expiré n\'appelle aucune décision, même avec une date en poche', () {
      // Le serveur dit expiré ; la date d'expiration est là et l'application ne
      // doit pas s'en servir pour rouvrir le bouton.
      final negotiation = Negotiation.fromJson(payload(
        negotiationStatus: 'priced',
        proposed: 180000,
        hasLiveOffer: false,
        offerExpired: true,
        expiresAt: '2026-09-14T08:00:00+00:00',
      ));

      expect(negotiation.awaitingBuyer, isFalse);
      expect(negotiation.statusLabel, 'Prix expiré');
    });

    test('une fois le prix accepté, la commande sort de la négociation', () {
      final negotiation = Negotiation.fromJson(payload(
        status: 'new',
        negotiationStatus: 'agreed',
        grandTotal: 180000,
      ));

      expect(negotiation.isOpen, isFalse);
      expect(negotiation.isAgreed, isTrue);
      expect(negotiation.awaitingBuyer, isFalse);
      expect(negotiation.statusLabel, 'Prix accepté');
    });

    test('une négociation annulée le dit', () {
      final negotiation = Negotiation.fromJson(payload(
        status: 'cancelled',
        negotiationStatus: null,
      ));

      expect(negotiation.isCancelled, isTrue);
      expect(negotiation.statusLabel, 'Annulée');
    });
  });

  group('la remise', () {
    test('est la différence entre le prix de départ et le prix convenu', () {
      final negotiation = Negotiation.fromJson(payload(
        status: 'new',
        negotiationStatus: 'agreed',
        grandTotal: 180000,
      ));

      expect(negotiation.discount, 40000);
    });

    test('est nulle quand le prix convenu est plus élevé', () {
      // Une boutique peut proposer plus cher ; afficher « remise : −20 000 »
      // serait un mensonge.
      final negotiation = Negotiation.fromJson(payload(
        status: 'new',
        negotiationStatus: 'agreed',
        grandTotal: 240000,
      ));

      expect(negotiation.discount, isNull);
    });

    test('est nulle quand il n\'y a rien à comparer', () {
      final negotiation = Negotiation.fromJson(payload(original: null));

      expect(negotiation.discount, isNull);
    });
  });

  group('NegotiationMessage', () {
    test('distingue le client de la boutique par from_buyer', () {
      // sender_id seul ne suffit pas : les deux vivent dans des tables
      // différentes et peuvent porter le même identifiant.
      final mine = NegotiationMessage.fromJson({
        'id': 1,
        'from_buyer': true,
        'message': 'Un geste ?',
        'sent_at': '2026-09-11T08:00:00+00:00',
      });
      final theirs = NegotiationMessage.fromJson({
        'id': 2,
        'from_buyer': false,
        'message': 'Prix proposé : 180 000,00 GNF.',
      });

      expect(mine.fromBuyer, isTrue);
      expect(mine.sentAt, isNotNull);
      expect(theirs.fromBuyer, isFalse);
      expect(theirs.sentAt, isNull);
    });
  });

  group('Order.isNegotiating', () {
    test('une commande en négociation ne propose pas le paiement', () {
      // payment_status vaut 'pending' comme pour une commande impayée
      // ordinaire : c'est le statut qui les sépare.
      final order = Order.fromJson({
        'id': 12,
        'order_number': 'ORD-2026-0012',
        'status': 'negotiating',
        'payment_status': 'pending',
        'grand_total': 220000,
      });

      expect(order.isNegotiating, isTrue);
      expect(order.statusLabel, 'En négociation');
    });

    test('une commande ordinaire reste payable', () {
      final order = Order.fromJson({
        'id': 13,
        'order_number': 'ORD-2026-0013',
        'status': 'new',
        'payment_status': 'pending',
        'grand_total': 50000,
      });

      expect(order.isNegotiating, isFalse);
      expect(order.statusLabel, 'Nouvelle');
    });
  });
}
