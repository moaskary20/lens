import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
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
  static const _bg = Color(0xFF000000);
  static const _surface = Color(0xFF141414);
  static const _muted = Color(0xFF8E8B84);
  static const _hint = Color(0xFF6F6C66);
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
  bool get _canSend => _change.text.trim().isNotEmpty || _selected.isNotEmpty || _comment.text.trim().isNotEmpty;

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
                  const Text('Request an Edit', style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.15)),
                  const SizedBox(height: 6),
                  const Text('Tell your creator exactly what needs to change.', style: TextStyle(color: _muted, fontSize: 14)),
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
                  const Text('Quick requests', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 16,
                    runSpacing: 12,
                    children: [
                      for (final chip in _chips) _chip(chip.$1, chip.$2),
                    ],
                  ),
                  const SizedBox(height: 22),
                  const Text('Add reference images (optional)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
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
            onPressed: () => Navigator.of(context).pop(false),
            icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 30),
          ),
          const Expanded(
            child: Column(
              children: [
                Icon(Icons.auto_awesome, color: _orange, size: 18),
                Text('lens', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 20, height: 1)),
              ],
            ),
          ),
          const SizedBox(width: 48),
        ],
      ),
    );
  }

  Widget _fileCard() {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(color: _surface, borderRadius: BorderRadius.circular(16)),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: SizedBox(width: 54, height: 54, child: _thumb(widget.fileUrl)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.fileName, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                if (widget.vendorName.isNotEmpty)
                  Text(widget.vendorName, style: const TextStyle(color: _muted, fontSize: 13)),
                if (widget.vendorRole.isNotEmpty || widget.projectName.isNotEmpty)
                  Text(
                    widget.vendorRole.isNotEmpty ? widget.vendorRole : widget.projectName,
                    style: const TextStyle(color: _muted, fontSize: 12),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _box({required String label, required String hint, required TextEditingController controller}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 15)),
        const SizedBox(height: 8),
        Container(
          decoration: BoxDecoration(color: _surface, borderRadius: BorderRadius.circular(16)),
          child: Column(
            children: [
              TextField(
                controller: controller,
                maxLength: 500,
                maxLines: 3,
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
                  child: Text('${controller.text.length}/500', style: const TextStyle(color: _hint, fontSize: 12)),
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
      onTap: () => setState(() => on ? _selected.remove(label) : _selected.add(label)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: _orange, size: 18),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(color: on ? _orange : Colors.white, fontWeight: FontWeight.w600, fontSize: 13)),
        ],
      ),
    );
  }

  Widget _references() {
    return SizedBox(
      height: 78,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          _uploadTile(),
          for (final file in _refs) ...[
            const SizedBox(width: 8),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(width: 72, height: 72, child: _fileThumb(file)),
            ),
          ],
          const SizedBox(width: 8),
          _plusTile(),
        ],
      ),
    );
  }

  Widget _uploadTile() {
    return GestureDetector(
      onTap: _pickRefs,
      child: Container(
        width: 168,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFF3A3A3E)),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 12),
        child: const Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.add_photo_alternate_outlined, color: _muted),
            SizedBox(height: 6),
            Text('Upload reference images', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 11)),
            Text('JPG, PNG, HEIC (10 files)', style: TextStyle(color: _muted, fontSize: 10)),
          ],
        ),
      ),
    );
  }

  Widget _plusTile() {
    return GestureDetector(
      onTap: _pickRefs,
      child: Container(
        width: 72,
        height: 72,
        decoration: BoxDecoration(color: _surface, borderRadius: BorderRadius.circular(12)),
        child: const Icon(Icons.add, color: Colors.white, size: 28),
      ),
    );
  }

  Widget _thumb(String? url) {
    if (!LensConfig.useNetwork || url == null || url.isEmpty) {
      return const ColoredBox(color: Color(0xFF1C1D22), child: Icon(Icons.image_outlined, color: _muted));
    }
    return Image.network(url, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: Color(0xFF1C1D22)));
  }

  Widget _fileThumb(XFile file) {
    if (kIsWeb) {
      return const ColoredBox(color: Color(0xFF1C1D22));
    }
    return Image.file(File(file.path), fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: Color(0xFF1C1D22)));
  }

  Future<void> _pickRefs() async {
    if (_refs.length >= 10) {
      return;
    }
    try {
      final picked = await ImagePicker().pickMultiImage(imageQuality: 85);
      if (picked.isEmpty) {
        return;
      }
      setState(() {
        _refs.addAll(picked.take(10 - _refs.length));
      });
    } catch (_) {}
  }

  Widget _footer() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
      child: Column(
        children: [
          SizedBox(
            width: double.infinity,
            height: 54,
            child: FilledButton(
              onPressed: _canSend && !_sending ? _send : null,
              style: FilledButton.styleFrom(
                backgroundColor: _orange,
                disabledBackgroundColor: const Color(0xFF4A2414),
                disabledForegroundColor: const Color(0x99FFFFFF),
                foregroundColor: Colors.white,
                shape: const StadiumBorder(),
                padding: const EdgeInsets.symmetric(horizontal: 22),
              ),
              child: _sending
                  ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Stack(
                      alignment: Alignment.center,
                      children: [
                        Text('Send Revision Request', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: Colors.white)),
                        Align(
                          alignment: Alignment.centerRight,
                          child: Icon(Icons.chevron_right_rounded, size: 22, color: Colors.white),
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
              onPressed: () => Navigator.of(context).pop(false),
              style: OutlinedButton.styleFrom(
                backgroundColor: Colors.transparent,
                foregroundColor: _orange,
                side: const BorderSide(color: _orange, width: 1.6),
                shape: const StadiumBorder(),
              ),
              child: const Text('Cancel', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: _orange)),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _send() async {
    final parts = <String>[
      if (_selected.isNotEmpty) 'Quick requests: ${_selected.join(', ')}',
      if (_change.text.trim().isNotEmpty) _change.text.trim(),
      if (_comment.text.trim().isNotEmpty) 'Photo ${widget.fileName}: ${_comment.text.trim()}',
      if (_refs.isNotEmpty) '${_refs.length} reference image(s) attached.',
    ];
    final note = parts.join('\n');
    setState(() => _sending = true);
    var message = 'Edit requested. The creator will upload a new version.';
    if (_live) {
      try {
        final payload = await ApiClient().postJson('/app/bookings/${widget.bookingId}/request-edit', {'note': note});
        message = payload['message']?.toString() ?? message;
      } catch (error) {
        if (mounted) {
          setState(() => _sending = false);
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error is ApiException ? error.message : 'Could not send the request.')));
        }
        return;
      }
    }
    if (!mounted) {
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    Navigator.of(context).pop(true);
  }
}
