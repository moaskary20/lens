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
  static const _gold = Color(0xFFF5C451);
  static const _danger = Color(0xFFFF3B30);

  final _pager = PageController();
  List<_Shot> _shots = [];
  int _index = 0;
  bool _loading = false;
  bool _unlocked = false;
  String? _vendorName;
  String? _vendorRole;
  String? _vendorPhoto;
  String? _dateLabel;
  double _total = 0;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;
  _Shot get _current => _shots.isEmpty ? const _Shot(id: 0, name: 'File', version: 1) : _shots[_index.clamp(0, _shots.length - 1)];

  @override
  void initState() {
    super.initState();
    _vendorName = widget.vendorName;
    _vendorRole = widget.vendorRole;
    _vendorPhoto = widget.vendorPhoto;
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
        _unlocked = preview['downloads_unlocked'] == true;
        _shots = files.isEmpty ? _demoShots() : files;
        _vendorName = payload['vendor_name']?.toString().isNotEmpty == true ? payload['vendor_name'].toString() : _vendorName;
        _vendorRole = payload['vendor_type']?.toString().isNotEmpty == true ? payload['vendor_type'].toString() : _vendorRole;
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
      ...VendorPhotos.shots('food_stylist', 0).take(8),
      'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=1200&q=80',
      'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=1200&q=80',
    ];
    return List<_Shot>.generate(24, (index) {
      final n = (index + 1).toString().padLeft(2, '0');
      return _Shot(
        id: index + 1,
        name: 'Pasta_Shot_$n.jpg',
        version: 1,
        url: pool[index % pool.length],
        unlocked: _unlocked,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: SafeArea(
        child: _loading
            ? const Center(child: CircularProgressIndicator(color: LensColors.primary))
            : Column(
                children: [
                  _topBar(),
                  Expanded(child: _gallery()),
                  _fileName(),
                  _creatorRow(),
                  _actions(),
                ],
              ),
      ),
    );
  }

  Widget _topBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 4, 8, 8),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Close',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.close, color: Colors.white, size: 26),
          ),
          Expanded(
            child: Text(
              '${_index + 1} / ${_shots.isEmpty ? 1 : _shots.length}',
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 16),
            ),
          ),
          const SizedBox(width: 48),
        ],
      ),
    );
  }

  Widget _gallery() {
    if (_shots.isEmpty) {
      return const Center(child: Text('No files uploaded yet.', style: TextStyle(color: Color(0xFF8E8B84))));
    }
    return PageView.builder(
      controller: _pager,
      itemCount: _shots.length,
      onPageChanged: (index) => setState(() => _index = index),
      itemBuilder: (context, index) {
        final shot = _shots[index];
        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: Stack(
              fit: StackFit.expand,
              children: [
                _photo(shot.url),
                if (!_unlocked) const _ProtectedMark(),
                if (!_unlocked)
                  const Align(
                    alignment: Alignment.bottomCenter,
                    child: Padding(
                      padding: EdgeInsets.only(bottom: 22),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.lock_outline_rounded, color: Colors.white, size: 18),
                          SizedBox(width: 8),
                          Text('Available after approval', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 14)),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
        );
      },
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
      errorBuilder: (_, __, ___) => const ColoredBox(color: Color(0xFF141210)),
    );
  }

  Widget _fileName() {
    return Align(
      alignment: Alignment.centerLeft,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 12),
        child: Text(_current.name, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w700)),
      ),
    );
  }

  Widget _creatorRow() {
    final name = (_vendorName ?? widget.vendorName).trim().isEmpty ? 'Creator' : (_vendorName ?? widget.vendorName);
    final role = (_vendorRole ?? widget.vendorRole).trim();
    final date = (_dateLabel ?? widget.dateLabel).trim();
    final photo = _vendorPhoto ?? widget.vendorPhoto;
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
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
                if (role.isNotEmpty)
                  Text(role, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('Version ${_current.version}', style: const TextStyle(color: Color(0xFFB8B4AD), fontWeight: FontWeight.w600, fontSize: 13)),
              if (date.isNotEmpty) Text(date, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _actions() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 20, 14),
      child: Row(
        children: [
          _roundAction(color: _danger, icon: Icons.close, label: 'Refuse', onTap: _refuse, filled: true),
          _roundAction(color: _gold, icon: Icons.check, label: 'Approve Delivery', onTap: _approve, filled: true),
          _roundAction(color: LensColors.primary, icon: Icons.north_east_rounded, label: 'Request Edit', onTap: _requestEdit),
        ],
      ),
    );
  }

  Widget _roundAction({
    required Color color,
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    bool filled = false,
  }) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        behavior: HitTestBehavior.opaque,
        child: Column(
          children: [
            Container(
              width: 56,
              height: 56,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: filled ? color : Colors.transparent,
                border: Border.all(color: color, width: 2),
              ),
              child: Icon(icon, color: filled && color == _danger ? Colors.white : filled ? const Color(0xFF1A1208) : color, size: 24),
            ),
            const SizedBox(height: 8),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 12, height: 1.15),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _requestEdit() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RequestEditPage(
          bookingId: widget.bookingId,
          fileName: _current.name,
          fileUrl: _current.url,
          vendorName: _vendorName ?? widget.vendorName,
          vendorRole: _vendorRole ?? widget.vendorRole,
          projectName: widget.projectName,
        ),
      ),
    );
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
      projectName: widget.projectName,
      total: _total > 0 ? _total : widget.total,
    );
    if (!ok || !mounted) {
      return;
    }
    await _post('/app/bookings/${widget.bookingId}/approve', const {}, 'Delivery approved. Files are unlocked.');
    if (mounted) {
      setState(() => _unlocked = true);
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

class _ProtectedMark extends StatelessWidget {
  const _ProtectedMark();

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: Transform.rotate(
        angle: -0.52,
        child: OverflowBox(
          maxWidth: 980,
          maxHeight: 1200,
          child: Wrap(
            spacing: 28,
            runSpacing: 36,
            children: List<Widget>.generate(
              72,
              (_) => const Text(
                'lens   PROTECTED',
                style: TextStyle(
                  color: Color(0x66FFFFFF),
                  fontSize: 13,
                  letterSpacing: 1.4,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Shot {
  const _Shot({required this.id, required this.name, required this.version, this.url, this.unlocked = false});

  factory _Shot.fromJson(Map<String, dynamic> json) {
    return _Shot(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      name: json['name']?.toString() ?? 'File',
      version: json['version'] is int ? json['version'] as int : int.tryParse('${json['version']}') ?? 1,
      url: json['url']?.toString(),
      unlocked: json['unlocked'] == true,
    );
  }

  final int id;
  final String name;
  final int version;
  final String? url;
  final bool unlocked;
}
