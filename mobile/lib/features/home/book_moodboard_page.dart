import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/widgets/brand_logo.dart';
import 'package:lens/features/home/book_draft.dart';
import 'package:lens/features/home/book_moodboard.dart';

class BookMoodboardPage extends StatefulWidget {
  const BookMoodboardPage({super.key, required this.draft});

  final BookDraft draft;

  @override
  State<BookMoodboardPage> createState() => _BookMoodboardPageState();
}

class _BookMoodboardPageState extends State<BookMoodboardPage> with TickerProviderStateMixin {
  late final TextEditingController _brief;
  late BookMoodboard? _board;
  bool _generating = false;
  late final AnimationController _pulse;
  late final AnimationController _reveal;

  BookDraft get draft => widget.draft;

  @override
  void initState() {
    super.initState();
    _brief = TextEditingController(text: draft.brief);
    _board = draft.moodboard?.copy();
    _pulse = AnimationController(vsync: this, duration: const Duration(milliseconds: 1100))..repeat(reverse: true);
    _reveal = AnimationController(vsync: this, duration: const Duration(milliseconds: 720));
    if (_board != null) {
      _reveal.value = 1;
    }
  }

  @override
  void dispose() {
    _brief.dispose();
    _pulse.dispose();
    _reveal.dispose();
    super.dispose();
  }

  Future<void> _generate() async {
    final brief = _brief.text.trim().isEmpty ? draft.brief.trim() : _brief.text.trim();
    if (brief.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Add a short direction or project brief first.')),
      );
      return;
    }
    setState(() => _generating = true);
    HapticFeedback.lightImpact();
    await Future<void>.delayed(const Duration(milliseconds: 780));
    if (!mounted) {
      return;
    }
    final existingImages = _board?.images ?? <String>[];
    final next = BookMoodboard.fromTemplate(brief);
    next.images = List<String>.from(existingImages);
    if (existingImages.isNotEmpty) {
      next.source = 'mixed';
    }
    setState(() {
      _board = next;
      _generating = false;
    });
    _reveal
      ..reset()
      ..forward();
    HapticFeedback.selectionClick();
  }

  Future<void> _upload() async {
    try {
      final picked = await ImagePicker().pickMultiImage(imageQuality: 85);
      if (picked.isEmpty || !mounted) {
        return;
      }
      final board = _board ??
          BookMoodboard(
            title: 'Uploaded moodboard',
            style: '',
            mood: '',
            visualDirection: 'Visual references uploaded by the client.',
            colors: const [],
            shotList: const [],
            script: '',
            musicTone: '',
            source: 'uploaded',
          );
      for (final file in picked) {
        if (!board.images.contains(file.path)) {
          board.images.add(file.path);
        }
      }
      if (board.hasDirection && board.source == 'generated') {
        board.source = 'mixed';
      }
      setState(() => _board = board);
      if (_reveal.value < 1) {
        _reveal.forward();
      }
    } catch (_) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open the photo library.')),
      );
    }
  }

  void _removeImage(int index) {
    final board = _board;
    if (board == null || index < 0 || index >= board.images.length) {
      return;
    }
    setState(() => board.images.removeAt(index));
  }

  void _clear() {
    setState(() {
      _board = null;
      _reveal.value = 0;
    });
    draft.moodboard = null;
  }

  void _attach() {
    final board = _board;
    if (board == null || (!board.hasDirection && !board.hasImages)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Generate a direction or upload images first.')),
      );
      return;
    }
    board.attached = true;
    draft.moodboard = board.copy();
    if (_brief.text.trim().isNotEmpty && draft.brief.trim().isEmpty) {
      draft.brief = _brief.text.trim();
    }
    HapticFeedback.mediumImpact();
    Navigator.of(context).pop(true);
  }

  @override
  Widget build(BuildContext context) {
    final board = _board;
    final attached = draft.hasMoodboard;

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            _header(),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
                children: [
                  const Text(
                    'Lens AI Moodboard',
                    style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.08),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Optional — lock the look, palette, and shot direction before you confirm.',
                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.35),
                  ),
                  const SizedBox(height: 18),
                  _heroRail(),
                  const SizedBox(height: 16),
                  _briefField(),
                  const SizedBox(height: 14),
                  _actions(),
                  if (_generating) ...[
                    const SizedBox(height: 18),
                    _generatingStrip(),
                  ],
                  if (board != null && !_generating) ...[
                    const SizedBox(height: 18),
                    FadeTransition(
                      opacity: CurvedAnimation(parent: _reveal, curve: Curves.easeOut),
                      child: SlideTransition(
                        position: Tween<Offset>(begin: const Offset(0, 0.04), end: Offset.zero)
                            .animate(CurvedAnimation(parent: _reveal, curve: Curves.easeOutCubic)),
                        child: _preview(board),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            _footer(attached: attached, canAttach: board != null && (board.hasDirection || board.hasImages)),
          ],
        ),
      ),
    );
  }

  Widget _header() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 4, 8, 4),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 32),
          ),
          const Expanded(child: Center(child: BrandHeader(height: 36))),
          const SizedBox(width: 48),
        ],
      ),
    );
  }

  Widget _heroRail() {
    return AnimatedBuilder(
      animation: _pulse,
      builder: (context, child) {
        final glow = 0.18 + (_pulse.value * 0.22);
        return Container(
          height: 108,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF1C100C), Color(0xFF0D0D0F), Color(0xFF18120E)],
            ),
            border: Border.all(color: Color.fromRGBO(255, 90, 31, 0.35 + glow * 0.4)),
            boxShadow: [
              BoxShadow(
                color: LensColors.primary.withValues(alpha: glow * 0.55),
                blurRadius: 28,
                spreadRadius: -6,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          clipBehavior: Clip.antiAlias,
          child: Stack(
            children: [
              Positioned(
                right: -18,
                top: -24,
                child: Transform.rotate(
                  angle: -0.35,
                  child: Container(
                    width: 120,
                    height: 120,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: RadialGradient(
                        colors: [
                          LensColors.primary.withValues(alpha: 0.35 + glow * 0.25),
                          Colors.transparent,
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              Positioned.fill(
                child: CustomPaint(painter: _MoodGridPainter(progress: _pulse.value)),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
                child: Row(
                  children: [
                    Container(
                      width: 46,
                      height: 46,
                      decoration: BoxDecoration(
                        color: const Color(0x3324140E),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0x55FF5A1F)),
                      ),
                      child: const Icon(Icons.auto_awesome, color: LensColors.primary, size: 22),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            'Visual direction for the shoot',
                            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 15),
                          ),
                          SizedBox(height: 4),
                          Text(
                            'Generate a palette, mood, and shot list — or upload your own boards.',
                            style: TextStyle(color: Color(0xFFB0ABA3), fontSize: 12.5, height: 1.35),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _briefField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Direction brief', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13.5)),
        const SizedBox(height: 8),
        TextField(
          controller: _brief,
          maxLines: 3,
          style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.35),
          cursorColor: LensColors.primary,
          decoration: InputDecoration(
            hintText: 'Warm daylight portraits, soft motion, cream wardrobe…',
            hintStyle: const TextStyle(color: Color(0xFF6B6B70), fontSize: 13.5),
            filled: true,
            fillColor: const Color(0xFF141416),
            contentPadding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: Color(0xFF2A2A2E)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: LensColors.primary),
            ),
          ),
        ),
      ],
    );
  }

  Widget _actions() {
    return Column(
      children: [
        SizedBox(
          height: 52,
          width: double.infinity,
          child: FilledButton.icon(
            onPressed: _generating ? null : _generate,
            icon: const Icon(Icons.auto_awesome, size: 18),
            label: Text(
              _board?.hasDirection == true ? 'Regenerate with Lens AI' : 'Generate with Lens AI',
              style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
            ),
            style: FilledButton.styleFrom(
              backgroundColor: LensColors.primary,
              disabledBackgroundColor: const Color(0xFF3A2418),
              foregroundColor: Colors.white,
              elevation: 0,
              shape: const StadiumBorder(),
            ),
          ),
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 52,
          width: double.infinity,
          child: OutlinedButton.icon(
            onPressed: _generating ? null : _upload,
            icon: const Icon(Icons.photo_library_outlined, size: 18),
            label: const Text('Upload moodboard images', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14.5)),
            style: OutlinedButton.styleFrom(
              foregroundColor: Colors.white,
              side: const BorderSide(color: Color(0xFF3A3A3E), width: 1.2),
              shape: const StadiumBorder(),
            ),
          ),
        ),
      ],
    );
  }

  Widget _generatingStrip() {
    return AnimatedBuilder(
      animation: _pulse,
      builder: (context, _) {
        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          decoration: BoxDecoration(
            color: const Color(0xFF161412),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Color.fromRGBO(255, 90, 31, 0.25 + _pulse.value * 0.35)),
          ),
          child: Row(
            children: [
              SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  strokeWidth: 2.2,
                  color: LensColors.primary.withValues(alpha: 0.7 + _pulse.value * 0.3),
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'Composing palette, mood, and shot list…',
                  style: TextStyle(color: Color(0xFFD0CBC3), fontWeight: FontWeight.w600, fontSize: 13),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _preview(BookMoodboard board) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (board.colors.isNotEmpty) ...[
          _palette(board.colors),
          const SizedBox(height: 12),
        ],
        Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
          decoration: BoxDecoration(
            color: const Color(0xFF141416),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: const Color(0xFF2A2A2E)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      board.title.isEmpty ? 'Moodboard' : board.title,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16),
                    ),
                  ),
                  if (board.mood.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                      decoration: BoxDecoration(
                        color: const Color(0xFF24140E),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: const Color(0x55FF5A1F)),
                      ),
                      child: Text(
                        board.mood.split(',').first.trim(),
                        style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 11.5),
                      ),
                    ),
                ],
              ),
              if (board.style.isNotEmpty) ...[
                const SizedBox(height: 10),
                Text(board.style, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 13, height: 1.4)),
              ],
              if (board.visualDirection.isNotEmpty) ...[
                const SizedBox(height: 12),
                const Text('Visual direction', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12, fontWeight: FontWeight.w600)),
                const SizedBox(height: 4),
                Text(board.visualDirection, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13.5, height: 1.4)),
              ],
              if (board.shotList.isNotEmpty) ...[
                const SizedBox(height: 14),
                const Text('Shot list', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12, fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    for (var i = 0; i < board.shotList.length; i++)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1A1B1F),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFF2A2A2E)),
                        ),
                        child: Text(
                          '${i + 1}. ${board.shotList[i]}',
                          style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                        ),
                      ),
                  ],
                ),
              ],
              if (board.musicTone.isNotEmpty) ...[
                const SizedBox(height: 14),
                Row(
                  children: [
                    const Icon(Icons.music_note_rounded, color: Color(0xFF8E8B84), size: 16),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(board.musicTone, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12.5)),
                    ),
                  ],
                ),
              ],
              if (board.script.isNotEmpty) ...[
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF0F0F11),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Text(
                    board.script,
                    style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 12.5, height: 1.45, fontStyle: FontStyle.italic),
                  ),
                ),
              ],
            ],
          ),
        ),
        if (board.images.isNotEmpty) ...[
          const SizedBox(height: 14),
          const Text('Uploaded references', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13.5)),
          const SizedBox(height: 8),
          SizedBox(
            height: 84,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: board.images.length,
              separatorBuilder: (_, _) => const SizedBox(width: 10),
              itemBuilder: (context, index) => _thumb(board.images[index], () => _removeImage(index)),
            ),
          ),
        ],
      ],
    );
  }

  Widget _palette(List<BookMoodColor> colors) {
    return SizedBox(
      height: 72,
      child: Row(
        children: [
          for (var i = 0; i < colors.length; i++) ...[
            if (i > 0) const SizedBox(width: 8),
            Expanded(
              child: AnimatedBuilder(
                animation: _reveal,
                builder: (context, child) {
                  final local = Curves.easeOutCubic.transform(((_reveal.value * colors.length) - i).clamp(0.0, 1.0));
                  return Opacity(
                    opacity: local,
                    child: Transform.translate(
                      offset: Offset(0, (1 - local) * 10),
                      child: child,
                    ),
                  );
                },
                child: Column(
                  children: [
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: colors[i].color,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0x33FFFFFF)),
                        ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      colors[i].name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 10.5, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _thumb(String path, VoidCallback onRemove) {
    return SizedBox(
      width: 84,
      height: 84,
      child: Stack(
        children: [
          Positioned.fill(
            child: Container(
              decoration: BoxDecoration(color: LensColors.graphite, borderRadius: BorderRadius.circular(14)),
              clipBehavior: Clip.antiAlias,
              child: _imageFor(path),
            ),
          ),
          Positioned(
            top: 4,
            right: 4,
            child: GestureDetector(
              onTap: onRemove,
              child: Container(
                width: 22,
                height: 22,
                decoration: const BoxDecoration(color: Color(0xCC141416), shape: BoxShape.circle),
                child: const Icon(Icons.close, color: Colors.white, size: 13),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _imageFor(String path) {
    const fallback = ColoredBox(color: LensColors.graphite);
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return Image.network(path, fit: BoxFit.cover, errorBuilder: (_, _, _) => fallback);
    }
    if (kIsWeb) {
      return fallback;
    }
    return Image.file(File(path), fit: BoxFit.cover, errorBuilder: (_, _, _) => fallback);
  }

  Widget _footer({required bool attached, required bool canAttach}) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
      child: Column(
        children: [
          SizedBox(
            height: 54,
            width: double.infinity,
            child: FilledButton(
              onPressed: canAttach ? _attach : null,
              style: FilledButton.styleFrom(
                backgroundColor: LensColors.primary,
                disabledBackgroundColor: const Color(0xFF2A2A2E),
                foregroundColor: Colors.white,
                elevation: 0,
                shape: const StadiumBorder(),
              ),
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Text(
                    attached ? 'Update attachment' : 'Attach to booking',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
                  ),
                  const Align(
                    alignment: Alignment.centerRight,
                    child: Icon(Icons.chevron_right_rounded, size: 26),
                  ),
                ],
              ),
            ),
          ),
          if (_board != null) ...[
            const SizedBox(height: 4),
            TextButton(
              onPressed: _clear,
              child: Text(
                attached ? 'Remove from booking' : 'Clear moodboard',
                style: const TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _MoodGridPainter extends CustomPainter {
  const _MoodGridPainter({required this.progress});

  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0x14FF5A1F)
      ..strokeWidth = 1;
    const step = 18.0;
    final shift = progress * step;
    for (var x = -step; x < size.width + step; x += step) {
      canvas.drawLine(Offset(x + shift, 0), Offset(x + shift - 12, size.height), paint);
    }
    final orb = Paint()
      ..shader = RadialGradient(
        colors: [
          Color.fromRGBO(255, 90, 31, 0.12 + progress * 0.08),
          Colors.transparent,
        ],
      ).createShader(Rect.fromCircle(center: Offset(size.width * 0.78, size.height * 0.35), radius: 70));
    canvas.drawCircle(Offset(size.width * 0.78, size.height * 0.35), 70, orb);
  }

  @override
  bool shouldRepaint(covariant _MoodGridPainter oldDelegate) => oldDelegate.progress != progress;
}
