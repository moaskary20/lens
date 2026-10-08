import 'dart:math' as math;
import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/widgets/brand_logo.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/bookings/bookings_page.dart';
import 'package:lens/features/home/home_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/search_page.dart';
import 'package:lens/features/profile/app_settings_page.dart';
import 'package:lens/features/profile/messages_page.dart';
import 'package:lens/features/profile/profile_page.dart';
import 'package:lens/features/profile/report_issue_page.dart';
import 'package:lens/features/shell/lens_nav_bar.dart';

class AppShell extends StatefulWidget {
  const AppShell({super.key, required this.bootstrap});

  final Map<String, dynamic> bootstrap;

  static void Function({bool guestPreview})? openBookingsTab;
  static void Function(int index)? selectTab;
  static HomeData? currentHome;
  static Map<String, dynamic>? currentBootstrap;

  static bool get bookingsOn => currentHome?.on('bookings') ?? true;
  static int get searchIndex => bookingsOn ? 2 : 1;
  static int get profileIndex => bookingsOn ? 3 : 2;

  static void showBookings({bool guestPreview = false}) {
    openBookingsTab?.call(guestPreview: guestPreview);
  }

  static void openTab(BuildContext context, int index) {
    final navigator = Navigator.of(context);
    if (navigator.canPop()) {
      navigator.popUntil((route) => route.isFirst);
    }
    selectTab?.call(index);
  }

  static void openCreate(BuildContext context) {
    openTab(context, searchIndex);
  }

  static Widget navBar(
    BuildContext context, {
    required int index,
    bool withFab = true,
  }) {
    return LensNavBar.bar(
      bookingsOn: bookingsOn,
      index: index,
      withFab: withFab,
      onSelect: (value) => openTab(context, value),
    );
  }

  static Widget navFab(BuildContext context) {
    return LensNavBar.fab(
      onPressed: () {
        if (!SessionStore.instance.isGuest) {
          openCreate(context);
          return;
        }

        final bootstrap = currentBootstrap ?? <String, dynamic>{};
        final authenticatedBootstrap = Map<String, dynamic>.from(bootstrap);
        final navigator = Navigator.of(context);
        navigator.push(
          MaterialPageRoute<void>(
            builder: (_) => Scaffold(
              backgroundColor: LensColors.charcoal,
              body: LoginView(
                bootstrap: bootstrap,
                onSuccess: () => navigator.pushAndRemoveUntil(
                  MaterialPageRoute<void>(
                    builder: (_) => AppShell(bootstrap: authenticatedBootstrap),
                  ),
                  (route) => false,
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  @override
  State<AppShell> createState() => AppShellState();
}

class AppShellState extends State<AppShell>
    with SingleTickerProviderStateMixin {
  int _index = 0;
  bool _focusSearch = false;
  bool _guestBookings = false;
  late final AnimationController _menu;
  late final Animation<double> _menuAnim;

  @override
  void initState() {
    super.initState();
    AppShell.currentBootstrap = widget.bootstrap;
    _menu = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 420),
    );
    _menuAnim = CurvedAnimation(
      parent: _menu,
      curve: Curves.easeOutCubic,
      reverseCurve: Curves.easeInCubic,
    );
    final home = HomeData.fromJson(widget.bootstrap);
    FavoritesStore.instance.hydrate(home);
    NotificationsStore.instance.hydrate(unread: home.unreadNotifications);
    AppShell.openBookingsTab = ({bool guestPreview = false}) =>
        openBookings(guestPreview: guestPreview);
    AppShell.selectTab = (index) => setState(() {
      _index = index;
      if (index != 1) {
        _guestBookings = false;
      }
      if (index !=
          (HomeData.fromJson(widget.bootstrap).on('bookings') ? 2 : 1)) {
        _focusSearch = false;
      }
    });
  }

  @override
  void dispose() {
    if (AppShell.openBookingsTab != null) {
      AppShell.openBookingsTab = null;
    }
    if (AppShell.selectTab != null) {
      AppShell.selectTab = null;
    }
    if (AppShell.currentHome != null) {
      AppShell.currentHome = null;
    }
    if (identical(AppShell.currentBootstrap, widget.bootstrap)) {
      AppShell.currentBootstrap = null;
    }
    _menu.dispose();
    super.dispose();
  }

  void _openMenu() => _menu.forward();

  void _closeMenu() => _menu.reverse();

  @override
  Widget build(BuildContext context) {
    final home = HomeData.fromJson(widget.bootstrap);
    AppShell.currentHome = home;
    final bookingsOn = home.on('bookings');
    final searchIndex = bookingsOn ? 2 : 1;
    final pages = [
      HomePage(
        data: home,
        onOpenMenu: _openMenu,
        onOpenSearch: () => _openSearch(focus: true),
      ),
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

    final selected = visibleIndex == 0
        ? 'home'
        : (bookingsOn && visibleIndex == 1)
        ? 'bookings'
        : (visibleIndex == searchIndex)
        ? 'search'
        : 'profile';

    return AnimatedBuilder(
      animation: _menuAnim,
      builder: (context, child) {
        final t = _menuAnim.value;
        final size = MediaQuery.sizeOf(context);
        final menuWidth = math.min(340.0, size.width * 0.72);
        final open = t > 0.04;

        return ColoredBox(
          color: const Color(0xFF07080A),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Transform(
                alignment: Alignment.centerLeft,
                transform: _contentTransform(t),
                child: GestureDetector(
                  onTap: open ? _closeMenu : null,
                  child: Container(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(28 * t),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.5 * t),
                          blurRadius: 36,
                          offset: Offset(-10 * t, 14 * t),
                        ),
                        BoxShadow(
                          color: LensColors.primary.withValues(alpha: 0.22 * t),
                          blurRadius: 28,
                          offset: Offset(10 * t, 0),
                        ),
                      ],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(28 * t),
                      child: IgnorePointer(ignoring: open, child: child),
                    ),
                  ),
                ),
              ),
              if (open)
                Positioned(
                  top: 10,
                  bottom: 10,
                  right: 8,
                  width: menuWidth,
                  child: _LensMenu(
                    progress: t,
                    selected: selected,
                    home: home,
                    bookingsOn: bookingsOn,
                    onClose: _closeMenu,
                    onOpen: (id) =>
                        _openMenuItem(context, home, bookingsOn, id),
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
        floatingActionButton: AppShell.navFab(context),
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

  Matrix4 _contentTransform(double t) {
    return Matrix4.identity()
      ..setEntry(3, 2, 0.0011)
      ..translate(10.0 * t, 16.0 * t)
      ..rotateY(0.9 * t)
      ..scale(0.88 + 0.12 * (1 - t), 0.92 + 0.08 * (1 - t));
  }

  void _openMenuItem(
    BuildContext context,
    HomeData home,
    bool bookingsOn,
    String id,
  ) {
    _closeMenu();
    switch (id) {
      case 'home':
        setState(() => _index = 0);
      case 'search':
        _openSearch();
      case 'bookings':
        if (bookingsOn) {
          setState(() => _index = 1);
        }
      case 'profile':
        setState(() => _index = bookingsOn ? 3 : 2);
      case 'favorites':
        openFavorites(context, home);
      case 'notifications':
        openNotifications(context, home);
      case 'messages':
        Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => MessagesPage(home: home)),
        );
      case 'report':
        openReportIssue(context);
      case 'settings':
        Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => AppSettingsPage(home: home)),
        );
      case 'signin':
        setState(() => _index = bookingsOn ? 3 : 2);
      case 'signout':
        SessionStore.instance.signOut();
        setState(() => _index = 0);
    }
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
}

class _LensMenu extends StatelessWidget {
  const _LensMenu({
    required this.progress,
    required this.selected,
    required this.home,
    required this.bookingsOn,
    required this.onClose,
    required this.onOpen,
  });

  final double progress;
  final String selected;
  final HomeData home;
  final bool bookingsOn;
  final VoidCallback onClose;
  final ValueChanged<String> onOpen;

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: progress.clamp(0, 1),
      child: Transform.translate(
        offset: Offset((1 - progress) * 36, 0),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 18, sigmaY: 18),
            child: DecoratedBox(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(28),
                color: const Color(0xF0121216),
                border: Border.all(color: const Color(0x88FF5A1F), width: 1.2),
                boxShadow: [
                  BoxShadow(
                    color: LensColors.primary.withValues(alpha: 0.18),
                    blurRadius: 28,
                    offset: const Offset(-8, 0),
                  ),
                ],
              ),
              child: SafeArea(
                left: false,
                child: ListenableBuilder(
                  listenable: Listenable.merge([
                    SessionStore.instance,
                    FavoritesStore.instance,
                    NotificationsStore.instance,
                  ]),
                  builder: (context, _) {
                    final signedIn = SessionStore.instance.account != null;
                    return ListView(
                      key: const Key('lens-side-menu'),
                      padding: const EdgeInsets.fromLTRB(16, 4, 12, 16),
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: BrandLogo(
                                name: home.name,
                                logoUrl: home.logoUrl,
                                width: 180,
                                height: 40,
                                fontSize: 32,
                              ),
                            ),
                            IconButton(
                              tooltip: 'Close',
                              onPressed: onClose,
                              icon: const Icon(
                                Icons.close_rounded,
                                color: Colors.white,
                                size: 26,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        const Divider(color: Color(0xFF2A2D34), height: 1),
                        const SizedBox(height: 10),
                        _item(
                          id: 'home',
                          icon: Icons.home_outlined,
                          label: 'Home',
                        ),
                        if (home.on('favorites'))
                          _item(
                            id: 'favorites',
                            icon: Icons.favorite_border_rounded,
                            label: 'Favorites',
                            badge: FavoritesStore.instance.count,
                          ),
                        if (bookingsOn)
                          _item(
                            id: 'bookings',
                            icon: Icons.calendar_month_outlined,
                            label: 'Bookings',
                          ),
                        _item(
                          id: 'search',
                          icon: Icons.search,
                          label: 'Search',
                        ),
                        _item(
                          id: 'profile',
                          icon: Icons.person_outline,
                          label: 'Profile',
                        ),
                        if (home.on('notifications'))
                          _item(
                            id: 'notifications',
                            icon: Icons.notifications_none_rounded,
                            label: 'Notifications',
                            badge: NotificationsStore.instance.unread,
                          ),
                        const SizedBox(height: 8),
                        const Divider(color: Color(0xFF2A2D34), height: 1),
                        const SizedBox(height: 12),
                        if (home.on('chat'))
                          _item(
                            id: 'messages',
                            icon: Icons.chat_bubble_outline_rounded,
                            label: 'Messages',
                          ),
                        if (home.on('issue_reports'))
                          _item(
                            id: 'report',
                            icon: Icons.help_outline_rounded,
                            label: 'Report an Issue',
                          ),
                        _item(
                          id: 'settings',
                          icon: Icons.settings_outlined,
                          label: 'App Settings',
                        ),
                        _item(
                          id: signedIn ? 'signout' : 'signin',
                          icon: signedIn
                              ? Icons.logout_rounded
                              : Icons.login_rounded,
                          label: signedIn ? 'Log Out' : 'Sign In',
                        ),
                      ],
                    );
                  },
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _item({
    required String id,
    required IconData icon,
    required String label,
    int badge = 0,
  }) {
    final on = selected == id;
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Material(
        color: on ? const Color(0xFF2A2D34) : Colors.transparent,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          onTap: () => onOpen(id),
          borderRadius: BorderRadius.circular(18),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(8, 6, 10, 6),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: const Color(0xFF1A100C),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0x66FF5A1F)),
                  ),
                  child: Icon(icon, color: LensColors.primary, size: 20),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(
                    label,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 16,
                    ),
                  ),
                ),
                if (badge > 0)
                  Padding(
                    padding: const EdgeInsets.only(right: 6),
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 6,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: LensColors.primary,
                        borderRadius: BorderRadius.circular(99),
                      ),
                      child: Text(
                        badge > 99 ? '99+' : '$badge',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ),
                const Icon(
                  Icons.chevron_right_rounded,
                  color: Color(0xFF8E8B84),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
