/// Models for the chat the API has been serving all along.
///
/// ChatController and its five routes exist and work; ApiEndpoints already
/// declared three of them. Nothing on the Flutter side ever called them, so the
/// app shipped without the messaging the brief asks for in the profile.
///
/// Shapes follow ChatController::formatContact() and formatMessage() exactly.
class ChatContact {
  final int id;
  final String name;
  final String? email;
  final String? avatar;

  /// 'vendor', 'manager' or 'customer' — who the person is to you. Comes back
  /// null if the account carries no role.
  final String? role;

  final String? lastMessage;

  /// The server sends this already humanised ("2 hours ago"), so it is a string
  /// rather than a DateTime; there is no machine-readable timestamp on a
  /// contact's last message to sort or format from.
  final String? lastMessageAt;

  final bool lastMessageIsFromMe;
  final int unreadCount;

  const ChatContact({
    required this.id,
    required this.name,
    this.email,
    this.avatar,
    this.role,
    this.lastMessage,
    this.lastMessageAt,
    this.lastMessageIsFromMe = false,
    this.unreadCount = 0,
  });

  bool get hasUnread => unreadCount > 0;

  /// Initials for the avatar placeholder, so a contact with no picture still
  /// reads as a person rather than a grey circle.
  String get initials {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return (parts.first[0] + parts.last[0]).toUpperCase();
  }

  factory ChatContact.fromJson(Map<String, dynamic> json) {
    final last = json['last_message'];

    return ChatContact(
      id: json['id'] is int ? json['id'] : int.tryParse('${json['id']}') ?? 0,
      name: json['name']?.toString() ?? 'Utilisateur',
      email: json['email']?.toString(),
      avatar: json['avatar']?.toString(),
      role: json['role']?.toString(),
      lastMessage: last is Map ? last['content']?.toString() : null,
      lastMessageAt: last is Map ? last['created_at']?.toString() : null,
      lastMessageIsFromMe: last is Map && last['is_from_me'] == true,
      unreadCount: json['unread_count'] is int
          ? json['unread_count']
          : int.tryParse('${json['unread_count']}') ?? 0,
    );
  }
}

class ChatMessage {
  final int id;
  final String content;
  final int senderId;
  final String senderName;
  final int receiverId;

  /// Absolute timestamp, used to group messages by day.
  final DateTime? createdAt;

  /// The server's own "il y a 5 minutes" rendering, shown under each bubble so
  /// the app and the web say the same thing.
  final String? createdAtHuman;

  final bool isRead;

  /// Decided server-side against the authenticated user, so the app never has
  /// to know its own user id to lay the conversation out.
  final bool isFromMe;

  const ChatMessage({
    required this.id,
    required this.content,
    required this.senderId,
    required this.senderName,
    required this.receiverId,
    this.createdAt,
    this.createdAtHuman,
    this.isRead = false,
    this.isFromMe = false,
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id'] is int ? json['id'] : int.tryParse('${json['id']}') ?? 0,
      content: json['content']?.toString() ?? '',
      senderId: json['sender_id'] is int
          ? json['sender_id']
          : int.tryParse('${json['sender_id']}') ?? 0,
      senderName: json['sender_name']?.toString() ?? '',
      receiverId: json['receiver_id'] is int
          ? json['receiver_id']
          : int.tryParse('${json['receiver_id']}') ?? 0,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
      createdAtHuman: json['created_at_human']?.toString(),
      isRead: json['is_read'] == true,
      isFromMe: json['is_from_me'] == true,
    );
  }
}
