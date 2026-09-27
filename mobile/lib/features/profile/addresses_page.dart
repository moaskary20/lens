import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/book_draft.dart';
import 'package:lens/features/home/book_map_picker_page.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class AddressesPage extends StatefulWidget {
  const AddressesPage({super.key});

  @override
  State<AddressesPage> createState() => _AddressesPageState();
}

class _AddressesPageState extends State<AddressesPage> {
  final _api = ApiClient();
  List<_SavedAddress> _items = [];
  bool _loading = false;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!_live) {
      setState(() {
        _items = [
          const _SavedAddress(id: 1, label: 'Home', line: 'Zamalek, Cairo', city: 'Cairo', latitude: 30.0626, longitude: 31.2197),
        ];
      });
      return;
    }
    setState(() => _loading = true);
    try {
      final payload = await _api.getJson('/app/account/addresses');
      if (!mounted) {
        return;
      }
      setState(() {
        _items = (payload['addresses'] as List<dynamic>? ?? const [])
            .whereType<Map>()
            .map((item) => _SavedAddress.fromJson(Map<String, dynamic>.from(item)))
            .toList();
        _loading = false;
      });
    } catch (_) {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _add() async {
    final pin = await Navigator.of(context).push<PickedMapLocation>(
      MaterialPageRoute(builder: (_) => const BookMapPickerPage()),
    );
    if (pin == null || !mounted) {
      return;
    }
    final label = await showModalBottomSheet<String>(
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
                const Text('Save this pin as', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                const SizedBox(height: 12),
                for (final name in const ['Home', 'Work', 'Studio', 'Other'])
                  ListTile(
                    title: Text(name, style: const TextStyle(color: Colors.white)),
                    onTap: () => Navigator.pop(context, name),
                  ),
              ],
            ),
          ),
        );
      },
    );
    if (label == null) {
      return;
    }
    final city = pin.label.split(',').last.trim();
    final local = _SavedAddress(
      id: DateTime.now().millisecondsSinceEpoch,
      label: label,
      line: pin.label,
      city: city,
      latitude: pin.latitude,
      longitude: pin.longitude,
    );
    setState(() => _items = [..._items, local]);
    if (!_live) {
      return;
    }
    try {
      await _api.postJson('/app/account/addresses', {
        'label': label,
        'line': pin.label,
        'city': city,
        'latitude': pin.latitude,
        'longitude': pin.longitude,
        'is_default': _items.length == 1,
      });
      await _load();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$error')));
      }
    }
  }

  Future<void> _remove(_SavedAddress item) async {
    setState(() => _items = _items.where((address) => address.id != item.id).toList());
    if (_live) {
      try {
        await _api.deleteJson('/app/account/addresses/${item.id}');
      } catch (_) {}
    }
  }

  @override
  Widget build(BuildContext context) {
    return ProfileScaffold(
      title: 'Addresses',
      action: IconButton(
        onPressed: _add,
        icon: const Icon(Icons.add_rounded, color: LensColors.primary),
      ),
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
        children: [
          const Text('Pin shoot locations on Google Maps and keep more than one address.', style: TextStyle(color: Color(0xFF8E8B84))),
          const SizedBox(height: 16),
          if (_loading) const Center(child: CircularProgressIndicator(color: LensColors.primary)),
          for (final item in _items) _tile(item),
          const SizedBox(height: 8),
          FilledButton.icon(
            onPressed: _add,
            icon: const Icon(Icons.map_outlined),
            label: const Text('Add address from map', style: TextStyle(fontWeight: FontWeight.w800)),
            style: FilledButton.styleFrom(
              backgroundColor: LensColors.primary,
              foregroundColor: Colors.white,
              minimumSize: const Size.fromHeight(52),
              shape: const StadiumBorder(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _tile(_SavedAddress item) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: const Color(0xFF161412),
        borderRadius: BorderRadius.circular(16),
        child: ListTile(
          leading: const Icon(Icons.location_on_outlined, color: LensColors.primary),
          title: Text(item.label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
          subtitle: Text(item.line, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
          trailing: IconButton(
            onPressed: () => _remove(item),
            icon: const Icon(Icons.delete_outline_rounded, color: Color(0xFFFF4D4F)),
          ),
        ),
      ),
    );
  }
}

class _SavedAddress {
  const _SavedAddress({
    required this.id,
    required this.label,
    required this.line,
    this.city,
    this.latitude,
    this.longitude,
  });

  factory _SavedAddress.fromJson(Map<String, dynamic> json) {
    return _SavedAddress(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      label: json['label']?.toString() ?? 'Home',
      line: json['line']?.toString() ?? '',
      city: json['city']?.toString(),
      latitude: json['latitude'] is num ? (json['latitude'] as num).toDouble() : double.tryParse('${json['latitude']}'),
      longitude: json['longitude'] is num ? (json['longitude'] as num).toDouble() : double.tryParse('${json['longitude']}'),
    );
  }

  final int id;
  final String label;
  final String line;
  final String? city;
  final double? latitude;
  final double? longitude;
}
