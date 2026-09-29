import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/bookings/approve_delivery_sheet.dart';
import 'package:lens/features/bookings/request_edit_page.dart';

class DeliverablesPage extends StatefulWidget {
  const DeliverablesPage({
    super.key,
    required this.bookingId,
    required this.projectName,
    this.vendorName = '',
    this.vendorRole = '',
    this.vendorPhoto,
    this.dateLabel = '',
    this.total = 0,
  });

  final int bookingId;
  final String projectName;
  final String vendorName;
  final String vendorRole;
  final String? vendorPhoto;
  final String dateLabel;
  final double total;

  @override
  State<DeliverablesPage> createState() => _DeliverablesPageState();
}

class _DeliverablesPageState extends State<DeliverablesPage> {
  static const _danger = Color(0xFFFF3B30);

  final _pager = PageController();
  List<_Shot> _shots = [];
  int _index = 0;
  bool _loading = false;
  bool _unlocked = false;
  bool _watermarked = true;
  String? _vendorName;
  String? _vendorRole;
  String? _vendorPhoto;
  String? _projectName;
  String? _dateLabel;
  double _total = 0;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;
  bool get _protect => !_unlocked && _watermarked;
  _Shot get _current => _shots.isEmpty ? const _Shot(id: 0, name: 'File', version: 1) : _shots[_index.clamp(0, _shots.length - 1)];

  @override
  void initState() {
    super.initState();
    _vendorName = widget.vendorName;
    _vendorRole = widget.vendorRole;
    _vendorPhoto = widget.vendorPhoto;
    _projectName = widget.projectName;
    _dateLabel = widget.dateLabel;
    _total = widget.total;
    _load();
  }

  @override
  void dispose() {
    _pager.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    if (!_live) {
      setState(() => _shots = _demoShots());
      return;
    }
    setState(() => _loading = true);
    try {
      final payload = await ApiClient().getJson('/app/bookings/${widget.bookingId}/deliverables');
      if (!mounted) {
        return;
      }
      final preview = payload['preview'] is Map ? Map<String, dynamic>.from(payload['preview'] as Map) : const <String, dynamic>{};
      final files = (payload['deliverables'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => _Shot.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      setState(() {
        _unlocked = preview['downloads_unlocked'] == true || payload['watermarked'] == false;
        _watermarked = payload['watermarked'] != false;
        _shots = files;
        _vendorName = payload['vendor_name']?.toString().isNotEmpty == true ? payload['vendor_name'].toString() : _vendorName;
        _vendorRole = payload['vendor_type']?.toString().isNotEmpty == true ? payload['vendor_type'].toString() : _vendorRole;
        _projectName = payload['project_name']?.toString().isNotEmpty == true ? payload['project_name'].toString() : _projectName;
        _vendorPhoto = payload['vendor_photo']?.toString() ?? _vendorPhoto;
        _dateLabel = payload['date_label']?.toString().isNotEmpty == true ? payload['date_label'].toString() : _dateLabel;
        _total = payload['total'] is num ? (payload['total'] as num).toDouble() : double.tryParse('${payload['total']}') ?? _total;
        _loading = false;
      });
    } catch (_) {
      if (mounted) {
        setState(() {
          _shots = _demoShots();
          _loading = false;
        });
      }
    }
  }

  List<_Shot> _demoShots() {
    final pool = <String>[
      'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=1400&q=80',
      ...VendorPhotos.shots('food_stylist', 0),
    ];
    return List<_Shot>.generate(24, (index) {
      final n = (index + 1).toString().padLeft(2, '0');
      return _Shot(
        id: index + 1,
        name: 'Pasta_Shot_$n.jpg',
        version: 1,
        url: pool[index % pool.length],
        unlocked: false,
        watermarked: true,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.paddingOf(context).bottom;
    return Scaffold(
      backgroundColor: Colors.black,
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: LensColors.primary))
          : Column(
              children: [
                Expanded(child: _stage()),
                _fileName(),
                _creatorRow(),
                _actions(),
                SizedBox(height: bottom > 0 ? bottom : 10),
              ],
            ),
    );
  }

  Widget _stage() {
    final top = MediaQuery.paddingOf(context).top;
    return Stack(
      fit: StackFit.expand,
      children: [
        if (_shots.isEmpty)
          const ColoredBox(
            color: Color(0xFF141210),
            child: Center(child: Text('Waiting for project files.', style: TextStyle(color: Color(0xFF8E8B84)))),
          )
        else
          PageView.builder(
            controller: _pager,
            itemCount: _shots.length,
            onPageChanged: (index) => setState(() => _index = index),
            itemBuilder: (context, index) => _photo(_shots[index].url),
          ),
        if (_protect) const _LensProtectedWatermark(),
        Positioned(
          top: top + 6,
          left: 16,
          right: 16,
          child: Row(
            children: [
              _closeButton(),
              Expanded(
                child: Text(
                  '${_shots.isEmpty ? 0 : _index + 1} / ${_shots.isEmpty ? 0 : _shots.length}',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 16),
                ),
              ),
              const SizedBox(width: 40),
            ],
          ),
        ),
        if (_protect)
          const Positioned(
            left: 0,
            right: 0,
            bottom: 22,
            child: Center(child: _LockPill()),
          ),
      ],
    );
  }

  Widget _closeButton() {
    return Tooltip(
      message: 'Close',
      child: Material(
        color: const Color(0xCC1A1A1A),
        shape: const CircleBorder(),
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: () => Navigator.of(context).pop(),
          child: const SizedBox(
            width: 40,
            height: 40,
            child: Icon(Icons.close, color: Colors.white, size: 22),
          ),
        ),
      ),
    );
  }

  Widget _photo(String? url) {
    if (!LensConfig.useNetwork || url == null || url.isEmpty) {
      return const ColoredBox(
        color: Color(0xFF141210),
        child: Center(child: Icon(Icons.image_outlined, color: Color(0xFF8E8B84), size: 64)),
      );
    }
    return Image.network(
      url,
      fit: BoxFit.cover,
      alignment: Alignment.center,
      filterQuality: FilterQuality.high,
      errorBuilder: (_, __, ___) => const ColoredBox(color: Color(0xFF141210)),
    );
  }

  Widget _fileName() {
    return Align(
      alignment: Alignment.centerLeft,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 12),
        child: Text(
          _current.name,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700),
        ),
      ),
    );
  }

  Widget _creatorRow() {
    final name = (_vendorName ?? widget.vendorName).trim().isEmpty ? 'Creator' : (_vendorName ?? widget.vendorName);
    final session = (_projectName ?? widget.projectName).trim().isNotEmpty
        ? (_projectName ?? widget.projectName)
        : (_vendorRole ?? widget.vendorRole).trim();
    final date = (_dateLabel ?? widget.dateLabel).trim();
    final photo = _vendorPhoto ?? widget.vendorPhoto;
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
      child: Row(
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: LensColors.graphite,
            backgroundImage: LensConfig.useNetwork && photo != null && photo.isNotEmpty ? NetworkImage(photo) : null,
            child: LensConfig.useNetwork && photo != null && photo.isNotEmpty
                ? null
                : Text(name.substring(0, 1).toUpperCase(), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                if (session.isNotEmpty)
                  Text(session, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('Version ${_current.version}', style: const TextStyle(color: Color(0xFFB8B4AD), fontWeight: FontWeight.w600, fontSize: 13)),
              if (date.isNotEmpty) Text(date, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _actions() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: _pill(
              label: 'Refuse',
              foreground: Colors.white,
              background: _danger,
              icon: Icons.cancel,
              onTap: _refuse,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _pill(
              label: 'Approve Delivery',
              foreground: Colors.white,
              background: LensColors.primary,
              onTap: _approve,
              leading: const _ApproveMark(),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _pill(
              label: 'Request Edit',
              foreground: LensColors.primary,
              background: Colors.transparent,
              border: LensColors.primary,
              icon: Icons.ios_share_rounded,
              onTap: _requestEdit,
            ),
          ),
        ],
      ),
    );
  }

  Widget _pill({
    required String label,
    required Color foreground,
    required Color background,
    required VoidCallback onTap,
    IconData? icon,
    Widget? leading,
    Color? border,
  }) {
    return SizedBox(
      height: 48,
      child: Material(
        color: background,
        shape: StadiumBorder(side: BorderSide(color: border ?? background, width: 1.6)),
        child: InkWell(
          onTap: onTap,
          customBorder: const StadiumBorder(),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                leading ?? Icon(icon, color: foreground, size: 18),
                const SizedBox(width: 5),
                Flexible(
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(color: foreground, fontWeight: FontWeight.w800, fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _requestEdit() async {
    final sent = await Navigator.of(context).push<bool>(
      MaterialPageRoute<bool>(
        builder: (_) => RequestEditPage(
          bookingId: widget.bookingId,
          fileName: _current.name,
          fileUrl: _current.url,
          vendorName: _vendorName ?? widget.vendorName,
          vendorRole: _vendorRole ?? widget.vendorRole,
          projectName: _projectName ?? widget.projectName,
        ),
      ),
    );
    if (sent == true && mounted) {
      await _load();
    }
  }

  Future<void> _refuse() async {
    final name = (_vendorName ?? widget.vendorName).trim().isEmpty ? 'this creator' : (_vendorName ?? widget.vendorName);
    final ok = await showDialog<bool>(
          context: context,
          barrierColor: const Color(0x99000000),
          builder: (dialogContext) => _RefuseConfirm(vendorName: name),
        ) ??
        false;
    if (!ok || !mounted) {
      return;
    }
    await _post(
      '/app/bookings/${widget.bookingId}/refuse',
      {'reason': 'Client refused this delivery.'},
      'Delivery refused. Admin will review the complaint.',
    );
  }

  Future<void> _approve() async {
    final ok = await showApproveDeliverySheet(
      context,
      vendorName: _vendorName ?? widget.vendorName,
      vendorRole: _vendorRole ?? widget.vendorRole,
      vendorPhoto: _vendorPhoto ?? widget.vendorPhoto,
      projectName: _projectName ?? widget.projectName,
      total: _total > 0 ? _total : widget.total,
    );
    if (!ok || !mounted) {
      return;
    }
    await _post('/app/bookings/${widget.bookingId}/approve', const {}, 'Delivery approved. Files are unlocked.');
    if (mounted) {
      setState(() {
        _unlocked = true;
        _watermarked = false;
      });
    }
  }

  Future<void> _post(String path, Map<String, dynamic> body, String fallback) async {
    var message = fallback;
    if (_live) {
      try {
        final payload = await ApiClient().postJson(path, body);
        message = payload['message']?.toString() ?? fallback;
      } catch (error) {
        message = error is ApiException ? error.message : 'Could not update this booking.';
      }
    }
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    }
  }
}

class _ApproveMark extends StatelessWidget {
  const _ApproveMark();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 18,
      height: 18,
      decoration: const BoxDecoration(color: Color(0xFF1A1208), shape: BoxShape.circle),
      child: const Icon(Icons.check_rounded, color: Colors.white, size: 12),
    );
  }
}

class _LockPill extends StatelessWidget {
  const _LockPill();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: const Color(0xE61A1A1A),
        borderRadius: BorderRadius.circular(99),
      ),
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.lock_rounded, color: Colors.white, size: 16),
          SizedBox(width: 8),
          Text('Available after approval', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 14)),
        ],
      ),
    );
  }
}

class _LensProtectedWatermark extends StatelessWidget {
  const _LensProtectedWatermark();

  @override
  Widget build(BuildContext context) {
    return const IgnorePointer(
      child: CustomPaint(painter: _WatermarkPainter(), child: SizedBox.expand()),
    );
  }
}

class _WatermarkPainter extends CustomPainter {
  const _WatermarkPainter();

  @override
  void paint(Canvas canvas, Size size) {
    const cellW = 118.0;
    const cellH = 86.0;
    const lens = TextStyle(
      color: Color(0x6BFFFFFF),
      fontSize: 26,
      fontWeight: FontWeight.w400,
      height: 1,
      fontStyle: FontStyle.italic,
    );
    const protectedStyle = TextStyle(
      color: Color(0x6BFFFFFF),
      fontSize: 9,
      fontWeight: FontWeight.w800,
      letterSpacing: 1.8,
      height: 1,
    );

    canvas.save();
    canvas.translate(size.width / 2, size.height / 2);
    canvas.rotate(-0.06);
    canvas.translate(-size.width / 2 - 50, -size.height / 2 - 40);

    for (var y = 0.0; y < size.height + 160; y += cellH) {
      for (var x = 0.0; x < size.width + 160; x += cellW) {
        final lensPaint = TextPainter(
          text: const TextSpan(text: 'lens', style: lens),
          textDirection: TextDirection.ltr,
        )..layout();
        final markPaint = TextPainter(
          text: const TextSpan(text: 'PROTECTED', style: protectedStyle),
          textDirection: TextDirection.ltr,
        )..layout();
        lensPaint.paint(canvas, Offset(x + (cellW - lensPaint.width) / 2, y));
        markPaint.paint(canvas, Offset(x + (cellW - markPaint.width) / 2, y + 30));
      }
    }
    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _RefuseConfirm extends StatelessWidget {
  const _RefuseConfirm({required this.vendorName});

  final String vendorName;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 28),
      child: Container(
        padding: const EdgeInsets.fromLTRB(22, 28, 22, 20),
        decoration: BoxDecoration(
          color: const Color(0xFF141414),
          borderRadius: BorderRadius.circular(28),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 54,
              height: 54,
              decoration: const BoxDecoration(color: Color(0xFF2A1214), shape: BoxShape.circle),
              child: const Icon(Icons.close, color: Color(0xFFFF3B30), size: 30),
            ),
            const SizedBox(height: 18),
            const Text(
              'Are you sure you want to refuse this project?',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800, height: 1.25),
            ),
            const SizedBox(height: 10),
            Text(
              'This will decline the project from $vendorName. You can\'t undo this action.',
              textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.4),
            ),
            const SizedBox(height: 22),
            Row(
              children: [
                Expanded(
                  child: SizedBox(
                    height: 48,
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context, false),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.white,
                        side: const BorderSide(color: Color(0xFF3A3A3E)),
                        shape: const StadiumBorder(),
                      ),
                      child: const Text('Cancel', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: SizedBox(
                    height: 48,
                    child: FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFFFF3B30),
                        foregroundColor: Colors.white,
                        shape: const StadiumBorder(),
                      ),
                      child: const Text('Refuse', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Shot {
  const _Shot({
    required this.id,
    required this.name,
    required this.version,
    this.url,
    this.unlocked = false,
    this.watermarked = true,
  });

  factory _Shot.fromJson(Map<String, dynamic> json) {
    return _Shot(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      name: json['name']?.toString() ?? 'File',
      version: json['version'] is int ? json['version'] as int : int.tryParse('${json['version']}') ?? 1,
      url: json['url']?.toString(),
      unlocked: json['unlocked'] == true,
      watermarked: json['watermarked'] != false,
    );
  }

  final int id;
  final String name;
  final int version;
  final String? url;
  final bool unlocked;
  final bool watermarked;
}
