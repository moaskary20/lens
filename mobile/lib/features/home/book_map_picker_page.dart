import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:http/http.dart' as http;
import 'package:latlong2/latlong.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/book_draft.dart';

class BookMapPickerPage extends StatefulWidget {
  const BookMapPickerPage({
    super.key,
    this.initial,
    this.fallbackLabel = 'Cairo, Egypt',
    this.fallbackLatitude = 30.0444,
    this.fallbackLongitude = 31.2357,
  });

  final PickedMapLocation? initial;
  final String fallbackLabel;
  final double fallbackLatitude;
  final double fallbackLongitude;

  @override
  State<BookMapPickerPage> createState() => _BookMapPickerPageState();
}

class _BookMapPickerPageState extends State<BookMapPickerPage> {
  static const _fallbackPlaces = [
    PickedMapLocation(label: 'Zamalek, Cairo', latitude: 30.0626, longitude: 31.2197),
    PickedMapLocation(label: 'New Cairo, Cairo', latitude: 30.0300, longitude: 31.4700),
    PickedMapLocation(label: 'Maadi, Cairo', latitude: 29.9602, longitude: 31.2569),
    PickedMapLocation(label: 'Alexandria, Egypt', latitude: 31.2001, longitude: 29.9187),
    PickedMapLocation(label: 'Giza, Egypt', latitude: 30.0131, longitude: 31.2089),
  ];

  final _search = TextEditingController();
  final _map = MapController();
  late PickedMapLocation _picked;
  List<PickedMapLocation> _suggestions = const [];
  bool _searching = false;

  @override
  void initState() {
    super.initState();
    _picked = widget.initial ??
        PickedMapLocation(
          label: widget.fallbackLabel,
          latitude: widget.fallbackLatitude,
          longitude: widget.fallbackLongitude,
        );
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  LatLng get _point => LatLng(_picked.latitude, _picked.longitude);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(4, 4, 8, 8),
              child: Row(
                children: [
                  IconButton(
                    tooltip: 'Back',
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 32),
                  ),
                  const Expanded(
                    child: Text(
                      'Pin on Google Maps',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800),
                    ),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
              child: TextField(
                controller: _search,
                style: const TextStyle(color: Colors.white),
                textInputAction: TextInputAction.search,
                onSubmitted: (_) => _lookup(),
                decoration: InputDecoration(
                  hintText: 'Search Google Maps',
                  hintStyle: const TextStyle(color: Color(0xFF6B6B70)),
                  prefixIcon: const Icon(Icons.search, color: Color(0xFF8E8B84)),
                  suffixIcon: IconButton(
                    tooltip: 'Search',
                    onPressed: _lookup,
                    icon: _searching
                        ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: LensColors.primary))
                        : const Icon(Icons.arrow_forward, color: LensColors.primary),
                  ),
                  filled: true,
                  fillColor: const Color(0xFF141416),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFF2A2A2E))),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFF2A2A2E))),
                ),
              ),
            ),
            SizedBox(
              height: 42,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  for (final place in _fallbackPlaces) ...[
                    _chip(place),
                    const SizedBox(width: 8),
                  ],
                ],
              ),
            ),
            if (_suggestions.isNotEmpty)
              SizedBox(
                height: 120,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                  children: [
                    for (final place in _suggestions)
                      ListTile(
                        dense: true,
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.place_outlined, color: LensColors.primary),
                        title: Text(place.label, style: const TextStyle(color: Colors.white, fontSize: 13.5)),
                        onTap: () => _use(place),
                      ),
                  ],
                ),
              ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(18),
                  child: FlutterMap(
                    mapController: _map,
                    options: MapOptions(
                      initialCenter: _point,
                      initialZoom: 13,
                      onTap: (_, point) => _dropPin(point),
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&key=${LensConfig.googleMapsApiKey}',
                        userAgentPackageName: 'app.lens',
                      ),
                      MarkerLayer(
                        markers: [
                          Marker(
                            point: _point,
                            width: 36,
                            height: 36,
                            child: const Icon(Icons.location_on, color: LensColors.primary, size: 36),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Column(
                children: [
                  Text(_picked.label, textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 4),
                  const Text('Powered by Google Maps', style: TextStyle(color: Color(0xFF6B6B70), fontSize: 11.5)),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 52,
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: () => Navigator.of(context).pop(_picked),
                      style: FilledButton.styleFrom(backgroundColor: LensColors.primary, foregroundColor: Colors.white, shape: const StadiumBorder()),
                      child: const Text('Use this location', style: TextStyle(fontWeight: FontWeight.w800)),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _chip(PickedMapLocation place) {
    final selected = place.label == _picked.label;
    return GestureDetector(
      onTap: () => _use(place),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? const Color(0x33FF5A1F) : const Color(0xFF141416),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2A2E)),
        ),
        child: Text(place.label, style: TextStyle(color: selected ? Colors.white : const Color(0xFFB0ABA3), fontWeight: FontWeight.w600, fontSize: 12.5)),
      ),
    );
  }

  Future<void> _lookup() async {
    final query = _search.text.trim();
    if (query.isEmpty) {
      return;
    }
    setState(() => _searching = true);
    try {
      final uri = Uri.https('maps.googleapis.com', '/maps/api/place/autocomplete/json', {
        'input': query,
        'key': LensConfig.googleMapsApiKey,
        'components': 'country:eg',
      });
      final response = await http.get(uri).timeout(const Duration(seconds: 8));
      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      final predictions = (payload['predictions'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
      final places = <PickedMapLocation>[];
      for (final item in predictions.take(5)) {
        final placeId = item['place_id']?.toString();
        final description = item['description']?.toString() ?? query;
        if (placeId == null) {
          continue;
        }
        final details = await _details(placeId, description);
        if (details != null) {
          places.add(details);
        }
      }
      if (!mounted) {
        return;
      }
      setState(() {
        _suggestions = places.isEmpty
            ? _fallbackPlaces.where((place) => place.label.toLowerCase().contains(query.toLowerCase())).toList()
            : places;
        _searching = false;
      });
      if (_suggestions.isNotEmpty) {
        _use(_suggestions.first);
      }
    } catch (_) {
      if (!mounted) {
        return;
      }
      final local = _fallbackPlaces.where((place) => place.label.toLowerCase().contains(query.toLowerCase())).toList();
      setState(() {
        _suggestions = local;
        _searching = false;
      });
      if (local.isNotEmpty) {
        _use(local.first);
      }
    }
  }

  Future<PickedMapLocation?> _details(String placeId, String fallback) async {
    try {
      final uri = Uri.https('maps.googleapis.com', '/maps/api/place/details/json', {
        'place_id': placeId,
        'fields': 'geometry,formatted_address,name',
        'key': LensConfig.googleMapsApiKey,
      });
      final response = await http.get(uri).timeout(const Duration(seconds: 8));
      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      final result = payload['result'] as Map<String, dynamic>?;
      final location = result?['geometry'] is Map ? (result!['geometry'] as Map)['location'] : null;
      if (location is! Map) {
        return null;
      }
      final lat = (location['lat'] as num?)?.toDouble();
      final lng = (location['lng'] as num?)?.toDouble();
      if (lat == null || lng == null) {
        return null;
      }
      return PickedMapLocation(
        label: result?['formatted_address']?.toString() ?? result?['name']?.toString() ?? fallback,
        latitude: lat,
        longitude: lng,
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> _dropPin(LatLng point) async {
    var label = '${point.latitude.toStringAsFixed(4)}, ${point.longitude.toStringAsFixed(4)}';
    try {
      final uri = Uri.https('maps.googleapis.com', '/maps/api/geocode/json', {
        'latlng': '${point.latitude},${point.longitude}',
        'key': LensConfig.googleMapsApiKey,
      });
      final response = await http.get(uri).timeout(const Duration(seconds: 8));
      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      final results = (payload['results'] as List<dynamic>? ?? const []).whereType<Map>();
      final first = results.isEmpty ? null : results.first;
      final formatted = first?['formatted_address']?.toString();
      if (formatted != null && formatted.isNotEmpty) {
        label = formatted;
      }
    } catch (_) {}
    if (!mounted) {
      return;
    }
    _use(PickedMapLocation(label: label, latitude: point.latitude, longitude: point.longitude));
  }

  void _use(PickedMapLocation place) {
    setState(() {
      _picked = place;
      _suggestions = const [];
    });
    _map.move(LatLng(place.latitude, place.longitude), 14);
  }
}
