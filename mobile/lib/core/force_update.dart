import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

class ForceUpdatePolicy {
  const ForceUpdatePolicy({
    required this.force,
    required this.minVersion,
    required this.storeUrl,
    required this.title,
    required this.message,
  });

  static const playStoreUrl =
      'https://play.google.com/store/apps/details?id=app.lens.lens';

  factory ForceUpdatePolicy.fromBootstrap(Map<String, dynamic> bootstrap) {
    final raw = bootstrap['app_update'];
    final map = raw is Map
        ? Map<String, dynamic>.from(raw)
        : const <String, dynamic>{};
    final android = defaultTargetPlatform == TargetPlatform.android;
    final store = (android ? map['android_store_url'] : map['ios_store_url'])
            ?.toString()
            .trim() ??
        '';
    final configured = (android
                ? map['android_min_version']
                : map['ios_min_version'])
            ?.toString()
            .trim() ??
        '';
    final storeVersion = (android
                ? map['android_store_version']
                : map['ios_store_version'])
            ?.toString()
            .trim() ??
        '';
    final required = _higherVersion(configured, storeVersion);
    // Default to forced updates whenever a target version exists.
    final force = map.containsKey('force_update')
        ? map['force_update'] == true
        : required.isNotEmpty;
    return ForceUpdatePolicy(
      force: force,
      minVersion: required,
      storeUrl: store.isNotEmpty
          ? store
          : (android ? playStoreUrl : 'https://apps.apple.com'),
      title: map['title']?.toString().trim().isNotEmpty == true
          ? map['title'].toString().trim()
          : 'Update required',
      message: map['message']?.toString().trim().isNotEmpty == true
          ? map['message'].toString().trim()
          : 'A new version of Lens is available on the store. Please update before continuing.',
    );
  }

  static String _higherVersion(String left, String right) {
    if (left.isEmpty) {
      return right;
    }
    if (right.isEmpty) {
      return left;
    }
    return ForceUpdate.compareVersions(left, right) >= 0 ? left : right;
  }

  final bool force;
  final String minVersion;
  final String storeUrl;
  final String title;
  final String message;

  bool get hasRule => force && minVersion.isNotEmpty;
}

class ForceUpdate {
  ForceUpdate._();

  static bool _gateOpen = false;

  /// Compare dotted versions like 5.5.5 vs 5.5.6. Returns negative if [current] < [minimum].
  static int compareVersions(String current, String minimum) {
    List<int> parts(String value) {
      return value
          .split(RegExp(r'[^0-9]+'))
          .where((part) => part.isNotEmpty)
          .map(int.parse)
          .toList();
    }

    final a = parts(current);
    final b = parts(minimum);
    final len = a.length > b.length ? a.length : b.length;
    for (var i = 0; i < len; i++) {
      final left = i < a.length ? a[i] : 0;
      final right = i < b.length ? b[i] : 0;
      if (left != right) {
        return left.compareTo(right);
      }
    }
    return 0;
  }

  static Future<ForceUpdatePolicy?> loadPolicy({ApiClient? api}) async {
    if (!LensConfig.useNetwork) {
      return null;
    }
    try {
      final remote = await (api ?? ApiClient()).getJson('/app/bootstrap');
      return ForceUpdatePolicy.fromBootstrap(
        Map<String, dynamic>.from(remote),
      );
    } catch (_) {
      return null;
    }
  }

  static Future<bool> isRequired(ForceUpdatePolicy policy) async {
    if (!policy.hasRule) {
      return false;
    }
    try {
      final info = await PackageInfo.fromPlatform()
          .timeout(const Duration(milliseconds: 800));
      return compareVersions(info.version, policy.minVersion) < 0;
    } catch (_) {
      // Never brick launch if package_info isn't ready yet (common on web).
      return false;
    }
  }

  /// Returns true when the user is blocked and must update from the store.
  /// Prefer [bootstrap] so splash never waits on a second network round-trip.
  static Future<bool> enforce(
    BuildContext context, {
    Map<String, dynamic>? bootstrap,
    ApiClient? api,
    bool fetchLive = false,
    Duration liveTimeout = const Duration(seconds: 3),
  }) async {
    // Chrome / Flutter web has no store install path — never block launch.
    if (kIsWeb) {
      return false;
    }
    if (!context.mounted) {
      return false;
    }

    ForceUpdatePolicy? policy =
        bootstrap != null ? ForceUpdatePolicy.fromBootstrap(bootstrap) : null;

    if (fetchLive || policy == null) {
      try {
        final live = await loadPolicy(api: api).timeout(liveTimeout);
        if (live != null) {
          policy = live;
        }
      } catch (_) {}
    }

    if (policy == null) {
      return false;
    }

    try {
      if (!await isRequired(policy)) {
        return false;
      }
    } catch (_) {
      return false;
    }

    if (!context.mounted) {
      return true;
    }
    await showBlockingGate(context, policy);
    return true;
  }

  static Future<void> showBlockingGate(
    BuildContext context,
    ForceUpdatePolicy policy,
  ) async {
    if (_gateOpen) {
      return;
    }
    _gateOpen = true;
    try {
      await Navigator.of(context, rootNavigator: true).push<void>(
        PageRouteBuilder<void>(
          opaque: true,
          barrierDismissible: false,
          pageBuilder: (context, animation, secondaryAnimation) {
            return PopScope(
              canPop: false,
              child: _ForceUpdatePage(policy: policy),
            );
          },
        ),
      );
    } finally {
      _gateOpen = false;
    }
  }

  static Future<void> openStore(String url) async {
    final fallback = defaultTargetPlatform == TargetPlatform.iOS
        ? 'https://apps.apple.com'
        : ForceUpdatePolicy.playStoreUrl;
    final target = url.trim().isNotEmpty ? url.trim() : fallback;
    final uri = Uri.tryParse(target);
    if (uri == null) {
      return;
    }
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

class _ForceUpdatePage extends StatelessWidget {
  const _ForceUpdatePage({required this.policy});

  final ForceUpdatePolicy policy;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0D0D0F),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(28, 40, 28, 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Spacer(),
              const Icon(
                Icons.system_update_alt_rounded,
                color: LensColors.primary,
                size: 72,
              ),
              const SizedBox(height: 28),
              Text(
                policy.title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 26,
                  fontWeight: FontWeight.w800,
                  height: 1.2,
                ),
              ),
              const SizedBox(height: 14),
              Text(
                policy.message,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: LensColors.cream,
                  fontSize: 15,
                  height: 1.45,
                ),
              ),
              const Spacer(),
              FilledButton(
                style: FilledButton.styleFrom(
                  backgroundColor: LensColors.primary,
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                onPressed: () => ForceUpdate.openStore(policy.storeUrl),
                child: Text(
                  defaultTargetPlatform == TargetPlatform.iOS
                      ? 'Update from App Store'
                      : 'Update from Google Play',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Lens cannot continue until this update is installed.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
