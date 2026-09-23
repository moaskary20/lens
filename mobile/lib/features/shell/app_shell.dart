import 'dart:math' as math;
import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/bookings/bookings_page.dart';
import 'package:lens/features/home/home_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/search_page.dart';
import 'package:lens/features/profile/profile_page.dart';
import 'package:lens/features/shell/lens_nav_bar.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class AppShell extends StatefulWidget {
  const AppShell({super.key, required this.bootstrap});

  final Map<String, dynamic> bootstrap;

  static void Function({bool guestPreview})? openBookingsTab;

  static void showBookings({bool guestPreview = false}) {
    openBookingsTab?.call(guestPreview: guestPreview);
  }

  @override
  State<AppShell> createState() => AppShellState();
}

class AppShellState extends State<AppShell> with SingleTickerProviderStateMixin {
  int _index = 0;
  bool _focusSearch = false;
  bool _guestBookings = false;
  late final AnimationController _menu;
  late final Animation<double> _menuAnim;

  @override
  void initState() {
    super.initState();
    _menu = AnimationController(vsync: this, duration: const Duration(milliseconds: 420));
    _menuAnim = CurvedAnimation(parent: _menu, curve: Curves.easeOutCubic, reverseCurve: Curves.easeInCubic);
    final home = HomeData.fromJson(widget.bootstrap);
    FavoritesStore.instance.hydrate(home);
    NotificationsStore.instance.hydrate(unread: home.unreadNotifications);
    AppShell.openBookingsTab = ({bool guestPreview = false}) => openBookings(guestPreview: guestPreview);
  }

  @override
  void dispose() {
    if (AppShell.openBookingsTab != null) {
      AppShell.openBookingsTab = null;
    }
    _menu.dispose();
    super.dispose();
  }

  void _openMenu() => _menu.forward();

  void _closeMenu() => _menu.reverse();

  @override
  Widget build(BuildContext context) {
    final home = HomeData.fromJson(widget.bootstrap);
    final bookingsOn = home.on('bookings');
    final searchIndex = bookingsOn ? 2 : 1;
    final pages = [
      HomePage(data: home, onOpenMenu: _openMenu, onOpenSearch: () => _openSearch(focus: true)),
      if (bookingsOn)
        BookingsPage(
          home: home,
          asRoute: _guestBookings,
          onBack: () => setState(() {
            _index = 0;
            _guestBookings = false;
          }),
        ),
      SearchPage(data: home, autofocus: _focusSearch && _index == searchIndex),
      ProfilePage(
        data: home,
        bootstrap: widget.bootstrap,
        onOpenBookings: bookingsOn ? () => setState(() => _index = 1) : null,
      ),
    ];

    final visibleIndex = _index.clamp(0, pages.length - 1);

    return AnimatedBuilder(
      animation: _menuAnim,
      builder: (context, child) {
        final t = _menuAnim.value;
        final size = MediaQuery.sizeOf(context);
        final menuWidth = math.min(320.0, size.width * 0.78);

        return ColoredBox(
          color: const Color(0xFF07070A),
          child: Stack(
            children: [
              _LensMenu(
                width: menuWidth,
                progress: t,
                bookingsOn: bookingsOn,
                onClose: _closeMenu,
                onOpen: (title) {
                  _closeMenu();
                  if (title == 'Home') {
                    setState(() => _index = 0);
                    return;
                  }
                  if (title == 'Search') {
                    _openSearch();
                    return;
                  }
                  if (title == 'Bookings' && bookingsOn) {
                    setState(() => _index = 1);
                    return;
                  }
                  if (title == 'Profile') {
                    setState(() => _index = bookingsOn ? 3 : 2);
                    return;
                  }
                  if (title == 'Favorites') {
                    openFavorites(context, home);
                    return;
                  }
                  if (title == 'Notifications') {
                    openNotifications(context, home);
                    return;
                  }
                  _open(context, title);
                },
              ),
              Transform(
                alignment: Alignment.centerLeft,
                transform: _contentTransform(t, size.width),
                child: GestureDetector(
                  onTap: t > 0.05 ? _closeMenu : null,
                  child: Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(28 * t),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.55 * t),
                          blurRadius: 48,
                          spreadRadius: -4,
                          offset: Offset(-18 * t, 12 * t),
                        ),
                        BoxShadow(
                          color: LensColors.primary.withValues(alpha: 0.18 * t),
                          blurRadius: 24,
                          offset: Offset(-8 * t, 0),
                        ),
                      ],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(28 * t),
                      child: IgnorePointer(
                        ignoring: t > 0.05,
                        child: child,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
      child: Scaffold(
        backgroundColor: LensColors.charcoal,
        body: pages[visibleIndex],
        floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
        floatingActionButton: LensNavBar.fab(
          onPressed: () => _open(context, bookingsOn ? 'New booking' : 'Create'),
        ),
        bottomNavigationBar: LensNavBar.bar(
          bookingsOn: bookingsOn,
          index: visibleIndex,
          onSelect: (index) => setState(() {
            _index = index;
            if (index != 1) {
              _guestBookings = false;
            }
            if (index != searchIndex) {
              _focusSearch = false;
            }
          }),
        ),
      ),
    );
  }

  Matrix4 _contentTransform(double t, double width) {
    return Matrix4.identity()
      ..setEntry(3, 2, 0.00135)
      ..translate(-width * 0.18 * t, 24.0 * t)
      ..rotateY(-0.72 * t)
      ..scale(1 - 0.14 * t, 1 - 0.08 * t);
  }

  void openBookings({bool guestPreview = false}) {
    final bookingsOn = HomeData.fromJson(widget.bootstrap).on('bookings');
    if (!bookingsOn) {
      return;
    }
    setState(() {
      _index = 1;
      _guestBookings = guestPreview;
      _focusSearch = false;
    });
  }

  void _openSearch({bool focus = false}) {
    final bookingsOn = HomeData.fromJson(widget.bootstrap).on('bookings');
    setState(() {
      _index = bookingsOn ? 2 : 1;
      _focusSearch = focus;
    });
  }

  void _open(BuildContext context, String title) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: title)));
  }
}

class _LensMenu extends StatelessWidget {
  const _LensMenu({
    required this.width,
    required this.progress,
    required this.bookingsOn,
    required this.onClose,
    required this.onOpen,
  });

  final double width;
  final double progress;
  final bool bookingsOn;
  final VoidCallback onClose;
  final ValueChanged<String> onOpen;

  @override
  Widget build(BuildContext context) {
    final t = progress;
    if (t == 0) {
      return const SizedBox.shrink();
    }

    return Align(
      alignment: Alignment.centerRight,
      child: Transform(
        alignment: Alignment.centerRight,
        transform: Matrix4.identity()
          ..setEntry(3, 2, 0.0016)
          ..translate(width * 0.35 * (1 - t))
          ..rotateY(1.05 * (1 - t)),
        child: Opacity(
          opacity: t.clamp(0, 1),
          child: Padding(
            padding: EdgeInsets.fromLTRB(0, 28 + (1 - t) * 18, 12, 28 + (1 - t) * 18),
            child: SizedBox(
              width: width,
              child: ClipRRect(
                borderRadius: BorderRadius.circular(28),
                child: BackdropFilter(
                  filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(28),
                      gradient: const LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [Color(0xF01C120E), Color(0xF0141418), Color(0xF00D0D10)],
                      ),
                      border: Border.all(color: const Color(0x44FF5A1F)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.45),
                          blurRadius: 28,
                          offset: const Offset(-12, 10),
                        ),
                        BoxShadow(
                          color: LensColors.primary.withValues(alpha: 0.22),
                          blurRadius: 18,
                          offset: const Offset(-4, 0),
                        ),
                      ],
                    ),
                    child: SafeArea(
                      left: false,
                      child: ListView(
                        padding: const EdgeInsets.fromLTRB(18, 12, 18, 24),
                        children: [
                          Row(
                            children: [
                              const Expanded(
                                child: Text(
                                  'Lens',
                                  style: TextStyle(color: LensColors.primary, fontSize: 32, fontWeight: FontWeight.w800, height: 1),
                                ),
                              ),
                              IconButton(
                                onPressed: onClose,
                                icon: const Icon(Icons.close_rounded, color: LensColors.cream),
                              ),
                            ],
                          ),
                          const SizedBox(height: 18),
                          Container(height: 1, color: const Color(0xFF2A2D34)),
                          const SizedBox(height: 10),
                          _item(Icons.home_outlined, 'Home'),
                          _item(Icons.favorite_border_rounded, 'Favorites'),
                          if (bookingsOn) _item(Icons.calendar_month_outlined, 'Bookings'),
                          _item(Icons.search, 'Search'),
                          _item(Icons.person_outline, 'Profile'),
                          _item(Icons.notifications_none_rounded, 'Notifications'),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _item(IconData icon, String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Material(
        color: const Color(0x221C1D22),
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: () => onOpen(title),
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
            child: Row(
              children: [
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: const Color(0xFF24140E),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0x55FF5A1F)),
                  ),
                  child: Icon(icon, color: LensColors.primary, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(title, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w700, fontSize: 15)),
                ),
                const Icon(Icons.chevron_right_rounded, color: LensColors.slate),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
