import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/models/chat.dart';
import '../../services/api_service.dart';
import '../../utils/app_logger.dart';

/// One conversation.
///
/// Loading it marks the other side's messages read server-side, so there is no
/// separate mark-read call from here — ChatController::show() does it.
class ChatScreen extends StatefulWidget {
  final int userId;
  final String contactName;

  const ChatScreen({
    super.key,
    required this.userId,
    required this.contactName,
  });

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _input = TextEditingController();
  final ScrollController _scroll = ScrollController();

  List<ChatMessage> _messages = [];
  bool _isLoading = true;
  bool _isSending = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await context.read<ApiService>().getChatMessages(widget.userId);
      if (!mounted) return;

      if (response.success) {
        final payload = response.data is Map ? response.data['data'] : null;
        final raw = payload is Map ? payload['messages'] : null;

        setState(() {
          _messages = (raw as List? ?? [])
              .map((m) => ChatMessage.fromJson(Map<String, dynamic>.from(m)))
              .toList();
          _isLoading = false;
        });
        _jumpToLatest();
      } else {
        setState(() {
          _error = response.message ?? 'Impossible de charger la conversation';
          _isLoading = false;
        });
      }
    } catch (e) {
      logDebug('🔴 Chat messages failed: $e');
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _send() async {
    final text = _input.text.trim();
    if (text.isEmpty || _isSending) return;

    setState(() => _isSending = true);

    try {
      final response = await context.read<ApiService>().sendChatMessage(widget.userId, text);
      if (!mounted) return;

      if (response.success) {
        // Clear only once the server has accepted it: wiping the field first
        // loses what someone typed when the request fails.
        _input.clear();

        final raw = response.data is Map ? response.data['data'] : null;
        if (raw is Map) {
          setState(() {
            _messages = [..._messages, ChatMessage.fromJson(Map<String, dynamic>.from(raw))];
          });
          _jumpToLatest();
        } else {
          await _load();
        }
      } else {
        // The API refuses a message between two people with no order between
        // them (403 from canChat), and that has to be visible — not a tap that
        // appears to do nothing.
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response.message ?? 'Message non envoyé'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      logDebug('🔴 Send message failed: $e');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSending = false);
    }
  }

  void _jumpToLatest() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.jumpTo(_scroll.position.maxScrollExtent);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey[50],
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.pop(),
        ),
        title: Text(
          widget.contactName,
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ),
      body: Column(
        children: [
          Expanded(child: _conversation()),
          _composer(),
        ],
      ),
    );
  }

  Widget _conversation() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null && _messages.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.cloud_off, size: 56, color: Colors.grey),
              const SizedBox(height: 12),
              Text(
                'Impossible de charger la conversation',
                style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey[800]),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 6),
              Text(
                _error!,
                style: TextStyle(color: Colors.grey[600], fontSize: 13),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _load,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFD4AF37),
                  foregroundColor: Colors.white,
                ),
                child: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    if (_messages.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Text(
            'Écrivez le premier message.',
            style: TextStyle(color: Colors.grey[600]),
            textAlign: TextAlign.center,
          ),
        ),
      );
    }

    return ListView.builder(
      controller: _scroll,
      padding: const EdgeInsets.all(16),
      itemCount: _messages.length,
      itemBuilder: (context, index) => _bubble(_messages[index]),
    );
  }

  Widget _bubble(ChatMessage message) {
    final mine = message.isFromMe;

    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        decoration: BoxDecoration(
          color: mine ? const Color(0xFFD4AF37) : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(mine ? 16 : 4),
            bottomRight: Radius.circular(mine ? 4 : 16),
          ),
          border: mine ? null : Border.all(color: Colors.grey[200]!),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              message.content,
              style: TextStyle(
                color: mine ? Colors.white : Colors.grey[900],
                fontSize: 14,
              ),
            ),
            if (message.createdAtHuman != null) ...[
              const SizedBox(height: 4),
              Text(
                message.createdAtHuman!,
                style: TextStyle(
                  fontSize: 10,
                  color: mine ? Colors.white70 : Colors.grey[500],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _composer() {
    return Container(
      padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey[200]!)),
      ),
      child: SafeArea(
        top: false,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              child: TextField(
                controller: _input,
                minLines: 1,
                maxLines: 4,
                // The API caps a message at 1000 characters; stopping here keeps
                // someone from writing a long message only to have it refused.
                maxLength: 1000,
                textInputAction: TextInputAction.newline,
                decoration: InputDecoration(
                  hintText: 'Votre message…',
                  counterText: '',
                  filled: true,
                  fillColor: Colors.grey[100],
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            Material(
              color: const Color(0xFFD4AF37),
              shape: const CircleBorder(),
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: _isSending ? null : _send,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: _isSending
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation(Colors.white),
                          ),
                        )
                      : const Icon(Icons.send, color: Colors.white, size: 20),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
