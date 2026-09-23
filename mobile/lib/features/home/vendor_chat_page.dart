import 'dart:async';
import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/contact_launch.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';

class VendorChatPage extends StatefulWidget {
  const VendorChatPage({
    super.key,
    required this.vendor,
    this.projectName = '',
    this.dateLabel = '',
    this.time = '',
    this.location = '',
    this.status = 'Confirmed',
    this.bookingId,
  });

  final VendorCard vendor;
  final String projectName;
  final String dateLabel;
  final String time;
  final String location;
  final String status;
  final int? bookingId;

  @override
  State<VendorChatPage> createState() => _VendorChatPageState();
}

class _VendorChatPageState extends State<VendorChatPage> {
  static const _muted = Color(0xFF8E8B84);
  static const _bubbleIn = Color(0xFF242428);
  static const _bubbleOut = Color(0xFFC45A32);
  static const _bar = Color(0xFF141416);

  final _input = TextEditingController();
  final _api = ApiClient();
  Timer? _poll;
  int? _conversationId;
  String? _remoteTitle;
  String? _remoteWhen;
  String? _remoteWhere;
  String? _remoteStatus;
  bool _busy = false;
  String? _playingKey;
  List<_ChatItem> _items = [];

  VendorCard get vendor => widget.vendor;

  String get _firstName {
    final parts = vendor.displayName.trim().split(RegExp(r'\s+'));
    return parts.isEmpty ? vendor.displayName : parts.first;
  }

  String get _sessionType {
    final type = switch (vendor.vendorType) {
      'food_stylist' => 'Food Photography',
      'photographer' => 'Photography',
      'videographer' => 'Video',
      'reels' => 'Reels',
      'studio' => 'Studio',
      'model' => 'Model',
      'ugc' => 'UGC',
      _ => vendor.vendorTypeName,
    };
    return '$type Session';
  }

  String get _sessionTitle {
    if ((_remoteTitle ?? '').trim().isNotEmpty) {
      return _remoteTitle!.trim();
    }
    final name = widget.projectName.trim();
    return name.isEmpty ? _sessionType : name;
  }

  String get _when {
    if ((_remoteWhen ?? '').trim().isNotEmpty) {
      return _remoteWhen!.trim();
    }
    if (widget.dateLabel.trim().isNotEmpty && widget.time.trim().isNotEmpty) {
      return '${widget.dateLabel} • ${widget.time}';
    }
    if (widget.dateLabel.trim().isNotEmpty) {
      return widget.dateLabel;
    }
    return 'Saturday, 19 September 2026 • 2:00 PM';
  }

  String get _where {
    if ((_remoteWhere ?? '').trim().isNotEmpty) {
      return _remoteWhere!.trim();
    }
    if (widget.location.trim().isNotEmpty) {
      return widget.location;
    }
    if (vendor.location.isNotEmpty) {
      return vendor.location;
    }
    return vendor.city.isEmpty ? 'Cairo, Egypt' : '${vendor.city}, Egypt';
  }

  String get _statusLabel => (_remoteStatus ?? widget.status).trim().isEmpty ? 'Confirmed' : (_remoteStatus ?? widget.status);

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;

  List<String> get _shots => VendorPhotos.shots(vendor.vendorType, vendor.id);

  String get _phone => '010${vendor.id.toString().padLeft(8, '0')}';

  @override
  void initState() {
    super.initState();
    _seedLocal();
    _connect();
  }

  @override
  void dispose() {
    _poll?.cancel();
    _input.dispose();
    super.dispose();
  }

  void _seedLocal() {
    final photos = _shots.take(3).toList();
    _items = [
      const _ChatItem.day('Today'),
      _ChatItem.text(
        mine: false,
        text: "Hi! I'm looking forward to the session this Saturday. Do you have any specific shots in mind?",
        time: '10:12 AM',
      ),
      _ChatItem.text(
        mine: true,
        text: "Hi $_firstName! Yes, I've attached a few reference images. I'd like a mix of close-ups and some lifestyle shots similar to these.",
        time: '10:15 AM',
      ),
      _ChatItem.images(mine: true, urls: photos, extra: 2, time: '10:15 AM'),
      _ChatItem.text(
        mine: false,
        text: "Perfect! These references look great. I'll prepare a shot list and share it with you before the session.",
        time: '10:18 AM',
      ),
      _ChatItem.file(
        mine: false,
        name: 'Shot List – $_sessionTitle.pdf',
        size: '2.4 MB',
        time: '10:18 AM',
      ),
      const _ChatItem.audio(mine: true, duration: '0:28', time: '10:21 AM'),
      const _ChatItem.text(mine: false, text: 'Got it! See you on Saturday.', time: '10:22 AM'),
    ];
  }

  Future<void> _connect() async {
    if (!_live) {
      return;
    }
    try {
      final payload = await _api.postJson('/app/conversations', {
        'vendor_id': vendor.id,
        if (widget.bookingId != null) 'booking_id': widget.bookingId,
      });
      if (!mounted) {
        return;
      }
      _applyPayload(payload);
      _poll?.cancel();
      _poll = Timer.periodic(const Duration(seconds: 6), (_) => _refresh());
    } catch (_) {
      if (!mounted) {
        return;
      }
      _toast('Could not open the vendor chat. Try again after signing in as a client.');
    }
  }

  Future<void> _refresh() async {
    if (!_live || _conversationId == null) {
      return;
    }
    try {
      final payload = await _api.getJson('/app/conversations/$_conversationId');
      if (mounted) {
        _applyPayload(payload);
      }
    } catch (_) {}
  }

  void _applyPayload(Map<String, dynamic> payload) {
    final session = payload['session'] is Map ? Map<String, dynamic>.from(payload['session'] as Map) : const <String, dynamic>{};
    final messages = (payload['messages'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => _ChatItem.fromJson(Map<String, dynamic>.from(item)))
        .toList();
    setState(() {
      _conversationId = payload['id'] is int ? payload['id'] as int : int.tryParse('${payload['id']}');
      _remoteTitle = session['title']?.toString();
      _remoteWhen = session['when']?.toString();
      _remoteWhere = session['where']?.toString();
      _remoteStatus = session['status']?.toString();
      if (messages.isNotEmpty) {
        _items = _withDays(messages);
      }
    });
  }

  List<_ChatItem> _withDays(List<_ChatItem> messages) {
    final items = <_ChatItem>[];
    String? lastDay;
    for (final message in messages) {
      if (message.day != null && message.day != lastDay) {
        lastDay = message.day;
        final today = DateTime.now().toIso8601String().split('T').first;
        items.add(_ChatItem.day(message.day == today ? 'Today' : message.day!));
      }
      items.add(message);
    }
    return items;
  }

  Future<void> _send() async {
    final text = _input.text.trim();
    if (text.isEmpty || _busy) {
      return;
    }
    _input.clear();
    await _postMessage(type: 'text', body: text, local: _ChatItem.text(mine: true, text: text, time: _now()));
  }

  Future<void> _postMessage({
    required String type,
    String? body,
    String? location,
    String? duration,
    List<http.MultipartFile> files = const [],
    _ChatItem? local,
  }) async {
    if (local != null) {
      setState(() => _items.add(local));
    }
    if (!_live) {
      if (local == null) {
        _toast('Sign in as a client to send this to the vendor.');
      }
      return;
    }
    if (_conversationId == null) {
      await _connect();
    }
    if (_conversationId == null) {
      return;
    }
    setState(() => _busy = true);
    try {
      final payload = files.isEmpty
          ? await _api.postJson('/app/conversations/$_conversationId/messages', {
              'type': type,
              if (body != null) 'body': body,
              if (location != null) 'location_text': location,
              if (widget.vendor.latitude != null) 'location_lat': vendor.latitude,
              if (widget.vendor.longitude != null) 'location_lng': vendor.longitude,
              if (duration != null) 'duration': duration,
            })
          : await _api.postForm(
              '/app/conversations/$_conversationId/messages',
              {
                'type': type,
                if (body != null) 'body': body,
                if (duration != null) 'duration': duration,
              },
              files: files,
            );
      if (mounted) {
        _applyPayload(payload);
      }
    } catch (error) {
      if (mounted) {
        _toast(error.toString());
      }
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Future<void> _pickImages() async {
    try {
      final picked = await ImagePicker().pickMultiImage(imageQuality: 85);
      if (picked.isEmpty) {
        return;
      }
      final files = <http.MultipartFile>[];
      for (var i = 0; i < picked.length; i++) {
        files.add(await http.MultipartFile.fromPath('files[$i]', picked[i].path, filename: picked[i].name));
      }
      await _postMessage(
        type: 'images',
        files: files,
        local: _ChatItem.images(mine: true, urls: picked.map((item) => item.path).toList(), extra: 0, time: _now()),
      );
    } catch (_) {
      _toast('Could not open the photo library.');
    }
  }

  Future<void> _pickFile() async {
    try {
      final result = await FilePicker.platform.pickFiles(withData: kIsWeb);
      final file = result?.files.firstOrNull;
      if (file == null) {
        return;
      }
      http.MultipartFile part;
      if (file.bytes != null) {
        part = http.MultipartFile.fromBytes('files[0]', file.bytes!, filename: file.name);
      } else if (file.path != null) {
        part = await http.MultipartFile.fromPath('files[0]', file.path!, filename: file.name);
      } else {
        return;
      }
      await _postMessage(
        type: 'file',
        files: [part],
        local: _ChatItem.file(mine: true, name: file.name, size: _fileSize(file.size), time: _now()),
      );
    } catch (_) {
      _toast('Could not attach a file.');
    }
  }

  Future<void> _recordVoice() async {
    var seconds = 0;
    final keep = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialog) {
            return AlertDialog(
              backgroundColor: const Color(0xFF141416),
              title: const Text('Voice note', style: TextStyle(color: Colors.white)),
              content: Text('Recording 0:${seconds.toString().padLeft(2, '0')}', style: const TextStyle(color: Color(0xFFD0CBC3))),
              actions: [
                TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
                TextButton(
                  onPressed: () {
                    seconds = seconds == 0 ? 3 : seconds;
                    Navigator.pop(context, true);
                  },
                  child: const Text('Send'),
                ),
              ],
            );
          },
        );
      },
    );
    if (keep != true) {
      return;
    }
    final duration = '0:${(seconds == 0 ? 3 : seconds).toString().padLeft(2, '0')}';
    await _postMessage(
      type: 'audio',
      duration: duration,
      local: _ChatItem.audio(mine: true, duration: duration, time: _now()),
    );
  }

  Future<void> _shareLocation() async {
    await _postMessage(
      type: 'location',
      location: _where,
      body: _where,
      local: _ChatItem.text(mine: true, text: _where, time: _now()),
    );
  }

  void _attachMenu() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                ListTile(
                  leading: const Icon(Icons.image_outlined, color: Colors.white),
                  title: const Text('Photo', style: TextStyle(color: Colors.white)),
                  onTap: () {
                    Navigator.pop(context);
                    _pickImages();
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.attach_file_rounded, color: Colors.white),
                  title: const Text('File', style: TextStyle(color: Colors.white)),
                  onTap: () {
                    Navigator.pop(context);
                    _pickFile();
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.mic_none_rounded, color: Colors.white),
                  title: const Text('Voice note', style: TextStyle(color: Colors.white)),
                  onTap: () {
                    Navigator.pop(context);
                    _recordVoice();
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  String _fileSize(int bytes) {
    if (bytes >= 1048576) {
      return '${(bytes / 1048576).toStringAsFixed(1)} MB';
    }
    return '${(bytes / 1024).round()} KB';
  }

  void _toast(String text) {
    if (!mounted) {
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(text)));
  }

  Future<void> _callVendor() async {
    final opened = await ContactLaunch.open(ContactLaunch.tel(_phone));
    if (!opened && mounted) {
      _toast('Call $_phone');
    }
  }

  Future<void> _whatsAppVendor() async {
    final opened = await ContactLaunch.open(ContactLaunch.whatsapp(_phone));
    if (!opened && mounted) {
      _toast('WhatsApp $_phone');
    }
  }

  Future<void> _openFile(_ChatItem item) async {
    final url = item.fileUrl;
    if (url != null && (url.startsWith('http://') || url.startsWith('https://'))) {
      final opened = await ContactLaunch.open(Uri.parse(url));
      if (!opened) {
        _toast(item.fileName ?? 'File attached');
      }
      return;
    }
    _toast(item.fileName ?? 'File attached');
  }

  String _now() {
    final now = TimeOfDay.now();
    final hour = now.hourOfPeriod == 0 ? 12 : now.hourOfPeriod;
    final minute = now.minute.toString().padLeft(2, '0');
    return '$hour:$minute ${now.period == DayPeriod.am ? 'AM' : 'PM'}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            _header(),
            _sessionCard(),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.fromLTRB(14, 8, 14, 12),
                itemCount: _items.length,
                itemBuilder: (context, index) => _bubble(_items[index]),
              ),
            ),
            _quickActions(),
            _composer(),
          ],
        ),
      ),
    );
  }

  Widget _header() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 4, 8, 10),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
          ),
          _avatar(34),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        vendor.displayName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800),
                      ),
                    ),
                    if (vendor.verified) ...[
                      const SizedBox(width: 4),
                      const Icon(Icons.verified, color: Color(0xFF3B82F6), size: 16),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  _sessionType,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: _muted, fontSize: 12.5),
                ),
              ],
            ),
          ),
          PopupMenuButton<String>(
            tooltip: 'Chat options',
            color: const Color(0xFF141416),
            icon: const Icon(Icons.more_vert_rounded, color: LensColors.cream),
            onSelected: (value) {
              switch (value) {
                case 'details':
                  _projectDetails();
                case 'call':
                  _callVendor();
                case 'whatsapp':
                  _whatsAppVendor();
              }
            },
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'details', child: Text('Project details', style: TextStyle(color: Colors.white))),
              PopupMenuItem(value: 'call', child: Text('Call vendor', style: TextStyle(color: Colors.white))),
              PopupMenuItem(value: 'whatsapp', child: Text('WhatsApp vendor', style: TextStyle(color: Colors.white))),
            ],
          ),
        ],
      ),
    );
  }

  Widget _sessionCard() {
    final cover = vendor.coverUrl ?? VendorPhotos.cover(vendor.vendorType, vendor.id);
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 0, 14, 8),
      child: Material(
        color: _bar,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: _projectDetails,
          child: Padding(
            padding: const EdgeInsets.fromLTRB(10, 10, 8, 10),
            child: Row(
              children: [
                _photo(cover, 46, radius: 12),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              _sessionTitle,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: const Color(0xFF163226),
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: Text(
                              _statusLabel,
                              style: const TextStyle(color: Color(0xFF7DCEA0), fontSize: 11, fontWeight: FontWeight.w800),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today_outlined, color: _muted, size: 13),
                          const SizedBox(width: 5),
                          Expanded(
                            child: Text(_when, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: _muted, fontSize: 11.5)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          const Icon(Icons.location_on_outlined, color: _muted, size: 14),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(_where, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: _muted, fontSize: 11.5)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right_rounded, color: _muted, size: 22),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _bubble(_ChatItem item) {
    if (item.kind == _ChatKind.day) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 10),
        child: Center(
          child: Text(item.text ?? '', style: const TextStyle(color: _muted, fontSize: 12, fontWeight: FontWeight.w600)),
        ),
      );
    }

    final child = switch (item.kind) {
      _ChatKind.images => _imageRow(item),
      _ChatKind.file => _fileCard(item),
      _ChatKind.audio => _audioCard(item),
      _ => _textCard(item),
    };

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisAlignment: item.mine ? MainAxisAlignment.end : MainAxisAlignment.start,
        children: [
          if (!item.mine) ...[
            _avatar(22),
            const SizedBox(width: 8),
          ],
          Flexible(
            child: Column(
              crossAxisAlignment: item.mine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                child,
                const SizedBox(height: 4),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(item.time, style: const TextStyle(color: Color(0xFF6B6B70), fontSize: 11)),
                    if (item.mine) ...[
                      const SizedBox(width: 4),
                      const Icon(Icons.done_all_rounded, color: Color(0xFF8E8B84), size: 14),
                    ],
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _textCard(_ChatItem item) {
    return Container(
      constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.72),
      padding: const EdgeInsets.fromLTRB(14, 11, 14, 11),
      decoration: BoxDecoration(
        color: item.mine ? _bubbleOut : _bubbleIn,
        borderRadius: BorderRadius.only(
          topLeft: const Radius.circular(18),
          topRight: const Radius.circular(18),
          bottomLeft: Radius.circular(item.mine ? 18 : 6),
          bottomRight: Radius.circular(item.mine ? 6 : 18),
        ),
      ),
      child: Text(item.text ?? '', style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.4)),
    );
  }

  Widget _imageRow(_ChatItem item) {
    final urls = item.urls;
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (var i = 0; i < urls.length; i++) ...[
          if (i > 0) const SizedBox(width: 6),
          Stack(
            children: [
              _photo(urls[i], 72, radius: 10),
              if (i == urls.length - 1 && item.extra > 0)
                Positioned.fill(
                  child: DecoratedBox(
                    decoration: BoxDecoration(color: const Color(0x990D0D0F), borderRadius: BorderRadius.circular(10)),
                    child: Center(
                      child: Text('+${item.extra}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                    ),
                  ),
                ),
            ],
          ),
        ],
      ],
    );
  }

  Widget _fileCard(_ChatItem item) {
    return Material(
      color: _bubbleIn,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _openFile(item),
        child: Container(
          width: 250,
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
          child: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(color: const Color(0xFF3A1515), borderRadius: BorderRadius.circular(8)),
                child: const Icon(Icons.picture_as_pdf_rounded, color: Color(0xFFE24B4B), size: 20),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item.fileName ?? 'File', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
                    const SizedBox(height: 2),
                    Text(item.fileSize ?? '', style: const TextStyle(color: _muted, fontSize: 11.5)),
                  ],
                ),
              ),
              const Icon(Icons.file_download_outlined, color: _muted, size: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _audioCard(_ChatItem item) {
    return Container(
      width: 210,
      padding: const EdgeInsets.fromLTRB(8, 8, 12, 8),
      decoration: BoxDecoration(color: _bubbleOut, borderRadius: BorderRadius.circular(22)),
      child: Row(
        children: [
          Material(
            color: Colors.white,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: () {
                final key = '${item.time}-${item.duration}-${item.mine}';
                setState(() => _playingKey = _playingKey == key ? null : key);
              },
              child: SizedBox(
                width: 34,
                height: 34,
                child: Icon(
                  _playingKey == '${item.time}-${item.duration}-${item.mine}' ? Icons.pause_rounded : Icons.play_arrow_rounded,
                  color: _bubbleOut,
                  size: 22,
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          const Expanded(child: _Waveform()),
          const SizedBox(width: 8),
          Text(item.duration ?? '0:00', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12)),
        ],
      ),
    );
  }

  Widget _quickActions() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 8),
      child: Row(
        children: [
          _action(Icons.image_outlined, 'Share Reference', _pickImages),
          const SizedBox(width: 8),
          _action(Icons.location_on_outlined, 'Send Location', _shareLocation),
          const SizedBox(width: 8),
          _action(Icons.description_outlined, 'Project Details', _projectDetails),
        ],
      ),
    );
  }

  Widget _action(IconData icon, String label, VoidCallback onTap) {
    return Expanded(
      child: Material(
        color: _bar,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Column(
              children: [
                Icon(icon, color: const Color(0xFFD0CBC3), size: 18),
                const SizedBox(height: 4),
                Text(label, textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 10.5, fontWeight: FontWeight.w600)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _composer() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 0, 12, 10),
      child: Row(
        children: [
          _round(Icons.add, _attachMenu),
          const SizedBox(width: 8),
          Expanded(
            child: Container(
              height: 46,
              padding: const EdgeInsets.only(left: 14, right: 6),
              decoration: BoxDecoration(
                color: _bar,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: const Color(0xFF2A2A2E)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _input,
                      style: const TextStyle(color: Colors.white, fontSize: 14),
                      decoration: const InputDecoration(
                        hintText: 'Type a message...',
                        hintStyle: TextStyle(color: Color(0xFF6B6B70), fontSize: 14),
                        border: InputBorder.none,
                        isDense: true,
                      ),
                      onSubmitted: (_) => _send(),
                    ),
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    onPressed: _pickImages,
                    icon: const Icon(Icons.image_outlined, color: _muted, size: 20),
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    onPressed: _pickFile,
                    icon: const Icon(Icons.attach_file_rounded, color: _muted, size: 20),
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    onPressed: _recordVoice,
                    icon: const Icon(Icons.mic_none_rounded, color: _muted, size: 20),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          Material(
            color: LensColors.primary,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: _send,
              child: const SizedBox(width: 46, height: 46, child: Icon(Icons.send_rounded, color: Colors.white, size: 20)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _round(IconData icon, VoidCallback onTap) {
    return Material(
      color: _bar,
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(width: 46, height: 46, child: Icon(icon, color: const Color(0xFFD0CBC3))),
      ),
    );
  }

  Widget _avatar(double size) {
    final photo = vendor.profilePhotoUrl ?? VendorPhotos.portrait(vendor.vendorType, vendor.id);
    return _photo(photo, size, radius: size / 2);
  }

  Widget _photo(String url, double size, {double radius = 10}) {
    Widget child = _fallback();
    if (!kIsWeb && (url.startsWith('/') || url.startsWith('file:'))) {
      child = Image.file(
        File(url.replaceFirst('file://', '')),
        fit: BoxFit.cover,
        errorBuilder: (_, __, ___) => _fallback(),
      );
    } else if (url.startsWith('http') && LensConfig.useNetwork) {
      child = Image.network(url, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _fallback());
    }
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: LensColors.graphite, borderRadius: BorderRadius.circular(radius)),
      clipBehavior: Clip.antiAlias,
      child: child,
    );
  }

  Widget _fallback() {
    return Center(child: Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800, fontSize: 11)));
  }

  void _projectDetails() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(_sessionTitle, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              Text(_sessionType, style: const TextStyle(color: _muted)),
              const SizedBox(height: 12),
              Text(_when, style: const TextStyle(color: Colors.white70)),
              const SizedBox(height: 6),
              Text(_where, style: const TextStyle(color: Colors.white70)),
              const SizedBox(height: 6),
              Text(_statusLabel, style: const TextStyle(color: Color(0xFF7DCEA0), fontWeight: FontWeight.w700)),
            ],
          ),
        );
      },
    );
  }
}

enum _ChatKind { day, text, images, file, audio }

class _ChatItem {
  const _ChatItem._({
    required this.kind,
    required this.mine,
    required this.time,
    this.text,
    this.urls = const [],
    this.extra = 0,
    this.fileName,
    this.fileSize,
    this.fileUrl,
    this.duration,
    this.day,
  });

  const _ChatItem.day(String label) : this._(kind: _ChatKind.day, mine: false, time: '', text: label);

  const _ChatItem.text({required bool mine, required String text, required String time})
      : this._(kind: _ChatKind.text, mine: mine, time: time, text: text);

  const _ChatItem.images({required bool mine, required List<String> urls, required int extra, required String time})
      : this._(kind: _ChatKind.images, mine: mine, time: time, urls: urls, extra: extra);

  const _ChatItem.file({required bool mine, required String name, required String size, required String time, String? url})
      : this._(kind: _ChatKind.file, mine: mine, time: time, fileName: name, fileSize: size, fileUrl: url);

  const _ChatItem.audio({required bool mine, required String duration, required String time})
      : this._(kind: _ChatKind.audio, mine: mine, time: time, duration: duration);

  factory _ChatItem.fromJson(Map<String, dynamic> json) {
    final type = json['type']?.toString() ?? 'text';
    final mine = json['mine'] == true;
    final time = json['time']?.toString() ?? '';
    final day = json['day']?.toString();
    final urls = (json['urls'] as List<dynamic>? ?? const []).map((item) => '$item').where((item) => item.isNotEmpty).toList();
    final body = (json['location'] ?? json['body'])?.toString() ?? '';
    return switch (type) {
      'images' => _ChatItem._(kind: _ChatKind.images, mine: mine, time: time, urls: urls, day: day),
      'file' => _ChatItem._(
          kind: _ChatKind.file,
          mine: mine,
          time: time,
          fileName: json['file_name']?.toString() ?? body,
          fileSize: json['file_size']?.toString() ?? '',
          fileUrl: urls.firstOrNull,
          day: day,
        ),
      'audio' => _ChatItem._(kind: _ChatKind.audio, mine: mine, time: time, duration: json['duration']?.toString() ?? '0:00', day: day),
      _ => _ChatItem._(kind: _ChatKind.text, mine: mine, time: time, text: body, day: day),
    };
  }

  final _ChatKind kind;
  final bool mine;
  final String time;
  final String? text;
  final List<String> urls;
  final int extra;
  final String? fileName;
  final String? fileSize;
  final String? fileUrl;
  final String? duration;
  final String? day;
}

class _Waveform extends StatelessWidget {
  const _Waveform();

  @override
  Widget build(BuildContext context) {
    const heights = [8.0, 14.0, 10.0, 18.0, 12.0, 20.0, 9.0, 16.0, 11.0, 19.0, 8.0, 13.0];
    return Row(
      children: [
        for (final height in heights) ...[
          Expanded(
            child: Container(
              height: height,
              margin: const EdgeInsets.symmetric(horizontal: 1.2),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(99)),
            ),
          ),
        ],
      ],
    );
  }
}
