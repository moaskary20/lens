import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/category_list_page.dart';
import 'package:lens/features/shell/app_shell.dart';

void openAllCategories(BuildContext context, HomeData home) {
  Navigator.of(context).push(
    PageRouteBuilder<void>(
      transitionDuration: const Duration(milliseconds: 620),
      reverseTransitionDuration: const Duration(milliseconds: 380),
      pageBuilder: (context, animation, secondaryAnimation) => CategoriesPage(home: home),
      transitionsBuilder: (context, animation, secondaryAnimation, child) {
        final curved = CurvedAnimation(parent: animation, curve: Curves.easeOutCubic, reverseCurve: Curves.easeInCubic);
        return FadeTransition(
          opacity: curved,
          child: SlideTransition(
            position: Tween<Offset>(begin: const Offset(0.04, 0.08), end: Offset.zero).animate(curved),
            child: ScaleTransition(
              scale: Tween<double>(begin: 0.94, end: 1).animate(curved),
              child: child,
            ),
          ),
        );
      },
    ),
  );
}

class CategoriesPage extends StatefulWidget {
  const CategoriesPage({super.key, required this.home});

  final HomeData home;

  @override
  State<CategoriesPage> createState() => _CategoriesPageState();
}

class _CategoriesPageState extends State<CategoriesPage> with SingleTickerProviderStateMixin {
  late final AnimationController _motion;

  @override
  void initState() {
    super.initState();
    _motion = AnimationController(vsync: this, duration: const Duration(milliseconds: 1100))..forward();
  }

  @override
  void dispose() {
    _motion.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final types = widget.home.vendorTypes;
    final featured = types.isEmpty ? null : types.first;
    final rest = types.length > 1 ? types.sublist(1) : const <VendorTypeItem>[];

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: AppShell.navFab(context),
      bottomNavigationBar: AppShell.navBar(context, index: 0),
      body: Stack(
        children: [
          const _AmbientGlow(),
          SafeArea(
            child: Column(
              children: [
                _topBar(),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 4, 20, 96),
                    children: [
                      _Reveal(animation: _motion, start: 0, child: _hero(types.length)),
                      if (featured != null) ...[
                        const SizedBox(height: 22),
                        _Reveal(
                          animation: _motion,
                          start: 0.12,
                          from: const Offset(0, 0.16),
                          child: _FeaturedCard(type: featured, home: widget.home, onTap: () => _open(featured)),
                        ),
                      ],
                      if (rest.isNotEmpty) ...[
                        const SizedBox(height: 26),
                        _Reveal(
                          animation: _motion,
                          start: 0.22,
                          child: const Text('Browse all', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                        ),
                        const SizedBox(height: 4),
                        _Reveal(
                          animation: _motion,
                          start: 0.24,
                          child: const Text('Tap a department to see every creator in it.', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
                        ),
                        const SizedBox(height: 14),
                        GridView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: rest.length,
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 2,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                            mainAxisExtent: 188,
                          ),
                          itemBuilder: (context, index) {
                            final type = rest[index];
                            return _Reveal(
                              animation: _motion,
                              start: 0.28 + (index * 0.07),
                              from: Offset(index.isEven ? -0.08 : 0.08, 0.12),
                              child: _GridCard(
                                type: type,
                                home: widget.home,
                                index: index + 2,
                                onTap: () => _open(type),
                              ),
                            );
                          },
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _topBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 4, 16, 0),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
          ),
          const Expanded(
            child: Text('All Categories', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
          ),
        ],
      ),
    );
  }

  Widget _hero(int count) {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF3A1A10), Color(0xFF161210), Color(0xFF0F0E10)],
        ),
        border: Border.all(color: const Color(0x66FF5A1F)),
        boxShadow: const [
          BoxShadow(color: Color(0x33000000), blurRadius: 24, offset: Offset(0, 12)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('LENS', style: TextStyle(color: LensColors.primary, fontSize: 12, letterSpacing: 3, fontWeight: FontWeight.w800)),
          const SizedBox(height: 10),
          const Text('Explore every department', style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.1)),
          const SizedBox(height: 8),
          const Text(
            'Photographers, studios, models, and more — one place to start a booking.',
            style: TextStyle(color: Color(0xFFB0ABA3), fontSize: 13.5, height: 1.4),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _pill('$count live'),
              const SizedBox(width: 8),
              _pill('Egypt'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _pill(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0x3324140E),
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: const Color(0x55FF5A1F)),
      ),
      child: Text(label, style: const TextStyle(color: LensColors.primary, fontSize: 11.5, fontWeight: FontWeight.w800)),
    );
  }

  void _open(VendorTypeItem type) {
    final vendors = widget.home.popular.where((section) => section.slug == type.slug).expand((section) => section.vendors).toList();
    Navigator.of(context).push(
      PageRouteBuilder<void>(
        transitionDuration: const Duration(milliseconds: 420),
        pageBuilder: (context, animation, secondaryAnimation) => CategoryListPage(type: type, home: widget.home, vendors: vendors),
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          final curved = CurvedAnimation(parent: animation, curve: Curves.easeOutCubic);
          return FadeTransition(
            opacity: curved,
            child: SlideTransition(
              position: Tween<Offset>(begin: const Offset(0.08, 0), end: Offset.zero).animate(curved),
              child: child,
            ),
          );
        },
      ),
    );
  }
}

class _AmbientGlow extends StatelessWidget {
  const _AmbientGlow();

  @override
  Widget build(BuildContext context) {
    return const IgnorePointer(
      child: Stack(
        children: [
          Positioned(top: -80, right: -60, child: _Blob(size: 220, color: Color(0x33FF5A1F))),
          Positioned(top: 220, left: -90, child: _Blob(size: 180, color: Color(0x1AFF5A1F))),
          Positioned(bottom: 40, right: -50, child: _Blob(size: 160, color: Color(0x14FF5A1F))),
        ],
      ),
    );
  }
}

class _Blob extends StatelessWidget {
  const _Blob({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(shape: BoxShape.circle, color: color),
    );
  }
}

class _Reveal extends StatelessWidget {
  const _Reveal({
    required this.animation,
    required this.start,
    required this.child,
    this.from = const Offset(0, 0.1),
  });

  final Animation<double> animation;
  final double start;
  final Offset from;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final curved = CurvedAnimation(
      parent: animation,
      curve: Interval(start.clamp(0, 0.75), (start + 0.34).clamp(0.2, 1), curve: Curves.easeOutCubic),
    );

    return FadeTransition(
      opacity: curved,
      child: SlideTransition(
        position: Tween<Offset>(begin: from, end: Offset.zero).animate(curved),
        child: ScaleTransition(
          scale: Tween<double>(begin: 0.94, end: 1).animate(curved),
          child: child,
        ),
      ),
    );
  }
}

class _Pressable extends StatefulWidget {
  const _Pressable({required this.onTap, required this.child});

  final VoidCallback onTap;
  final Widget child;

  @override
  State<_Pressable> createState() => _PressableState();
}

class _PressableState extends State<_Pressable> {
  bool _down = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => setState(() => _down = true),
      onTapCancel: () => setState(() => _down = false),
      onTapUp: (_) => setState(() => _down = false),
      onTap: widget.onTap,
      child: AnimatedScale(
        scale: _down ? 0.97 : 1,
        duration: const Duration(milliseconds: 140),
        curve: Curves.easeOut,
        child: widget.child,
      ),
    );
  }
}

class _FeaturedCard extends StatelessWidget {
  const _FeaturedCard({required this.type, required this.home, required this.onTap});

  final VendorTypeItem type;
  final HomeData home;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final count = _count(home, type.slug);

    return _Pressable(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(26),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFFFF5A1F), Color(0xFFB73A0F), Color(0xFF2A160E)],
            stops: [0, 0.42, 1],
          ),
          boxShadow: const [
            BoxShadow(color: Color(0x55FF5A1F), blurRadius: 22, offset: Offset(0, 10)),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.16), borderRadius: BorderRadius.circular(99)),
                  child: const Text('FEATURED', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 1.1)),
                ),
                const Spacer(),
                Text(_index(1), style: TextStyle(color: Colors.white.withValues(alpha: 0.35), fontSize: 28, fontWeight: FontWeight.w800)),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Container(
                  width: 62,
                  height: 62,
                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.14), borderRadius: BorderRadius.circular(20)),
                  child: Icon(_iconFor(type.slug), color: Colors.white, size: 30),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(type.label, style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
                      const SizedBox(height: 4),
                      Text(_blurb(type.slug), style: TextStyle(color: Colors.white.withValues(alpha: 0.82), fontSize: 13, height: 1.3)),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Text(
                  count > 0 ? '$count creators ready to book' : 'Browse verified creators',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
                ),
                const Spacer(),
                Container(
                  width: 36,
                  height: 36,
                  decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                  child: const Icon(Icons.arrow_forward_rounded, color: LensColors.primary, size: 18),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _GridCard extends StatelessWidget {
  const _GridCard({required this.type, required this.home, required this.index, required this.onTap});

  final VendorTypeItem type;
  final HomeData home;
  final int index;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final count = _count(home, type.slug);

    return _Pressable(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(22),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF1C1614), Color(0xFF121214)],
          ),
          border: Border.all(color: const Color(0xFF2A2A2E)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(color: const Color(0xFF24140E), borderRadius: BorderRadius.circular(14)),
                  child: Icon(_iconFor(type.slug), color: LensColors.primary, size: 22),
                ),
                const Spacer(),
                Text(_index(index), style: const TextStyle(color: Color(0x33FFFFFF), fontSize: 18, fontWeight: FontWeight.w800)),
              ],
            ),
            const Spacer(),
            Text(type.label, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800, height: 1.15)),
            const SizedBox(height: 6),
            Text(_blurb(type.slug), maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 11.5, height: 1.25)),
            const SizedBox(height: 10),
            Text(
              count > 0 ? '$count creators' : 'Browse',
              style: const TextStyle(color: LensColors.primary, fontSize: 12, fontWeight: FontWeight.w800),
            ),
          ],
        ),
      ),
    );
  }
}

int _count(HomeData home, String slug) {
  return home.popular.where((section) => section.slug == slug).expand((section) => section.vendors).length;
}

String _index(int value) => value.toString().padLeft(2, '0');

IconData _iconFor(String slug) {
  return switch (slug) {
    'photographer' => Icons.photo_camera_outlined,
    'videographer' => Icons.videocam_outlined,
    'reels' => Icons.phone_iphone,
    'model' => Icons.person_outline,
    'studio' => Icons.home_work_outlined,
    'ugc' => Icons.groups_outlined,
    'food_stylist' => Icons.restaurant,
    _ => Icons.category_outlined,
  };
}

String _blurb(String slug) {
  return switch (slug) {
    'photographer' => 'Portraits, events, and product stills',
    'videographer' => 'Films, ads, and motion stories',
    'reels' => 'Short-form social content',
    'model' => 'Talent for campaigns and lookbooks',
    'studio' => 'Spaces, cycloramas, and gear',
    'ugc' => 'Creator-led brand content',
    'food_stylist' => 'Food, table, and recipe styling',
    _ => 'Creators in this department',
  };
}
