import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/bookings/approve_delivery_sheet.dart';
import 'package:lens/features/bookings/deliverables_page.dart';
import 'package:lens/features/bookings/request_edit_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/vendor_profile_page.dart';
import 'package:lens/features/profile/app_settings_page.dart';

class BookingsPage extends StatefulWidget {
  const BookingsPage({super.key, required this.home, this.onBack, this.asRoute = false});

  final HomeData home;
  final VoidCallback? onBack;
  final bool asRoute;

  @override
  State<BookingsPage> createState() => _BookingsPageState();
}

class _BookingsPageState extends State<BookingsPage> {
  List<_BookingItem> _items = [];
  bool _loading = false;
  String _tab = 'upcoming';

  @override
  void initState() {
    super.initState();
    SessionStore.instance.addListener(_onSession);
    _load();
  }

  @override
  void dispose() {
    SessionStore.instance.removeListener(_onSession);
    super.dispose();
  }

  void _onSession() {
    if (!mounted) {
      return;
    }
    _load();
  }

  Future<void> _load() async {
    final session = SessionStore.instance;
    if (session.isGuest) {
      setState(() {
        _items = [];
        _loading = false;
      });
      return;
    }
    setState(() => _loading = true);
    if (!LensConfig.useNetwork) {
      setState(() {
        _items = session.isVendor ? _demoVendor() : _demoClient();
        _loading = false;
      });
      return;
    }
    try {
      final payload = await ApiClient().getJson('/app/bookings');
      final items = (payload['bookings'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => _BookingItem.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      if (mounted) {
        setState(() {
          _items = items.isEmpty && session.isClient ? _demoClient() : items;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _items = session.isVendor ? _demoVendor() : _demoClient();
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (SessionStore.instance.isGuest && !widget.asRoute) {
      return LoginView(bootstrap: widget.home.registerCatalog);
    }
    if (SessionStore.instance.isGuest || SessionStore.instance.isClient) {
      return _ClientBookings(
        home: widget.home,
        items: _items,
        loading: _loading,
        tab: _tab,
        onTab: (tab) => setState(() => _tab = tab),
        onBack: widget.onBack,
      );
    }
    return SafeArea(
      child: Column(
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(20, 10, 20, 8),
            child: Align(
              alignment: Alignment.centerLeft,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Lens.', style: TextStyle(color: LensColors.primary, fontSize: 28, fontWeight: FontWeight.w800, height: 1)),
                  SizedBox(height: 6),
                  Text('Incoming bookings', style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800)),
                ],
              ),
            ),
          ),
          Expanded(child: _vendorBody()),
        ],
      ),
    );
  }

  Widget _vendorBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator(color: LensColors.primary));
    }
    if (_items.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.symmetric(horizontal: 36),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.inbox_outlined, color: LensColors.primary, size: 42),
              SizedBox(height: 14),
              Text('No requests yet', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
              SizedBox(height: 8),
              Text(
                'When a client books you, the request will land here.',
                textAlign: TextAlign.center,
                style: TextStyle(color: LensColors.slate, height: 1.4),
              ),
            ],
          ),
        ),
      );
    }
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 96),
      itemCount: _items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (context, index) => _VendorCard(item: _items[index]),
    );
  }
}

class _ClientBookings extends StatelessWidget {
  const _ClientBookings({
    required this.home,
    required this.items,
    required this.loading,
    required this.tab,
    required this.onTab,
    this.onBack,
  });

  final HomeData home;
  final List<_BookingItem> items;
  final bool loading;
  final String tab;
  final ValueChanged<String> onTab;
  final VoidCallback? onBack;

  List<_BookingItem> get upcoming => items.where((item) => item.group == 'upcoming').toList();
  List<_BookingItem> get completed => items.where((item) => item.group == 'completed').toList();
  List<_BookingItem> get canceled => items.where((item) => item.group == 'canceled').toList();

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 8, 0),
            child: Row(
              children: [
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Lens.', style: TextStyle(color: LensColors.primary, fontSize: 30, fontWeight: FontWeight.w800, height: 1)),
                      SizedBox(height: 3),
                      Text('FIND. BOOK. CREATE.', style: TextStyle(color: LensColors.slate, fontSize: 9, letterSpacing: 1.5, fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
                IconButton(
                  onPressed: () => Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => AppSettingsPage(home: home))),
                  icon: const Icon(Icons.settings_outlined, color: LensColors.cream),
                ),
                if (home.on('notifications'))
                  IconButton(
                    onPressed: () => openNotifications(context, home),
                    icon: const Icon(Icons.notifications_none_rounded, color: LensColors.cream),
                  ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 4, 20, 0),
            child: Row(
              children: [
                IconButton(
                  onPressed: onBack ?? (Navigator.of(context).canPop() ? () => Navigator.of(context).pop() : null),
                  icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 30),
                ),
                const Text('My Bookings', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
            child: Row(
              children: [
                _tabChip('Upcoming', upcoming.length, 'upcoming'),
                _tabChip('Completed', completed.length, 'completed'),
                _tabChip('Canceled', canceled.length, 'canceled'),
              ],
            ),
          ),
          Expanded(
            child: loading
                ? const Center(child: CircularProgressIndicator(color: LensColors.primary))
                : ListView(
                    padding: const EdgeInsets.fromLTRB(20, 18, 20, 96),
                    children: _sections(),
                  ),
          ),
        ],
      ),
    );
  }

  List<Widget> _sections() {
    if (tab == 'completed') {
      return [
        _sectionTitle('Completed bookings', null),
        if (completed.isEmpty) _empty('No completed bookings yet') else ...completed.map(_card),
      ];
    }
    if (tab == 'canceled') {
      return [
        _sectionTitle('Canceled bookings', null),
        if (canceled.isEmpty) _empty('No canceled bookings yet') else ...canceled.map(_card),
      ];
    }
    return [
      _sectionTitle('Upcoming bookings', null),
      if (upcoming.isEmpty) _empty('No upcoming bookings yet') else ...upcoming.map(_card),
      if (completed.isNotEmpty) ...[
        const SizedBox(height: 18),
        _sectionTitle('Completed bookings', () => onTab('completed')),
        _card(completed.first),
      ],
      if (canceled.isNotEmpty) ...[
        const SizedBox(height: 18),
        _sectionTitle('Canceled bookings', () => onTab('canceled')),
        _card(canceled.first),
      ],
    ];
  }

  Widget _tabChip(String label, int count, String value) {
    final selected = tab == value;
    return Expanded(
      child: GestureDetector(
        onTap: () => onTab(value),
        child: Column(
          children: [
            Text(
              '$label ($count)',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: selected ? Colors.white : const Color(0xFF8E8B84),
                fontWeight: FontWeight.w700,
                fontSize: 13,
              ),
            ),
            const SizedBox(height: 8),
            Container(height: 2, color: selected ? LensColors.primary : Colors.transparent),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(String title, VoidCallback? onViewAll) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Expanded(child: Text(title, style: const TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800))),
          if (onViewAll != null)
            GestureDetector(
              onTap: onViewAll,
              child: const Row(
                children: [
                  Text('View all', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 13)),
                  Icon(Icons.chevron_right_rounded, color: LensColors.primary, size: 18),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _empty(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Text(text, style: const TextStyle(color: Color(0xFF8E8B84))),
    );
  }

  Widget _card(_BookingItem item) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: _ClientCard(item: item, home: home),
    );
  }
}

class _ClientCard extends StatelessWidget {
  const _ClientCard({required this.item, required this.home});

  final _BookingItem item;
  final HomeData home;

  VendorCard get vendor => item.vendor ?? item.fallbackVendor;

  @override
  Widget build(BuildContext context) {
    final colors = item.badgeColors;
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFF121212),
        borderRadius: BorderRadius.circular(22),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              GestureDetector(onTap: () => _openVendor(context), child: _VendorPhoto(url: item.photo ?? vendor.profilePhotoUrl ?? vendor.coverUrl)),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: GestureDetector(
                            onTap: () => _openVendor(context),
                            child: Text(
                              item.counterpart,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16),
                            ),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(99),
                            border: Border.all(color: colors.$1),
                          ),
                          child: Text(item.badge, style: TextStyle(color: colors.$1, fontSize: 11, fontWeight: FontWeight.w700)),
                        ),
                        const SizedBox(width: 2),
                        PopupMenuButton<String>(
                          tooltip: 'Booking actions',
                          padding: EdgeInsets.zero,
                          color: const Color(0xFF1A1A1A),
                          icon: const Icon(Icons.more_horiz, color: Color(0xFF8E8B84), size: 20),
                          onSelected: (value) => _menu(context, value),
                          itemBuilder: (context) => const [
                            PopupMenuItem(value: 'deliverables', child: Text('Deliverables', style: TextStyle(color: Colors.white))),
                            PopupMenuItem(value: 'request_edit', child: Text('Request edit', style: TextStyle(color: Colors.white))),
                            PopupMenuItem(value: 'approve', child: Text('Approved delivery', style: TextStyle(color: Colors.white))),
                            PopupMenuItem(value: 'refuse', child: Text('Refuse', style: TextStyle(color: Colors.white))),
                          ],
                        ),
                      ],
                    ),
                    if (item.role.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(item.role, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
                    ],
                    const SizedBox(height: 10),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        Expanded(
                          child: Column(
                            children: [
                              _meta(Icons.calendar_today_outlined, item.dateLabel),
                              _meta(Icons.access_time_rounded, item.timeLabel),
                              _meta(Icons.location_on_outlined, item.location),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        _action(item),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          GestureDetector(
            onTap: () => _openProject(context),
            child: Text(
              item.displayProject,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: LensColors.primary, fontSize: 14, fontWeight: FontWeight.w700, height: 1.25),
            ),
          ),
        ],
      ),
    );
  }

  Widget _meta(IconData icon, String text) {
    if (text.isEmpty) {
      return const SizedBox.shrink();
    }
    return Padding(
      padding: const EdgeInsets.only(bottom: 5),
      child: Row(
        children: [
          Icon(icon, color: const Color(0xFF8E8B84), size: 15),
          const SizedBox(width: 8),
          Expanded(child: Text(text, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13))),
        ],
      ),
    );
  }

  Widget _action(_BookingItem item) {
    final cancel = item.group == 'upcoming';
    return OutlinedButton.icon(
      onPressed: () {},
      icon: Icon(cancel ? Icons.delete_outline_rounded : Icons.chevron_right_rounded, size: 16),
      label: Text(cancel ? 'Cancel Booking' : 'View Details', style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12)),
      style: OutlinedButton.styleFrom(
        foregroundColor: cancel ? const Color(0xFFFF3B30) : Colors.white,
        side: BorderSide(color: cancel ? const Color(0xFFFF3B30) : const Color(0xFF3A3A3E)),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        shape: const StadiumBorder(),
      ),
    );
  }

  void _openVendor(BuildContext context) {
    openVendorProfile(context, vendor, home, booked: true);
  }

  Future<void> _openProject(BuildContext context) {
    return Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => DeliverablesPage(
          bookingId: item.id,
          projectName: item.displayProject,
          vendorName: item.counterpart,
          vendorRole: item.role,
          vendorPhoto: item.photo,
          dateLabel: item.dateLabel,
          total: item.total,
        ),
      ),
    );
  }

  Future<void> _menu(BuildContext context, String value) async {
    switch (value) {
      case 'deliverables':
        await _openProject(context);
      case 'request_edit':
        await Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => RequestEditPage(
              bookingId: item.id,
              fileName: item.displayProject,
              fileUrl: item.photo,
              vendorName: item.counterpart,
              vendorRole: item.role,
              projectName: item.projectName,
            ),
          ),
        );
      case 'approve':
        final ok = await showApproveDeliverySheet(
          context,
          vendorName: item.counterpart,
          vendorRole: item.role,
          vendorPhoto: item.photo,
          projectName: item.displayProject,
          total: item.total,
        );
        if (ok && context.mounted) {
          await _post(context, '/app/bookings/${item.id}/approve', const {}, 'Delivery approved. Files are unlocked.');
        }
      case 'refuse':
        await _promptAction(
          context,
          title: 'Refuse delivery',
          hint: 'Why are you refusing this delivery?',
          confirm: 'Refuse',
          path: '/app/bookings/${item.id}/refuse',
          field: 'reason',
          done: 'Delivery refused. Admin will review the complaint.',
        );
    }
  }

  Future<void> _promptAction(
    BuildContext context, {
    required String title,
    required String hint,
    required String confirm,
    required String path,
    required String field,
    required String done,
  }) async {
    final note = TextEditingController();
    final sent = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        backgroundColor: const Color(0xFF161412),
        title: Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
        content: TextField(
          controller: note,
          maxLines: 4,
          style: const TextStyle(color: Colors.white),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: Color(0xFF8E8B84)),
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: const Text('Cancel')),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, note.text.trim().isNotEmpty),
            style: FilledButton.styleFrom(backgroundColor: LensColors.primary),
            child: Text(confirm),
          ),
        ],
      ),
    );
    final text = note.text.trim();
    note.dispose();
    if (sent == true && text.isNotEmpty && context.mounted) {
      await _post(context, path, {field: text}, done);
    }
  }

  Future<bool> _confirm(BuildContext context, String message) async {
    return await showDialog<bool>(
          context: context,
          builder: (dialogContext) => AlertDialog(
            backgroundColor: const Color(0xFF161412),
            content: Text(message, style: const TextStyle(color: Colors.white)),
            actions: [
              TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: const Text('Cancel')),
              FilledButton(
                onPressed: () => Navigator.pop(dialogContext, true),
                style: FilledButton.styleFrom(backgroundColor: LensColors.primary),
                child: const Text('Approved delivery'),
              ),
            ],
          ),
        ) ??
        false;
  }

  Future<void> _post(BuildContext context, String path, Map<String, dynamic> body, String fallback) async {
    var message = fallback;
    if (LensConfig.useNetwork && SessionStore.instance.isClient) {
      try {
        final payload = await ApiClient().postJson(path, body);
        message = payload['message']?.toString() ?? fallback;
      } catch (error) {
        message = error is ApiException ? error.message : 'Could not update this booking.';
      }
    }
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
    }
  }
}

class _VendorPhoto extends StatelessWidget {
  const _VendorPhoto({this.url});

  final String? url;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: SizedBox(
        width: 96,
        height: 96,
        child: url == null
            ? const ColoredBox(color: LensColors.graphite, child: Icon(Icons.person, color: LensColors.cream))
            : Image.network(
                url!,
                fit: BoxFit.cover,
                alignment: Alignment.center,
                filterQuality: FilterQuality.high,
                errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite, child: Icon(Icons.person, color: LensColors.cream)),
              ),
      ),
    );
  }
}

class _VendorCard extends StatelessWidget {
  const _VendorCard({required this.item});

  final _BookingItem item;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF141518),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF2A2D34)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text(item.counterpart, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16))),
              Text(item.statusLabel, style: const TextStyle(color: LensColors.primary, fontSize: 11, fontWeight: FontWeight.w700)),
            ],
          ),
          const SizedBox(height: 6),
          Text(item.reference, style: const TextStyle(color: LensColors.slate, fontWeight: FontWeight.w700, fontSize: 12)),
          const SizedBox(height: 8),
          Text(item.when, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13)),
          if (item.location.isNotEmpty) Text(item.location, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13)),
          if (item.status == 'pending')
            const Padding(
              padding: EdgeInsets.only(top: 6),
              child: Text('Waiting for you to accept', style: TextStyle(color: Color(0xFFD0CBC3), fontSize: 12)),
            ),
        ],
      ),
    );
  }
}

class _BookingItem {
  const _BookingItem({
    required this.id,
    required this.reference,
    required this.status,
    required this.statusLabel,
    required this.badge,
    required this.group,
    required this.when,
    required this.dateLabel,
    required this.timeLabel,
    required this.package,
    required this.location,
    required this.total,
    required this.counterpart,
    required this.role,
    this.photo,
    this.projectName = '',
    this.vendorId,
    this.vendor,
    this.phone,
    this.whatsapp,
  });

  static String _projectNameOf(Map<String, dynamic> json) {
    for (final key in ['project_name', 'project', 'title']) {
      final value = json[key]?.toString().trim() ?? '';
      if (value.isNotEmpty && value.toLowerCase() != 'null') {
        return value;
      }
    }
    return '';
  }

  factory _BookingItem.fromJson(Map<String, dynamic> json) {
    final status = json['status']?.toString() ?? '';
    final group = json['group']?.toString() ?? _groupFor(status);
    final vendorJson = json['vendor'] is Map ? Map<String, dynamic>.from(json['vendor'] as Map) : null;
    return _BookingItem(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      reference: json['reference']?.toString() ?? '',
      status: status,
      statusLabel: json['status_label']?.toString() ?? status,
      badge: json['badge']?.toString() ?? _badgeFor(group, status),
      group: group,
      when: json['when']?.toString() ?? '',
      dateLabel: json['date_label']?.toString() ?? json['when']?.toString() ?? '',
      timeLabel: json['time_label']?.toString() ?? '',
      package: json['package']?.toString() ?? '',
      location: json['location']?.toString() ?? '',
      total: json['total'] is num ? (json['total'] as num).toDouble() : double.tryParse('${json['total']}') ?? 0,
      counterpart: json['counterpart']?.toString() ?? '',
      role: json['role']?.toString() ?? json['vendor_type']?.toString() ?? '',
      photo: json['photo']?.toString() ?? vendorJson?['profile_photo_url']?.toString(),
      projectName: _BookingItem._projectNameOf(json),
      vendorId: json['vendor_id'] is int ? json['vendor_id'] as int : int.tryParse('${json['vendor_id']}'),
      vendor: vendorJson == null ? null : VendorCard.fromJson(vendorJson),
      phone: json['phone']?.toString(),
      whatsapp: json['whatsapp']?.toString(),
    );
  }

  final int id;
  final String reference;
  final String status;
  final String statusLabel;
  final String badge;
  final String group;
  final String when;
  final String dateLabel;
  final String timeLabel;
  final String package;
  final String location;
  final double total;
  final String counterpart;
  final String role;
  final String? photo;
  final String projectName;
  String get displayProject {
    final name = projectName.trim();
    if (name.isNotEmpty && name.toLowerCase() != 'null') {
      return name;
    }
    final pack = package.trim();
    if (pack.isNotEmpty) {
      return '${pack[0].toUpperCase()}${pack.substring(1)} session';
    }
    return 'Creative session';
  }

  final int? vendorId;
  final VendorCard? vendor;
  final String? phone;
  final String? whatsapp;

  VendorCard get fallbackVendor {
    final name = counterpart.isEmpty ? 'Creator' : counterpart;
    final parts = name.trim().split(RegExp(r'\s+')).where((part) => part.isNotEmpty).toList();
    final initials = parts.isEmpty ? 'C' : parts.take(2).map((part) => part.substring(0, 1).toUpperCase()).join();
    final slug = switch (role.toLowerCase()) {
      final value when value.contains('studio') => 'studio',
      final value when value.contains('video') => 'videographer',
      final value when value.contains('food') => 'food_stylist',
      _ => 'photographer',
    };
    return VendorCard(
      id: vendorId ?? id,
      displayName: name,
      vendorTypeName: role,
      vendorType: slug,
      city: location.contains(',') ? location.split(',').first.trim() : 'Cairo',
      location: location,
      ratingAvg: 0,
      ratingCount: 0,
      badges: const [],
      coverUrl: photo,
      profilePhotoUrl: photo,
      initials: initials,
      tags: const [],
      startingFrom: total > 0 ? total : null,
      verified: true,
      latitude: null,
      longitude: null,
    );
  }

  (Color, Color) get badgeColors {
    return switch (group) {
      'canceled' => (const Color(0xFFFF4D4F), const Color(0xFFFF4D4F)),
      'completed' => (const Color(0xFFD0CBC3), const Color(0xFFD0CBC3)),
      _ => (const Color(0xFF3D8B5F), const Color(0xFF3D8B5F)),
    };
  }

  static String _groupFor(String status) {
    return switch (status) {
      'cancelled' || 'rejected' || 'failed' || 'refunded' => 'canceled',
      'delivered' || 'approved' || 'completed' => 'completed',
      _ => 'upcoming',
    };
  }

  static String _badgeFor(String group, String status) {
    return switch (group) {
      'canceled' => 'Canceled',
      'completed' => 'Completed',
      _ => status == 'pending' ? 'Pending' : 'Confirmed',
    };
  }
}

List<_BookingItem> _demoClient() {
  return [
    _BookingItem(
      id: 1,
      reference: 'LN-1003',
      status: 'accepted',
      statusLabel: 'Accepted',
      badge: 'Confirmed',
      group: 'upcoming',
      when: '20 Sep 2026 · 10:00',
      dateLabel: 'Saturday, Sep 20, 2026',
      timeLabel: '10:00 AM – 1:00 PM',
      package: 'half day',
      location: 'Cairo, Egypt',
      total: 1980,
      counterpart: 'Lana Mostafa',
      role: 'Food Stylist',
      projectName: 'Yasmin Hall wedding',
      photo: VendorPhotos.shots('food_stylist', 0).first,
    ),
    _BookingItem(
      id: 2,
      reference: 'LN-1004',
      status: 'accepted',
      statusLabel: 'Accepted',
      badge: 'Confirmed',
      group: 'upcoming',
      when: '21 Sep 2026 · 14:00',
      dateLabel: 'Sunday, Sep 21, 2026',
      timeLabel: '2:00 PM – 5:00 PM',
      package: 'half day',
      location: 'Cairo, Egypt',
      total: 2200,
      counterpart: 'Ahmed Zaki',
      role: 'Photographer',
      projectName: 'Brand portraits',
      photo: VendorPhotos.cover('photographer', 2),
    ),
    _BookingItem(
      id: 3,
      reference: 'LN-1005',
      status: 'accepted',
      statusLabel: 'Accepted',
      badge: 'Confirmed',
      group: 'upcoming',
      when: '23 Sep 2026 · 11:00',
      dateLabel: 'Tuesday, Sep 23, 2026',
      timeLabel: '11:00 AM – 3:00 PM',
      package: 'half day',
      location: 'Giza, Egypt',
      total: 2600,
      counterpart: 'Mariam Saad',
      role: 'Videographer',
      projectName: 'Product film',
      photo: VendorPhotos.cover('videographer', 1),
    ),
    _BookingItem(
      id: 4,
      reference: 'LN-1001',
      status: 'completed',
      statusLabel: 'Completed',
      badge: 'Completed',
      group: 'completed',
      when: '12 Sep 2026 · 10:00',
      dateLabel: 'Saturday, Sep 12, 2026',
      timeLabel: '10:00 AM – 1:00 PM',
      package: 'half day',
      location: 'Cairo, Egypt',
      total: 1980,
      counterpart: 'Omar Fathy',
      role: 'Food Stylist',
      projectName: 'Kitchen menu recap',
      photo: VendorPhotos.cover('food_stylist', 1),
    ),
    _BookingItem(
      id: 5,
      reference: 'LN-1006',
      status: 'cancelled',
      statusLabel: 'Cancelled',
      badge: 'Canceled',
      group: 'canceled',
      when: '5 Sep 2026 · 13:00',
      dateLabel: 'Friday, Sep 5, 2026',
      timeLabel: '1:00 PM – 4:00 PM',
      package: 'half day',
      location: 'Cairo, Egypt',
      total: 1600,
      counterpart: 'Sara Khaled',
      role: 'Food Photographer',
      projectName: 'Café launch',
      photo: VendorPhotos.cover('food_stylist', 2),
    ),
  ];
}

List<_BookingItem> _demoVendor() {
  return [
    _BookingItem(
      id: 1,
      reference: 'LN-1003',
      status: 'pending',
      statusLabel: 'Pending acceptance',
      badge: 'Pending',
      group: 'upcoming',
      when: '11 Oct 2026 · 11:00',
      dateLabel: 'Sunday, Oct 11, 2026',
      timeLabel: '11:00 AM – 2:00 PM',
      package: 'half day',
      location: 'Zamalek rooftop, Cairo',
      total: 1980,
      counterpart: 'Sarah Bennett',
      role: 'Client',
    ),
    _BookingItem(
      id: 2,
      reference: 'LN-1001',
      status: 'delivered',
      statusLabel: 'Delivered',
      badge: 'Completed',
      group: 'completed',
      when: '24 Sep 2026 · 10:00',
      dateLabel: 'Thursday, Sep 24, 2026',
      timeLabel: '10:00 AM – 4:00 PM',
      package: 'half day',
      location: 'Yasmin Hall, Cairo',
      total: 1980,
      counterpart: 'Sarah Bennett',
      role: 'Client',
    ),
  ];
}
