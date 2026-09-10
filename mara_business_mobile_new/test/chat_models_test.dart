import 'package:flutter_test/flutter_test.dart';
import 'package:mara_business_mobile_new/core/models/chat.dart';

/// Payloads here are copied from ChatController::formatContact() and
/// formatMessage(), so a change to either side shows up as a failing test rather
/// than an empty conversation on a phone.
void main() {
  group('ChatContact', () {
    test('reads a contact who has exchanged messages', () {
      final contact = ChatContact.fromJson({
        'id': 7,
        'name': 'OmarShop',
        'email': 'omar@example.com',
        'avatar': null,
        'role': 'vendor',
        'last_message': {
          'content': 'Votre commande part demain',
          'created_at': 'il y a 2 heures',
          'is_from_me': false,
        },
        'unread_count': 3,
        'online': false,
      });

      expect(contact.id, 7);
      expect(contact.name, 'OmarShop');
      expect(contact.role, 'vendor');
      expect(contact.lastMessage, 'Votre commande part demain');
      expect(contact.lastMessageAt, 'il y a 2 heures');
      expect(contact.lastMessageIsFromMe, isFalse);
      expect(contact.unreadCount, 3);
      expect(contact.hasUnread, isTrue);
    });

    test('a contact with no history yet has a null last message', () {
      // The API returns vendors drawn from the buyer's orders, so a contact can
      // appear before a single word has been exchanged. Reading `content` off a
      // null last_message would throw and take the whole list down.
      final contact = ChatContact.fromJson({
        'id': 9,
        'name': 'BilloStore',
        'role': 'vendor',
        'last_message': null,
        'unread_count': 0,
      });

      expect(contact.lastMessage, isNull);
      expect(contact.lastMessageAt, isNull);
      expect(contact.hasUnread, isFalse);
    });

    test('initials come from the name for the avatar placeholder', () {
      expect(ChatContact.fromJson({'id': 1, 'name': 'Amadou Diallo'}).initials, 'AD');
      expect(ChatContact.fromJson({'id': 2, 'name': 'OmarShop'}).initials, 'O');
      expect(ChatContact.fromJson({'id': 3, 'name': '   '}).initials, '?');
    });

    test('a missing name does not produce a blank row', () {
      final contact = ChatContact.fromJson({'id': 4});

      expect(contact.name, 'Utilisateur');
      expect(contact.initials, 'U');
    });
  });

  group('ChatMessage', () {
    test('reads a message and whose side it belongs on', () {
      // is_from_me is decided server-side against the authenticated user, so the
      // app never needs to know its own id to lay the conversation out.
      final message = ChatMessage.fromJson({
        'id': 42,
        'content': 'Bonjour, ma commande est-elle partie ?',
        'sender_id': 3,
        'sender_name': 'Fatou',
        'sender_avatar': null,
        'receiver_id': 7,
        'receiver_name': 'OmarShop',
        'created_at': '2026-09-10 14:32:05',
        'created_at_human': 'il y a 5 minutes',
        'is_read': false,
        'is_from_me': true,
      });

      expect(message.id, 42);
      expect(message.content, 'Bonjour, ma commande est-elle partie ?');
      expect(message.isFromMe, isTrue);
      expect(message.isRead, isFalse);
      expect(message.createdAtHuman, 'il y a 5 minutes');
      expect(message.createdAt, DateTime.parse('2026-09-10 14:32:05'));
    });

    test('an unparseable timestamp leaves the date null rather than throwing', () {
      final message = ChatMessage.fromJson({
        'id': 1,
        'content': 'Salut',
        'sender_id': 1,
        'sender_name': 'A',
        'receiver_id': 2,
        'created_at': 'not a date',
      });

      expect(message.createdAt, isNull);
      expect(message.content, 'Salut');
    });

    test('ids arriving as strings are still read as numbers', () {
      // PHP hands these back as ints, but a JSON layer that stringifies numeric
      // ids would otherwise put every message on the wrong side of the screen.
      final message = ChatMessage.fromJson({
        'id': '5',
        'content': 'ok',
        'sender_id': '3',
        'sender_name': 'A',
        'receiver_id': '7',
      });

      expect(message.id, 5);
      expect(message.senderId, 3);
      expect(message.receiverId, 7);
    });
  });
}
