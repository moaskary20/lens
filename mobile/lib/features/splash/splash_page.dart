import 'dart:async';

import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/demo_bootstrap.dart';
import 'package:lens/features/onboarding/onboarding_page.dart';
import 'package:video_player/video_player.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key, this.api});

  final ApiClient? api;

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage> {
  Timer? _hold;
  late final VideoPlayerController _videoController;
  late final Future<void> _videoReady;
  late DateTime _videoStartedAt;

  @override
  void initState() {
    super.initState();
    _videoController = VideoPlayerController.asset('assit/splash.mp4');
    _videoReady = _startVideo();
    _boot();
  }

  Future<void> _startVideo() async {
    await _videoController.initialize();
    await _videoController.play();
    _videoStartedAt = DateTime.now();
    if (mounted) {
      setState(() {});
    }
  }

  @override
  void dispose() {
    _hold?.cancel();
    _videoController.dispose();
    super.dispose();
  }

  Future<void> _boot() async {
    final started = DateTime.now();
    Map<String, dynamic> payload = DemoBootstrap.payload();
    try {
      final remote = await (widget.api ?? ApiClient()).getJson(
        '/app/bootstrap',
      );
      final types = remote['vendor_types'];
      final popular = remote['popular'];
      final empty =
          (types is! List || types.isEmpty) &&
          (popular is! List || popular.isEmpty);
      if (empty) {
        payload = DemoBootstrap.payload();
        payload['name'] = remote['name']?.toString() ?? payload['name'];
        payload['logo'] = remote['logo'];
      } else {
        payload = remote;
      }
    } catch (_) {
      payload = DemoBootstrap.payload();
    }
    await _videoReady;
    final videoDuration = _videoController.value.duration;
    final elapsed = DateTime.now().difference(started);
    final videoElapsed = DateTime.now().difference(_videoStartedAt);
    final videoWait = videoDuration - videoElapsed;
    final minimumWait = const Duration(milliseconds: 1400) - elapsed;
    final wait = videoWait > minimumWait ? videoWait : minimumWait;
    if (wait > Duration.zero && mounted) {
      final done = Completer<void>();
      _hold = Timer(wait, done.complete);
      await done.future;
    }
    if (!mounted) {
      return;
    }
    Navigator.of(context).pushReplacement(
      MaterialPageRoute<void>(
        builder: (_) => OnboardingPage(bootstrap: payload),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: SizedBox.expand(
        key: const Key('splash-black-background'),
        child: ColoredBox(
          color: Colors.black,
          child: _videoController.value.isInitialized
              ? Center(
                  child: AspectRatio(
                    aspectRatio: _videoController.value.aspectRatio,
                    child: VideoPlayer(
                      _videoController,
                      key: const Key('splash-video'),
                    ),
                  ),
                )
              : const SizedBox.shrink(),
        ),
      ),
    );
  }
}
