import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Vendor IDs the current client has booked — full names unlock only for these.
class BookedVendorsStore extends ChangeNotifier {
  BookedVendorsStore._();
  static final BookedVendorsStore instance = BookedVendorsStore._();

  static const _prefsKey = 'lens_booked_vendor_ids';

  final Set<int> _ids = <int>{};
  bool _hydrated = false;

  bool has(int vendorId) => vendorId > 0 && _ids.contains(vendorId);

  Future<void> hydrate() async {
    if (_hydrated) {
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getStringList(_prefsKey) ?? const <String>[];
    _ids
      ..clear()
      ..addAll(raw.map(int.tryParse).whereType<int>().where((id) => id > 0));
    _hydrated = true;
    notifyListeners();
  }

  void remember(int vendorId) {
    if (vendorId <= 0 || _ids.contains(vendorId)) {
      return;
    }
    _ids.add(vendorId);
    _persist();
    notifyListeners();
  }

  void rememberAll(Iterable<int> vendorIds) {
    var changed = false;
    for (final id in vendorIds) {
      if (id > 0 && _ids.add(id)) {
        changed = true;
      }
    }
    if (changed) {
      _persist();
      notifyListeners();
    }
  }

  void clear() {
    final had = _ids.isNotEmpty || _hydrated;
    _ids.clear();
    _hydrated = false;
    if (!had) {
      return;
    }
    _persist();
    notifyListeners();
  }

  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(
      _prefsKey,
      _ids.map((id) => id.toString()).toList(growable: false),
    );
  }
}
