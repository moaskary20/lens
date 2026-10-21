import 'package:flutter/widgets.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';

class DeviceLocation {
  const DeviceLocation._();

  static Future<String> currentGovernorate() async {
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

    final position = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.low,
        timeLimit: Duration(seconds: 12),
      ),
    );
    final placemarks = await Geocoding(locale: const Locale('en'))
        .placemarkFromCoordinates(
      position.latitude,
      position.longitude,
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
    return governorate;
  }

  static String? _cleanGovernorate(String? value) {
    final cleaned = value?.trim();
    if (cleaned == null || cleaned.isEmpty) {
      return null;
    }

    return cleaned
        .replaceFirst(RegExp(r'\s+Governorate$', caseSensitive: false), '')
        .replaceFirst(RegExp(r'\s+Province$', caseSensitive: false), '')
        .trim();
  }
}

class DeviceLocationException implements Exception {
  const DeviceLocationException(this.message);

  final String message;

  @override
  String toString() => message;
}
