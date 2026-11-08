import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';

class RequestEditPage extends StatefulWidget {
  const RequestEditPage({
    super.key,
    required this.bookingId,
    required this.fileName,
    this.fileUrl,
    this.vendorName = '',
    this.vendorRole = '',
    this.projectName = '',
  });

  final int bookingId;
  final String fileName;
  final String? fileUrl;
  final String vendorName;
  final String vendorRole;
  final String projectName;

  @override
  State<RequestEditPage> createState() => _RequestEditPageState();
}

class _RequestEditPageState extends State<RequestEditPage> {
  static const _orange = LensColors.primary;
  static const _bg = Color(0xFF101318);
  static const _surface = Color(0xFF161A20);
  static const _stroke = Color(0xFF2C3138);
  static const _muted = Color(0xFF9AA0A8);
  static const _hint = Color(0xFF6F7680);
  static const _chips = [
    (Icons.palette_outlined, 'Color'),
    (Icons.face_retouching_natural_outlined, 'Retouching'),
    (Icons.crop_outlined, 'Crop'),
    (Icons.wb_sunny_outlined, 'Exposure'),
    (Icons.description_outlined, 'Export Format'),
    (Icons.more_horiz, 'Other'),
  ];

  final _change = TextEditingController();
  final _comment = TextEditingController();
  final _selected = <String>{};
  final _refs = <XFile>[];
  bool _sending = false;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;
  bool get _canSend =>
      !_sending &&
      widget.bookingId > 0 &&
      (_change.text.trim().isNotEmpty ||
          _selected.isNotEmpty ||
          _comment.text.trim().isNotEmpty ||
          _refs.isNotEmpty);

  @override
  void dispose() {
    _change.dispose();
    _comment.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      body: SafeArea(
        child: Column(
          children: [
            _header(),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
                children: [
                  const Text(
                    'Request an Edit',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 28,
                      fontWeight: FontWeight.w800,
                      height: 1.15,
                    ),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Tell your creator exactly what needs to change.',
                    style: TextStyle(color: _muted, fontSize: 14),
                  ),
                  const SizedBox(height: 18),
                  _fileCard(),
                  const SizedBox(height: 22),
                  _box(
                    label: 'What would you like to change?',
                    hint: 'Type your request here...',
                    controller: _change,
                  ),
                  const SizedBox(height: 18),
                  _box(
                    label: 'Add comment to selected photo (optional)',
                    hint: 'Add a specific comment for this photo...',
                    controller: _comment,
                  ),
                  const SizedBox(height: 22),
                  const Text(
                    'Quick requests',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 16,
                    runSpacing: 12,
                    children: [
                      for (final chip in _chips) _chip(chip.$1, chip.$2),
                    ],
                  ),
                  const SizedBox(height: 22),
                  const Text(
                    'Add reference images (optional)',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _references(),
                ],
              ),
            ),
            _footer(),
          ],
        ),
      ),
    );
  }

  Widget _header() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 2, 12, 4),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: _sending ? null : () => Navigator.of(context).pop(false),
            icon: const Icon(
              Icons.chevron_left_rounded,
              color: Colors.white,
              size: 30,
            ),
          ),
          const Expanded(
            child: Column(
              children: [
                Icon(Icons.auto_awesome, color: _orange, size: 18),
                Text(
                  'lens',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 20,
                    height: 1,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 48),
        ],
      ),
    );
  }

  Widget _fileCard() {
    final session =
        widget.projectName.isNotEmpty ? widget.projectName : widget.vendorRole;
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _stroke),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: SizedBox(
              width: 54,
              height: 54,
              child: _thumb(widget.fileUrl),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.fileName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                if (widget.vendorName.isNotEmpty)
                  Text(
                    widget.vendorName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: _muted, fontSize: 13),
                  ),
                if (session.isNotEmpty)
                  Text(
                    session,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: _muted, fontSize: 12),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _box({
    required String label,
    required String hint,
    required TextEditingController controller,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w800,
            fontSize: 15,
          ),
        ),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(
            color: _surface,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: _stroke),
          ),
          child: Column(
            children: [
              TextField(
                controller: controller,
                maxLength: 500,
                maxLines: 3,
                enabled: !_sending,
                cursorColor: _orange,
                onChanged: (_) => setState(() {}),
                style: const TextStyle(color: Colors.white, fontSize: 14),
                decoration: InputDecoration(
                  hintText: hint,
                  hintStyle: const TextStyle(color: _hint),
                  border: InputBorder.none,
                  counterText: '',
                  contentPadding: const EdgeInsets.fromLTRB(14, 14, 14, 8),
                ),
              ),
              Align(
                alignment: Alignment.centerRight,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(0, 0, 12, 10),
                  child: Text(
                    '${controller.text.length}/500',
                    style: const TextStyle(color: _hint, fontSize: 12),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _chip(IconData icon, String label) {
    final on = _selected.contains(label);
    return GestureDetector(
      onTap: _sending
          ? null
          : () => setState(
              () => on ? _selected.remove(label) : _selected.add(label),
            ),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: on ? const Color(0xFF2A150E) : _surface,
          borderRadius: BorderRadius.circular(99),
          border: Border.all(color: on ? _orange : _stroke),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: _orange, size: 16),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                color: on ? _orange : Colors.white,
                fontWeight: FontWeight.w600,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _references() {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _stroke),
      ),
      child: SizedBox(
        height: 78,
        child: ListView(
          scrollDirection: Axis.horizontal,
          children: [
            _uploadTile(),
            for (var i = 0; i < _refs.length; i++) ...[
              const SizedBox(width: 8),
              _refThumb(i),
            ],
            if (_refs.isNotEmpty && _refs.length < 10) ...[
              const SizedBox(width: 8),
              _plusTile(),
            ],
          ],
        ),
      ),
    );
  }

  Widget _uploadTile() {
    return GestureDetector(
      onTap: _sending ? null : _pickRefs,
      child: Container(
        width: 168,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: _stroke),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 12),
        child: const Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.add_photo_alternate_outlined, color: _muted),
            SizedBox(height: 6),
            Text(
              'Upload reference images',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
                fontSize: 11,
              ),
            ),
            Text(
              'JPG, PNG, HEIC (Max 10 files)',
              style: TextStyle(color: _muted, fontSize: 10),
            ),
          ],
        ),
      ),
    );
  }

  Widget _plusTile() {
    return GestureDetector(
      onTap: _sending ? null : _pickRefs,
      child: Container(
        width: 72,
        height: 72,
        decoration: BoxDecoration(
          color: const Color(0xFF12161C),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: _stroke),
        ),
        child: const Icon(Icons.add, color: Colors.white, size: 28),
      ),
    );
  }

  Widget _refThumb(int index) {
    final file = _refs[index];
    return Stack(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: SizedBox(width: 72, height: 72, child: _fileThumb(file)),
        ),
        Positioned(
          top: 2,
          right: 2,
          child: Material(
            color: const Color(0xCC000000),
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: _sending
                  ? null
                  : () => setState(() => _refs.removeAt(index)),
              child: const Padding(
                padding: EdgeInsets.all(3),
                child: Icon(Icons.close, color: Colors.white, size: 14),
              ),
            ),
          ),
        ),
      ],
    );
  }

  String? _resolveUrl(String? url) {
    final raw = url?.trim();
    if (raw == null || raw.isEmpty || raw.toLowerCase() == 'null') {
      return null;
    }
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
      return raw;
    }
    final base = LensConfig.apiBaseUrl.replaceAll(RegExp(r'/api/?$'), '');
    if (raw.startsWith('/')) {
      return '$base$raw';
    }
    if (raw.startsWith('storage/')) {
      return '$base/$raw';
    }
    return '$base/storage/$raw';
  }

  Widget _thumb(String? url) {
    final resolved = _resolveUrl(url);
    if (!LensConfig.useNetwork || resolved == null) {
      return const ColoredBox(
        color: Color(0xFF1C1D22),
        child: Icon(Icons.image_outlined, color: _muted),
      );
    }
    return Image.network(
      resolved,
      fit: BoxFit.cover,
      errorBuilder: (_, __, ___) =>
          const ColoredBox(color: Color(0xFF1C1D22)),
    );
  }

  Widget _fileThumb(XFile file) {
    if (!kIsWeb && file.path.isNotEmpty) {
      return Image.file(
        File(file.path),
        fit: BoxFit.cover,
        errorBuilder: (_, __, ___) =>
            const ColoredBox(color: Color(0xFF1C1D22)),
      );
    }
    return FutureBuilder<Uint8List>(
      future: file.readAsBytes(),
      builder: (context, snapshot) {
        final bytes = snapshot.data;
        if (bytes == null) {
          return const ColoredBox(
            color: Color(0xFF1C1D22),
            child: Center(
              child: SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(strokeWidth: 2, color: _muted),
              ),
            ),
          );
        }
        return Image.memory(
          bytes,
          fit: BoxFit.cover,
          errorBuilder: (_, __, ___) =>
              const ColoredBox(color: Color(0xFF1C1D22)),
        );
      },
    );
  }

  Future<void> _pickRefs() async {
    if (_refs.length >= 10) {
      _toast('You can attach up to 10 reference images.');
      return;
    }
    try {
      final picked = await ImagePicker().pickMultiImage(imageQuality: 85);
      if (!mounted || picked.isEmpty) {
        return;
      }
      setState(() {
        _refs.addAll(picked.take(10 - _refs.length));
      });
    } catch (_) {
      if (mounted) {
        _toast('Could not open your photo library.');
      }
    }
  }

  Widget _footer() {
    final narrow = MediaQuery.sizeOf(context).width < 360;
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
      child: Column(
        children: [
          SizedBox(
            width: double.infinity,
            height: 54,
            child: FilledButton(
              onPressed: _canSend ? _send : null,
              style: FilledButton.styleFrom(
                backgroundColor: _orange,
                disabledBackgroundColor: const Color(0xFF4A2414),
                disabledForegroundColor: const Color(0x99FFFFFF),
                foregroundColor: Colors.white,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(horizontal: 18),
              ),
              child: _sending
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : Row(
                      children: [
                        Expanded(
                          child: Text(
                            narrow
                                ? 'Send request'
                                : 'Send Revision Request',
                            textAlign: TextAlign.center,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 16,
                              color: Colors.white,
                            ),
                          ),
                        ),
                        const Icon(
                          Icons.chevron_right_rounded,
                          size: 22,
                          color: Colors.white,
                        ),
                      ],
                    ),
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            height: 54,
            child: OutlinedButton(
              onPressed: _sending
                  ? null
                  : () => Navigator.of(context).pop(false),
              style: OutlinedButton.styleFrom(
                backgroundColor: Colors.transparent,
                foregroundColor: _orange,
                side: const BorderSide(color: _orange, width: 1.6),
                shape: const StadiumBorder(),
              ),
              child: const Text(
                'Cancel',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 16,
                  color: _orange,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<List<http.MultipartFile>> _buildReferenceFiles() async {
    final files = <http.MultipartFile>[];
    for (var i = 0; i < _refs.length; i++) {
      final ref = _refs[i];
      final field = 'references[$i]';
      final name = ref.name.isNotEmpty ? ref.name : 'reference_$i.jpg';
      if (kIsWeb || ref.path.isEmpty) {
        final bytes = await ref.readAsBytes();
        files.add(http.MultipartFile.fromBytes(field, bytes, filename: name));
      } else {
        files.add(
          await http.MultipartFile.fromPath(field, ref.path, filename: name),
        );
      }
    }
    return files;
  }

  Future<void> _send() async {
    if (!_canSend) {
      return;
    }
    if (widget.bookingId <= 0) {
      _toast('This booking is not ready for a revision request.');
      return;
    }

    final parts = <String>[
      if (_selected.isNotEmpty) 'Quick requests: ${_selected.join(', ')}',
      if (_change.text.trim().isNotEmpty) _change.text.trim(),
      if (_comment.text.trim().isNotEmpty)
        'Photo ${widget.fileName}: ${_comment.text.trim()}',
      if (_refs.isNotEmpty) '${_refs.length} reference image(s) attached.',
    ];
    final note = parts.join('\n').trim();
    if (note.isEmpty) {
      _toast('Describe the edit you need.');
      return;
    }

    setState(() => _sending = true);
    var message = 'Edit requested. The creator will upload a new version.';
    if (_live) {
      try {
        final body = <String, dynamic>{
          'note': note,
          'change': _change.text.trim(),
          'comment': _comment.text.trim(),
          'file_name': widget.fileName,
          'quick_requests': _selected.toList(),
        };
        final files = await _buildReferenceFiles();
        final payload = files.isEmpty
            ? await ApiClient().postJson(
                '/app/bookings/${widget.bookingId}/request-edit',
                body,
              )
            : await ApiClient().postForm(
                '/app/bookings/${widget.bookingId}/request-edit',
                body,
                files: files,
              );
        message = payload['message']?.toString() ?? message;
      } catch (error) {
        if (mounted) {
          setState(() => _sending = false);
          _toast(
            error is ApiException
                ? error.message
                : 'Could not send the request.',
          );
        }
        return;
      }
    }
    if (!mounted) {
      return;
    }
    _toast(message);
    Navigator.of(context).pop(true);
  }

  void _toast(String message) {
    final messenger = ScaffoldMessenger.maybeOf(context);
    if (messenger == null) {
      return;
    }
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }
}
