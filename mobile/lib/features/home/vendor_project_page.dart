import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';

class VendorProjectPage extends StatelessWidget {
  const VendorProjectPage({
    super.key,
    required this.title,
    required this.files,
    this.description = '',
    this.completedOn = '',
    this.externalUrl = '',
  });

  final String title;
  final String description;
  final String completedOn;
  final String externalUrl;
  final List<VendorProjectFile> files;

  @override
  Widget build(BuildContext context) {
    final photos = files.where((item) => item.kind == 'image' && item.url.isNotEmpty).toList();
    final videos = files.where((item) => item.kind == 'video' && item.url.isNotEmpty).toList();
    final links = files.where((item) => item.kind == 'link' || (item.externalUrl ?? '').isNotEmpty).toList();

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      appBar: AppBar(
        backgroundColor: LensColors.charcoal,
        foregroundColor: Colors.white,
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w800)),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
        children: [
          if (completedOn.isNotEmpty)
            Text(completedOn, style: const TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w600)),
          if (description.isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(description, style: const TextStyle(color: Color(0xFFE8E4DC), height: 1.45, fontSize: 14.5)),
          ],
          if (photos.isNotEmpty) ...[
            const SizedBox(height: 18),
            const Text('Photos', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: 10),
            for (var index = 0; index < photos.length; index++) ...[
              Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(14),
                  onTap: () => openProjectPhotoGallery(
                    context,
                    urls: photos.map((item) => item.url).toList(),
                    initialIndex: index,
                    title: title,
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(14),
                    child: AspectRatio(
                      aspectRatio: 4 / 5,
                      child: Stack(
                        fit: StackFit.expand,
                        children: [
                          _networkImage(photos[index].url),
                          const Positioned(
                            right: 10,
                            bottom: 10,
                            child: DecoratedBox(
                              decoration: BoxDecoration(
                                color: Color(0x99000000),
                                shape: BoxShape.circle,
                              ),
                              child: Padding(
                                padding: EdgeInsets.all(6),
                                child: Icon(Icons.fullscreen_rounded, color: Colors.white, size: 18),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 10),
            ],
          ],
          if (videos.isNotEmpty) ...[
            const SizedBox(height: 10),
            const Text('Videos', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: 10),
            for (final video in videos) ...[
              _ProjectVideoPlayer(url: video.url),
              const SizedBox(height: 12),
            ],
          ],
          if (links.isNotEmpty || externalUrl.isNotEmpty) ...[
            const SizedBox(height: 10),
            const Text('Links', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: 8),
            if (externalUrl.isNotEmpty) _linkTile(externalUrl),
            for (final link in links)
              if ((link.externalUrl ?? '').isNotEmpty && link.externalUrl != externalUrl)
                _linkTile(link.externalUrl!),
          ],
          if (photos.isEmpty && videos.isEmpty && links.isEmpty && externalUrl.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: 40),
              child: Text('No media in this project yet.', textAlign: TextAlign.center, style: TextStyle(color: Color(0xFF8E8B84))),
            ),
        ],
      ),
    );
  }

  Widget _linkTile(String url) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: const BorderSide(color: Color(0xFF2A2D34))),
        tileColor: const Color(0xFF141518),
        leading: const Icon(Icons.link_rounded, color: LensColors.primary),
        title: Text(url, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 13)),
        onTap: () async {
          final uri = Uri.tryParse(url);
          if (uri != null) {
            await launchUrl(uri, mode: LaunchMode.externalApplication);
          }
        },
      ),
    );
  }

  Widget _networkImage(String url) {
    if (!LensConfig.useNetwork || url.isEmpty) {
      return const ColoredBox(color: Color(0xFF1C1D22));
    }
    return Image.network(
      url,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) => const ColoredBox(color: Color(0xFF1C1D22)),
    );
  }
}

void openProjectPhotoGallery(
  BuildContext context, {
  required List<String> urls,
  int initialIndex = 0,
  String title = '',
}) {
  final clean = urls.where((url) => url.trim().isNotEmpty).toList();
  if (clean.isEmpty) {
    return;
  }
  final start = initialIndex.clamp(0, clean.length - 1);
  Navigator.of(context).push(
    PageRouteBuilder<void>(
      opaque: false,
      barrierColor: Colors.black87,
      pageBuilder: (context, animation, secondaryAnimation) => _ProjectPhotoGalleryPage(
        urls: clean,
        initialIndex: start,
        title: title,
      ),
      transitionsBuilder: (context, animation, secondaryAnimation, child) {
        return FadeTransition(opacity: animation, child: child);
      },
    ),
  );
}

class VendorProjectFile {
  const VendorProjectFile({
    required this.id,
    required this.url,
    required this.kind,
    this.externalUrl,
  });

  factory VendorProjectFile.fromJson(Map<String, dynamic> json) {
    final type = json['type']?.toString() ?? '';
    final kind = json['kind']?.toString().isNotEmpty == true
        ? json['kind'].toString()
        : (type == 'video'
            ? 'video'
            : (type == 'link' ? 'link' : 'image'));
    return VendorProjectFile(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      url: json['url']?.toString() ?? '',
      kind: kind,
      externalUrl: json['external_url']?.toString(),
    );
  }

  final int id;
  final String url;
  final String kind;
  final String? externalUrl;
}

class _ProjectPhotoGalleryPage extends StatefulWidget {
  const _ProjectPhotoGalleryPage({
    required this.urls,
    required this.initialIndex,
    required this.title,
  });

  final List<String> urls;
  final int initialIndex;
  final String title;

  @override
  State<_ProjectPhotoGalleryPage> createState() => _ProjectPhotoGalleryPageState();
}

class _ProjectPhotoGalleryPageState extends State<_ProjectPhotoGalleryPage> {
  late final PageController _pages;
  late int _index;

  @override
  void initState() {
    super.initState();
    _index = widget.initialIndex;
    _pages = PageController(initialPage: widget.initialIndex);
  }

  @override
  void dispose() {
    _pages.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: SafeArea(
        child: Stack(
          children: [
            PageView.builder(
              controller: _pages,
              itemCount: widget.urls.length,
              onPageChanged: (value) => setState(() => _index = value),
              itemBuilder: (context, index) {
                final url = widget.urls[index];
                return InteractiveViewer(
                  minScale: 1,
                  maxScale: 4,
                  child: Center(
                    child: LensConfig.useNetwork
                        ? Image.network(
                            url,
                            fit: BoxFit.contain,
                            errorBuilder: (context, error, stackTrace) => const Icon(Icons.broken_image_outlined, color: Color(0xFF8E8B84), size: 48),
                          )
                        : const Icon(Icons.image_outlined, color: Color(0xFF8E8B84), size: 48),
                  ),
                );
              },
            ),
            Positioned(
              top: 4,
              left: 4,
              right: 4,
              child: Row(
                children: [
                  IconButton(
                    tooltip: 'Close',
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close_rounded, color: Colors.white, size: 28),
                  ),
                  Expanded(
                    child: Text(
                      widget.title.isEmpty ? 'Photo' : widget.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(right: 12),
                    child: Text(
                      '${_index + 1}/${widget.urls.length}',
                      style: const TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600),
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
}

class _ProjectVideoPlayer extends StatefulWidget {
  const _ProjectVideoPlayer({required this.url});

  final String url;

  @override
  State<_ProjectVideoPlayer> createState() => _ProjectVideoPlayerState();
}

class _ProjectVideoPlayerState extends State<_ProjectVideoPlayer> {
  VideoPlayerController? _controller;
  bool _ready = false;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    try {
      final controller = VideoPlayerController.networkUrl(Uri.parse(widget.url));
      _controller = controller;
      await controller.initialize();
      if (!mounted) {
        return;
      }
      setState(() => _ready = true);
    } catch (_) {
      if (mounted) {
        setState(() => _failed = true);
      }
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_failed) {
      return Container(
        height: 180,
        alignment: Alignment.center,
        decoration: BoxDecoration(color: const Color(0xFF141518), borderRadius: BorderRadius.circular(14)),
        child: const Text('Could not load video', style: TextStyle(color: Color(0xFF8E8B84))),
      );
    }
    if (!_ready || _controller == null) {
      return Container(
        height: 180,
        alignment: Alignment.center,
        decoration: BoxDecoration(color: const Color(0xFF141518), borderRadius: BorderRadius.circular(14)),
        child: const CircularProgressIndicator(color: LensColors.primary),
      );
    }
    final controller = _controller!;
    return ClipRRect(
      borderRadius: BorderRadius.circular(14),
      child: AspectRatio(
        aspectRatio: controller.value.aspectRatio == 0 ? 16 / 9 : controller.value.aspectRatio,
        child: Stack(
          alignment: Alignment.center,
          children: [
            VideoPlayer(controller),
            Material(
              color: Colors.black38,
              shape: const CircleBorder(),
              child: IconButton(
                onPressed: () {
                  setState(() {
                    if (controller.value.isPlaying) {
                      controller.pause();
                    } else {
                      controller.play();
                    }
                  });
                },
                icon: Icon(
                  controller.value.isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded,
                  color: Colors.white,
                  size: 36,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
