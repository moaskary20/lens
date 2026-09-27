import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/contact_launch.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/vendor_chat_page.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class MessagesPage extends StatefulWidget {
  const MessagesPage({super.key, required this.home});

  final HomeData home;

  @override
  State<MessagesPage> createState() => _MessagesPageState();
}

class _MessagesPageState extends State<MessagesPage> {
  final _api = ApiClient();
  List<_Project> _items = [];
  int? _selected;
  bool _loading = false;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;

  _Project? get _current {
    if (_items.isEmpty) {
      return null;
    }
    return _items.cast<_Project?>().firstWhere((item) => item?.id == _selected, orElse: () => _items.first);
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!_live) {
      final vendor = widget.home.popular.isEmpty ? null : widget.home.popular.first.vendors.firstOrNull;
      setState(() {
        _items = [
          _Project(
            id: 1,
            title: 'Yasmin Hall wedding',
            when: 'Saturday • 2:00 PM',
            location: 'Zamalek, Cairo',
            status: 'Confirmed',
            phone: '01022223344',
            whatsapp: '01022223344',
            vendor: vendor,
          ),
        ];
        _selected = 1;
      });
      return;
    }
    setState(() => _loading = true);
    try {
      final payload = await _api.getJson('/app/account/projects');
      if (!mounted) {
        return;
      }
      final items = (payload['projects'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => _Project.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      setState(() {
        _items = items;
        _selected = items.isEmpty ? null : items.first.id;
        _loading = false;
      });
    } catch (_) {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _call(_Project project) async {
    final opened = await ContactLaunch.open(ContactLaunch.tel(project.phone));
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Call ${project.phone}')));
    }
  }

  Future<void> _whatsApp(_Project project) async {
    final opened = await ContactLaunch.open(ContactLaunch.whatsapp(project.whatsapp));
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('WhatsApp ${project.whatsapp}')));
    }
  }

  void _chat(_Project project) {
    final vendor = project.vendor;
    if (vendor == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('This project has no vendor yet.')));
      return;
    }
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => VendorChatPage(
          vendor: vendor,
          projectName: project.title,
          dateLabel: project.when,
          location: project.location,
          status: project.status,
          bookingId: project.id,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final project = _current;
    return ProfileScaffold(
      title: 'Messages',
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
        children: [
          const Text('Choose the current project, then call, WhatsApp, or open Chat in App.', style: TextStyle(color: Color(0xFF8E8B84))),
          const SizedBox(height: 16),
          if (_loading) const Center(child: CircularProgressIndicator(color: LensColors.primary)),
          if (!_loading && _items.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 40),
              child: Text('No current projects yet. Book a creator first.', textAlign: TextAlign.center, style: TextStyle(color: Color(0xFF8E8B84))),
            ),
          for (final item in _items) _projectTile(item),
          if (project != null) ...[
            const SizedBox(height: 18),
            _contact(project),
          ],
        ],
      ),
    );
  }

  Widget _projectTile(_Project item) {
    final selected = item.id == _selected;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: selected ? const Color(0xFF24140E) : const Color(0xFF161412),
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: () => setState(() => _selected = item.id),
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              children: [
                Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off, color: selected ? LensColors.primary : const Color(0xFF8E8B84)),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(item.title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                      const SizedBox(height: 4),
                      Text(item.vendor?.displayName ?? 'Creator', style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
                      if (item.when.isNotEmpty) Text(item.when, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                    ],
                  ),
                ),
                Text(item.status, style: const TextStyle(color: Color(0xFF7DCEA0), fontWeight: FontWeight.w700, fontSize: 12)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _contact(_Project project) {
    return Column(
      children: [
        _pill(Icons.call_outlined, 'Call Now', Colors.black, const Color(0xFF3A3A3E), () => _call(project)),
        const SizedBox(height: 10),
        _pill(Icons.chat_outlined, 'WhatsApp', const Color(0xFF128C7E), const Color(0xFF128C7E), () => _whatsApp(project)),
        const SizedBox(height: 10),
        _pill(Icons.forum_outlined, 'Chat in App', LensColors.primary, LensColors.primary, () => _chat(project)),
      ],
    );
  }

  Widget _pill(IconData icon, String label, Color fill, Color border, VoidCallback onTap) {
    return Material(
      color: fill,
      shape: StadiumBorder(side: BorderSide(color: border)),
      child: InkWell(
        customBorder: const StadiumBorder(),
        onTap: onTap,
        child: SizedBox(
          height: 52,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: Colors.white, size: 18),
              const SizedBox(width: 8),
              Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
            ],
          ),
        ),
      ),
    );
  }
}

class _Project {
  const _Project({
    required this.id,
    required this.title,
    required this.when,
    required this.location,
    required this.status,
    required this.phone,
    required this.whatsapp,
    this.vendor,
  });

  factory _Project.fromJson(Map<String, dynamic> json) {
    final vendorJson = json['vendor'] is Map ? Map<String, dynamic>.from(json['vendor'] as Map) : null;
    return _Project(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      title: json['title']?.toString() ?? 'Session',
      when: json['when']?.toString() ?? '',
      location: json['location']?.toString() ?? '',
      status: json['status_label']?.toString() ?? 'Confirmed',
      phone: json['phone']?.toString() ?? '01000000000',
      whatsapp: json['whatsapp']?.toString() ?? json['phone']?.toString() ?? '01000000000',
      vendor: vendorJson == null ? null : VendorCard.fromJson(vendorJson),
    );
  }

  final int id;
  final String title;
  final String when;
  final String location;
  final String status;
  final String phone;
  final String whatsapp;
  final VendorCard? vendor;
}
