import 'package:flutter/foundation.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';

class FavoritesStore extends ChangeNotifier {
  FavoritesStore._() {
    SessionStore.instance.addListener(_onSessionChanged);
  }

  static final FavoritesStore instance = FavoritesStore._();

  final Map<int, VendorCard> _cards = {};
  final Set<int> _ids = {};
  ApiClient _api = ApiClient();

  List<VendorCard> get vendors {
    if (SessionStore.instance.isGuest) {
      return const [];
    }
    final items = _ids.map((id) => _cards[id]).whereType<VendorCard>().toList();
    items.sort((a, b) => a.displayName.compareTo(b.displayName));
    return items;
  }

  int get count => SessionStore.instance.isGuest ? 0 : _ids.length;

  bool saved(int id) => !SessionStore.instance.isGuest && _ids.contains(id);

  void remember(Iterable<VendorCard> vendors) {
    for (final vendor in vendors) {
      _cards[vendor.id] = vendor;
    }
  }

  void hydrate(HomeData home) {
    remember(home.popular.expand((section) => section.vendors));
    if (SessionStore.instance.isGuest) {
      _ids.clear();
      notifyListeners();
      return;
    }
    _ids
      ..clear()
      ..addAll(home.favoriteIds);
    notifyListeners();
    if (LensConfig.useNetwork) {
      refresh();
    }
  }

  Future<void> refresh() async {
    if (SessionStore.instance.isGuest) {
      _ids.clear();
      notifyListeners();
      return;
    }
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      final payload = await _api.getJson('/app/favorites');
      final vendors = (payload['vendors'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => VendorCard.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      _ids
        ..clear()
        ..addAll(vendors.map((vendor) => vendor.id));
      remember(vendors);
      notifyListeners();
    } catch (_) {}
  }

  Future<void> toggle(VendorCard vendor) async {
    if (SessionStore.instance.isGuest) {
      return;
    }
    remember([vendor]);
    final adding = !_ids.contains(vendor.id);
    if (adding) {
      _ids.add(vendor.id);
    } else {
      _ids.remove(vendor.id);
    }
    notifyListeners();
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      if (adding) {
        await _api.postJson('/app/favorites/${vendor.id}');
      } else {
        await _api.deleteJson('/app/favorites/${vendor.id}');
      }
    } catch (_) {}
  }

  void _onSessionChanged() {
    if (SessionStore.instance.isGuest) {
      _ids.clear();
      notifyListeners();
      return;
    }
    refresh();
  }

  @visibleForTesting
  void reset() {
    _cards.clear();
    _ids.clear();
    _api = ApiClient();
    notifyListeners();
  }
}
