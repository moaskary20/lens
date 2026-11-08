import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/bootstrap_cache.dart';
import 'package:lens/core/demo_bootstrap.dart';
import 'package:lens/core/force_update.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/features/onboarding/onboarding_page.dart';
import 'package:lens/features/shell/app_shell.dart';
import 'package:video_player/video_player.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key, this.api});

  final ApiClient? api;

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage> {
  static const _holdDuration = Duration(milliseconds: 4940);
  static const _webHoldDuration = Duration(milliseconds: 900);
  static const _secureWindow = MethodChannel('lens/secure_window');

  Timer? _hold;
  Map<String, dynamic> _payload = BootstrapCache.withFastTypes(DemoBootstrap.payload());
  VideoPlayerController? _videoController;
  var _opened = false;
  var _videoReady = false;
  Future<void>? _bootstrapFuture;

  @override
  void initState() {
    super.initState();
    _bootstrapFuture = _loadBootstrap();
    if (kIsWeb) {
      // Video splash frequently leaves a black frame on Chrome — skip it.
      _hold = Timer(_webHoldDuration, _openOnboarding);
    } else {
      _startVideo();
      _hold = Timer(_holdDuration, _openOnboarding);
    }
  }

  @override
  void dispose() {
    _hold?.cancel();
    _videoController?.removeListener(_onVideoTick);
    _videoController?.dispose();
    super.dispose();
  }

  Future<void> _startVideo() async {
    final controller = VideoPlayerController.asset('assit/splash.mp4');
    _videoController = controller;
    controller.addListener(_onVideoTick);
    try {
      await controller.initialize();
      if (!mounted || _opened) {
        return;
      }
      await controller.setLooping(false);
      await controller.setVolume(0);
      await controller.play();
      if (!mounted || _opened) {
        return;
      }
      setState(() => _videoReady = true);
      final duration = controller.value.duration;
      if (duration > Duration.zero) {
        _hold?.cancel();
        _hold = Timer(duration, _openOnboarding);
      }
    } catch (_) {
      await _disposeVideo();
      if (mounted && !_opened) {
        setState(() => _videoReady = false);
      }
    }
  }

  Future<void> _disposeVideo() async {
    final controller = _videoController;
    _videoController = null;
    _videoReady = false;
    if (controller == null) {
      return;
    }
    controller.removeListener(_onVideoTick);
    await controller.dispose();
  }

  void _onVideoTick() {
    final value = _videoController?.value;
    if (value == null) {
      return;
    }
    if (value.hasError) {
      _disposeVideo().then((_) {
        if (mounted && !_opened) {
          setState(() {});
        }
      });
      return;
    }
    if (!value.isInitialized || value.duration <= Duration.zero) {
      return;
    }
    if (value.position >= value.duration) {
      _openOnboarding();
    }
  }

  Future<void> _loadBootstrap() async {
    await SessionStore.instance.restore();
    // Full category grid from the first frame (demo + last cache), never a short list.
    final cachedTypes = await BootstrapCache.loadVendorTypes();
    _payload = BootstrapCache.withFastTypes(
      DemoBootstrap.payload(),
      cached: cachedTypes,
    );
    try {
      final remote = await (widget.api ?? ApiClient()).getJson(
        '/app/bootstrap',
      );
      final types = remote['vendor_types'];
      final popular = remote['popular'];
      final empty =
          (types is! List || types.isEmpty) &&
          (popular is! List || popular.isEmpty);
      if (!mounted) {
        return;
      }
      if (empty) {
        final payload = DemoBootstrap.payload();
        payload['name'] = remote['name']?.toString() ?? payload['name'];
        payload['logo'] = remote['logo'];
        _payload = BootstrapCache.withFastTypes(payload, cached: cachedTypes);
      } else {
        final merged = Map<String, dynamic>.from(remote);
        merged['vendor_types'] = BootstrapCache.bestTypes(
          remote: types,
          cached: cachedTypes,
        );
        _payload = BootstrapCache.withFastTypes(merged, cached: cachedTypes);
        if (types is List && types.isNotEmpty) {
          unawaited(BootstrapCache.saveVendorTypes(types));
        }
      }
    } catch (_) {
      _payload = BootstrapCache.withFastTypes(
        DemoBootstrap.payload(),
        cached: cachedTypes,
      );
    }
  }

  Future<void> _openOnboarding() async {
    if (_opened || !mounted) {
      return;
    }
    _opened = true;
    _hold?.cancel();

    try {
      await (_bootstrapFuture ?? _loadBootstrap())
          .timeout(const Duration(seconds: 8));
    } catch (_) {}

    await SessionStore.instance.restore();
    if (!kIsWeb) {
      try {
        await _secureWindow.invokeMethod<void>('enable');
      } catch (_) {}
    }
    if (!mounted) {
      return;
    }

    // Never block Chrome / web on store update checks.
    if (!kIsWeb) {
      try {
        final blocked = await ForceUpdate.enforce(
          context,
          bootstrap: _payload,
          fetchLive: true,
          liveTimeout: const Duration(seconds: 5),
        );
        if (blocked || !mounted) {
          return;
        }
      } catch (_) {}
    }

    if (!mounted) {
      return;
    }

    Widget next;
    try {
      next = SessionStore.instance.shouldOpenHome
          ? AppShell(bootstrap: _payload)
          : OnboardingPage(bootstrap: _payload);
    } catch (_) {
      next = AppShell(bootstrap: DemoBootstrap.payload());
    }

    Navigator.of(context).pushReplacement(
      MaterialPageRoute<void>(builder: (_) => next),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (kIsWeb) {
      return const Scaffold(
        backgroundColor: Color(0xFF0D0D0F),
        body: Center(
          child: SizedBox(
            width: 28,
            height: 28,
            child: CircularProgressIndicator(
              strokeWidth: 2.4,
              color: Color(0xFFFF5A1F),
            ),
          ),
        ),
      );
    }

    final controller = _videoController;
    return Scaffold(
      backgroundColor: Colors.black,
      body: SizedBox.expand(
        key: const Key('splash-black-background'),
        child: ColoredBox(
          color: Colors.black,
          child: _videoReady &&
                  controller != null &&
                  controller.value.isInitialized
              ? FittedBox(
                  fit: BoxFit.contain,
                  child: SizedBox(
                    width: controller.value.size.width,
                    height: controller.value.size.height,
                    child: VideoPlayer(
                      controller,
                      key: const Key('splash-video'),
                    ),
                  ),
                )
              : const SizedBox.expand(
                  key: Key('splash-video-loading'),
                ),
        ),
      ),
    );
  }
}
