import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/models/chat.dart';
import '../../services/api_service.dart';
import '../../utils/app_logger.dart';

/// Who the signed-in user may talk to, newest conversation first.
///
/// The list is entirely server-decided: ChatController returns the vendors this
/// buyer has actually ordered from, plus any manager. There is no "start a chat
/// with anyone" here, because the API would refuse it — canChat() requires an
/// order between the two parties.
class ChatListScreen extends StatefulWidget {
  const ChatListScreen({super.key});

  @override
  State<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends State<ChatListScreen> {
  List<ChatContact> _contacts = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await context.read<ApiService>().getChatContacts();
      if (!mounted) return;

      if (response.success) {
        final raw = response.data is Map ? response.data['data'] : response.data;
        setState(() {
          _contacts = (raw as List? ?? [])
              .map((c) => ChatContact.fromJson(Map<String, dynamic>.from(c)))
              .toList();
          _isLoading = false;
        });
      } else {
        setState(() {
          _error = response.message ?? 'Impossible de charger vos conversations';
          _isLoading = false;
        });
      }
    } catch (e) {
      logDebug('🔴 Chat contacts failed: $e');
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        title: const Text('Messages', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/profile'),
        ),
      ),
      body: RefreshIndicator(onRefresh: _load, child: _body()),
    );
  }

  Widget _body() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    // Checked before the empty state: a failed load leaves the list empty too,
    // and telling someone they have no conversations when the request simply
    // failed is the worse of the two answers.
    if (_error != null && _contacts.isEmpty) {
      return ListView(
        children: [
          const SizedBox(height: 120),
          const Icon(Icons.cloud_off, size: 64, color: Colors.grey),
          const SizedBox(height: 16),
          Text(
            'Impossible de charger vos conversations',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey[800]),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Text(
              _error!,
              style: TextStyle(color: Colors.grey[600]),
              textAlign: TextAlign.center,
            ),
          ),
          const SizedBox(height: 24),
          Center(
            child: ElevatedButton(
              onPressed: _load,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFD4AF37),
                foregroundColor: Colors.white,
              ),
              child: const Text('Réessayer'),
            ),
          ),
        ],
      );
    }

    if (_contacts.isEmpty) {
      return ListView(
        children: [
          const SizedBox(height: 120),
          Icon(Icons.forum_outlined, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            'Aucune conversation',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey[800]),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(
              'Vous pourrez écrire à un vendeur après une commande passée chez lui.',
              style: TextStyle(color: Colors.grey[600]),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      );
    }

    return ListView.separated(
      itemCount: _contacts.length,
      separatorBuilder: (_, __) => Divider(height: 1, color: Colors.grey[200]),
      itemBuilder: (context, index) => _tile(_contacts[index]),
    );
  }

  Widget _tile(ChatContact contact) {
    return ListTile(
      tileColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      leading: CircleAvatar(
        radius: 24,
        backgroundColor: const Color(0xFFD4AF37).withValues(alpha: 0.15),
        child: Text(
          contact.initials,
          style: const TextStyle(
            color: Color(0xFFD4AF37),
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      title: Row(
        children: [
          Expanded(
            child: Text(
              contact.name,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontWeight: contact.hasUnread ? FontWeight.bold : FontWeight.w600,
              ),
            ),
          ),
          if (contact.lastMessageAt != null)
            Text(
              contact.lastMessageAt!,
              style: TextStyle(fontSize: 11, color: Colors.grey[500]),
            ),
        ],
      ),
      subtitle: contact.lastMessage == null
          ? Text(
              contact.role == 'vendor' ? 'Vendeur' : 'Support',
              style: TextStyle(fontSize: 12, color: Colors.grey[500]),
            )
          : Text(
              // "Vous : ..." mirrors the is_from_me flag the API already sends,
              // so a list of replies reads as a conversation rather than a pile
              // of anonymous lines.
              contact.lastMessageIsFromMe
                  ? 'Vous : ${contact.lastMessage}'
                  : contact.lastMessage!,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 13,
                color: contact.hasUnread ? Colors.grey[900] : Colors.grey[600],
                fontWeight: contact.hasUnread ? FontWeight.w600 : FontWeight.normal,
              ),
            ),
      trailing: contact.hasUnread
          ? Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: const BoxDecoration(
                color: Color(0xFFD4AF37),
                shape: BoxShape.circle,
              ),
              child: Text(
                '${contact.unreadCount}',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                ),
              ),
            )
          : const Icon(Icons.chevron_right, color: Colors.grey),
      onTap: () async {
        await context.push('/messages/${contact.id}?name=${Uri.encodeComponent(contact.name)}');
        // Reading a conversation clears its unread count server-side, so the
        // list is stale the moment we come back.
        if (mounted) _load();
      },
    );
  }
}
