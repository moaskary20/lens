import 'package:flutter/widgets.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:lens/core/egypt_governorates.dart';

class DevicePlace {
  const DevicePlace({
    required this.label,
    required this.latitude,
    required this.longitude,
  });

  final String label;
  final double latitude;
  final double longitude;
}

class DeviceLocation {
  const DeviceLocation._();

  static Future<Position> _position({
    LocationAccuracy accuracy = LocationAccuracy.low,
  }) async {
    if (!await Geolocator.isLocationServiceEnabled()) {
      throw const DeviceLocationException('Location services are turned off.');
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied) {
      throw const DeviceLocationException('Location permission was denied.');
    }
    if (permission == LocationPermission.deniedForever) {
      throw const DeviceLocationException(
        'Location permission is disabled. Enable it in your device settings.',
      );
    }

    return Geolocator.getCurrentPosition(
      locationSettings: LocationSettings(
        accuracy: accuracy,
        timeLimit: const Duration(seconds: 12),
      ),
    );
  }

  static Future<String> currentGovernorate() async {
    final position = await _position();
    final placemarks = await Geocoding(locale: const Locale('en'))
        .placemarkFromCoordinates(
      position.latitude,
      position.longitude,
      locale: const Locale('en'),
    );
    if (placemarks.isEmpty) {
      throw const DeviceLocationException(
        'Could not determine a governorate for your location.',
      );
    }

    final place = placemarks.first;
    final governorate = _cleanGovernorate(
      place.administrativeArea ??
          place.subAdministrativeArea ??
          place.locality ??
          place.subLocality,
    );
    if (governorate == null || governorate.isEmpty) {
      throw const DeviceLocationException(
        'Could not determine a governorate for your location.',
      );
    }
    return EgyptGovernorates.toEnglish(governorate);
  }

  static Future<DevicePlace> currentPlace() async {
    final position = await _position(accuracy: LocationAccuracy.high);
    var label =
        '${position.latitude.toStringAsFixed(5)}, ${position.longitude.toStringAsFixed(5)}';
    try {
      final placemarks = await Geocoding(locale: const Locale('en'))
          .placemarkFromCoordinates(
        position.latitude,
        position.longitude,
        locale: const Locale('en'),
      );
      if (placemarks.isNotEmpty) {
        final formatted = _formatPlace(placemarks.first);
        if (formatted.isNotEmpty) {
          label = formatted;
        }
      }
    } catch (_) {}

    return DevicePlace(
      label: label,
      latitude: position.latitude,
      longitude: position.longitude,
    );
  }

  static String _formatPlace(Placemark place) {
    final parts = <String>[];
    for (final value in [
      place.name,
      place.street,
      place.subLocality,
      place.locality,
      place.subAdministrativeArea,
      place.administrativeArea,
      place.country,
    ]) {
      final cleaned = value?.trim() ?? '';
      if (cleaned.isEmpty) {
        continue;
      }
      if (parts.isNotEmpty &&
          parts.last.toLowerCase() == cleaned.toLowerCase()) {
        continue;
      }
      parts.add(cleaned);
    }
    return parts.join(', ');
  }

  static String? _cleanGovernorate(String? value) {
    final cleaned = value?.trim();
    if (cleaned == null || cleaned.isEmpty) {
      return null;
    }

    return cleaned
        .replaceFirst(RegExp(r'\s+Governorate$', caseSensitive: false), '')
        .replaceFirst(RegExp(r'\s+Province$', caseSensitive: false), '')
        .replaceFirst(RegExp(r'\s+محافظة$', caseSensitive: false), '')
        .trim();
  }
}

class DeviceLocationException implements Exception {
  const DeviceLocationException(this.message);

  final String message;

  @override
  String toString() => message;
}
