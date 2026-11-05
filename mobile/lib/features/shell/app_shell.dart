import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/app.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/booked_vendors_store.dart';
import 'package:lens/core/bootstrap_cache.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/force_update.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/push_alerts.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/widgets/brand_logo.dart';
import 'package:lens/features/home/client_create_flow.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/bookings/bookings_page.dart';
import 'package:lens/features/home/home_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/search_page.dart';
import 'package:lens/features/profile/app_settings_page.dart';
import 'package:lens/features/profile/messages_page.dart';
import 'package:lens/features/profile/profile_page.dart';
import 'package:lens/features/profile/report_issue_page.dart';
import 'package:lens/features/profile/wallet_page.dart';
import 'package:lens/features/shell/lens_nav_bar.dart';

class AppShell extends StatefulWidget {
  const AppShell({
    super.key,
    required this.bootstrap,
    this.openClientCreateOnStart = false,
  });

  final Map<String, dynamic> bootstrap;
  final bool openClientCreateOnStart;

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

  static void create(BuildContext context) {
    if (SessionStore.instance.isClient) {
      final home = currentHome;
      if (home != null) {
        openClientCreateFlow(context, home);
        return;
      }
    }
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
                builder: (_) => AppShell(
                  bootstrap: authenticatedBootstrap,
                  openClientCreateOnStart: SessionStore.instance.isClient,
                ),
              ),
              (route) => false,
            ),
          ),
        ),
      ),
    );
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
      onCreate: () => create(context),
      onSelect: (value) => openTab(context, value),
    );
  }

  @override
  State<AppShell> createState() => AppShellState();
}

class AppShellState extends State<AppShell>
    with SingleTickerProviderStateMixin, WidgetsBindingObserver {
  int _index = 0;
  final List<int> _tabHistory = [0];
  bool _focusSearch = false;
  bool _guestBookings = false;
  late Map<String, dynamic> _bootstrap;
  late HomeData _home;
  late final AnimationController _menu;
  late final Animation<double> _menuAnim;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _bootstrap = BootstrapCache.withFastTypes(Map<String, dynamic>.from(widget.bootstrap));
    _home = HomeData.fromJson(_bootstrap);
    AppShell.currentBootstrap = _bootstrap;
    AppShell.currentHome = _home;
    // Upgrade to cached admin types ASAP when demo was shorter.
    unawaited(_hydrateCachedTypes());
    _menu = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 420),
    );
    _menuAnim = CurvedAnimation(
      parent: _menu,
      curve: Curves.easeOutCubic,
      reverseCurve: Curves.easeInCubic,
    );
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) {
        return;
      }
      FavoritesStore.instance.hydrate(_home);
      NotificationsStore.instance.hydrate(unread: _home.unreadNotifications);
      BookedVendorsStore.instance.hydrate();
      PushAlerts.instance.start(onOpenInbox: _openInboxFromPush);
      _refreshBootstrap();
    });
    AppShell.openBookingsTab = ({bool guestPreview = false}) =>
        openBookings(guestPreview: guestPreview);
    AppShell.selectTab = _selectTab;
    if (widget.openClientCreateOnStart) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          openClientCreateFlow(context, _home);
        }
      });
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    if (identical(AppShell.currentBootstrap, _bootstrap)) {
      PushAlerts.instance.stop();
      AppShell.openBookingsTab = null;
      AppShell.selectTab = null;
      AppShell.currentHome = null;
      AppShell.currentBootstrap = null;
    }
    _menu.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      unawaited(_refreshBootstrap());
      unawaited(
        ForceUpdate.enforce(
          context,
          bootstrap: _bootstrap,
          fetchLive: true,
        ),
      );
    }
  }

  void _openInboxFromPush() {
    final home = AppShell.currentHome ?? _home;
    if (!home.on('notifications')) {
      return;
    }
    final nav = LensApp.navigatorKey.currentState;
    if (nav == null) {
      return;
    }
    openNotifications(nav.context, home);
  }

  void _applyBootstrap(Map<String, dynamic> next) {
    _bootstrap = BootstrapCache.withFastTypes(next);
    _home = HomeData.fromJson(_bootstrap);
    AppShell.currentBootstrap = _bootstrap;
    AppShell.currentHome = _home;
  }

  Future<void> _hydrateCachedTypes() async {
    final cached = await BootstrapCache.loadVendorTypes();
    if (!mounted || cached == null || cached.isEmpty) {
      return;
    }
    final current = _bootstrap['vendor_types'];
    final currentSlugs = BootstrapCache.typeSlugs(current);
    final cachedSlugs = BootstrapCache.typeSlugs(cached);
    // Upgrade when cache has the full admin set; never shrink the painted grid.
    if (cachedSlugs.length <= currentSlugs.length &&
        cachedSlugs.difference(currentSlugs).isEmpty) {
      return;
    }
    setState(() {
      _applyBootstrap(
        Map<String, dynamic>.from(_bootstrap)
          ..['vendor_types'] = BootstrapCache.bestTypes(
            remote: current,
            cached: cached,
          ),
      );
    });
  }

  Future<void> _refreshBootstrap() async {
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      final remote = await ApiClient().getJson('/app/bootstrap');
      final types = remote['vendor_types'];
      if (!mounted || types is! List) {
        return;
      }
      final previousTypes = _bootstrap['vendor_types'];
      final previousSlugs = BootstrapCache.typeSlugs(previousTypes);
      final previousMaps = (previousTypes is List ? previousTypes : const [])
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
      final merged = Map<String, dynamic>.from(remote);
      final richest = BootstrapCache.bestTypes(
        remote: types,
        cached: previousMaps,
      );
      final nextSlugs = BootstrapCache.typeSlugs(richest);
      // Keep the already-painted grid stable unless admin added new type slugs.
      if (nextSlugs.difference(previousSlugs).isEmpty) {
        merged['vendor_types'] = previousTypes is List && previousMaps.isNotEmpty
            ? previousTypes
            : richest;
      } else {
        merged['vendor_types'] = richest;
        unawaited(BootstrapCache.saveVendorTypes(richest));
      }
      setState(() {
        _applyBootstrap(merged);
      });
      FavoritesStore.instance.hydrate(_home);
      NotificationsStore.instance.hydrate(unread: _home.unreadNotifications);
      if (mounted) {
        await ForceUpdate.enforce(
          context,
          bootstrap: _bootstrap,
          fetchLive: false,
        );
      }
    } catch (_) {}
  }

  void _openMenu() => _menu.forward();

  void _closeMenu() => _menu.reverse();

  bool get _menuOpen => _menu.value > 0.04;

  bool _exitDialogOpen = false;

  void _selectTab(int index, {bool resetHistory = false, bool guestBookings = false}) {
    if (index == 0) {
      _refreshBootstrap();
    }
    setState(() {
      if (resetHistory || index == 0) {
        _tabHistory
          ..clear()
          ..add(0);
      } else if (index != _index) {
        _tabHistory.add(index);
      }
      _index = index;
      _guestBookings = index == 1 && guestBookings;
      final searchIndex = _home.on('bookings') ? 2 : 1;
      if (index != searchIndex) {
        _focusSearch = false;
      }
    });
  }

  Future<void> _confirmExitApp() async {
    if (!mounted || _exitDialogOpen) {
      return;
    }
    _exitDialogOpen = true;
    final shouldExit = await showDialog<bool>(
      context: context,
      barrierDismissible: true,
      builder: (dialogContext) => AlertDialog(
        key: const Key('exit-app-dialog'),
        backgroundColor: const Color(0xFF191A1F),
        title: const Text(
          'Exit Lens?',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800),
        ),
        content: const Text(
          'Are you sure you want to close the app?',
          style: TextStyle(color: Color(0xFFC9C4BB)),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            key: const Key('exit-app-confirm'),
            onPressed: () => Navigator.pop(dialogContext, true),
            style: FilledButton.styleFrom(backgroundColor: LensColors.primary),
            child: const Text('Exit'),
          ),
        ],
      ),
    );
    _exitDialogOpen = false;
    if (shouldExit == true) {
      await SystemNavigator.pop();
    }
  }

  void _handleSystemBack() {
    if (_menuOpen) {
      _closeMenu();
      return;
    }
    if (_tabHistory.length > 1) {
      _tabHistory.removeLast();
      setState(() {
        _index = _tabHistory.last;
        _guestBookings = false;
        final searchIndex = _home.on('bookings')
            ? 2
            : 1;
        if (_index != searchIndex) {
          _focusSearch = false;
        }
      });
      return;
    }
    if (_index != 0) {
      _selectTab(0, resetHistory: true);
      return;
    }
    _confirmExitApp();
  }

  @override
  Widget build(BuildContext context) {
    final home = _home;
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
          onBack: _handleSystemBack,
        ),
      SearchPage(data: home, autofocus: _focusSearch && _index == searchIndex),
      ProfilePage(
        data: home,
        bootstrap: _bootstrap,
        onOpenBookings: bookingsOn ? () => _selectTab(1) : null,
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

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) {
          return;
        }
        _handleSystemBack();
      },
      child: AnimatedBuilder(
      animation: _menuAnim,
      builder: (context, child) {
        final t = _menuAnim.value;
        final size = MediaQuery.sizeOf(context);
        final menuWidth = math.min(340.0, size.width * 0.72);
        final open = t > 0.04;

        return ColoredBox(
          color: const Color(0xFF07080A),
          child: Stack(
            children: [
              IgnorePointer(ignoring: open, child: child),
              if (open)
                Positioned.fill(
                  child: GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: _closeMenu,
                    child: ColoredBox(
                      color: Colors.black.withValues(alpha: 0.32),
                    ),
                  ),
                ),
              if (open)
                Positioned(
                  top: 10,
                  bottom: 10,
                  right: 8,
                  width: menuWidth,
                  child: SlideTransition(
                    position: Tween<Offset>(
                      begin: const Offset(1, 0),
                      end: Offset.zero,
                    ).animate(_menuAnim),
                    child: _LensMenu(
                      selected: selected,
                      home: home,
                      bookingsOn: bookingsOn,
                      onClose: _closeMenu,
                      onOpen: (id) =>
                          _openMenuItem(context, home, bookingsOn, id),
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
        bottomNavigationBar: LensNavBar.bar(
          bookingsOn: bookingsOn,
          index: visibleIndex,
          onCreate: () => AppShell.create(context),
          onSelect: _selectTab,
        ),
      ),
      ),
    );
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
        _selectTab(0, resetHistory: true);
      case 'search':
        _openSearch();
      case 'bookings':
        if (bookingsOn) {
          _selectTab(1);
        }
      case 'profile':
        _selectTab(bookingsOn ? 3 : 2);
      case 'favorites':
        openFavorites(context, home);
      case 'notifications':
        openNotifications(context, home);
      case 'messages':
        Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => MessagesPage(home: home)),
        );
      case 'wallet':
        openWallet(context, home);
      case 'report':
        openReportIssue(context);
      case 'settings':
        Navigator.of(context).push(
          MaterialPageRoute<void>(builder: (_) => AppSettingsPage(home: home)),
        );
      case 'signin':
        _selectTab(bookingsOn ? 3 : 2);
      case 'signout':
        SessionStore.instance.signOut();
        _selectTab(0, resetHistory: true);
    }
  }

  void openBookings({bool guestPreview = false}) {
    final bookingsOn = _home.on('bookings');
    if (!bookingsOn) {
      return;
    }
    _selectTab(1, guestBookings: guestPreview);
  }

  void _openSearch({bool focus = false}) {
    final bookingsOn = _home.on('bookings');
    _selectTab(bookingsOn ? 2 : 1);
    if (focus) {
      setState(() => _focusSearch = true);
    }
  }
}

class _LensMenu extends StatelessWidget {
  const _LensMenu({
    required this.selected,
    required this.home,
    required this.bookingsOn,
    required this.onClose,
    required this.onOpen,
  });

  final String selected;
  final HomeData home;
  final bool bookingsOn;
  final VoidCallback onClose;
  final ValueChanged<String> onOpen;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final scale = width < 720 ? width / 767 : 1.0;
    final labelSize = 26 * scale;

    return ClipRRect(
      borderRadius: BorderRadius.circular(28),
      child: DecoratedBox(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(28),
          color: const Color(0xFF111216),
          border: Border.all(color: LensColors.graphite, width: 1),
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
                        child: BrandLogo(name: home.name),
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
                    fontSize: labelSize,
                  ),
                  if (home.on('favorites'))
                    _item(
                      id: 'favorites',
                      icon: Icons.favorite_border_rounded,
                      label: 'Favorites',
                      badge: FavoritesStore.instance.count,
                      fontSize: labelSize,
                    ),
                  if (bookingsOn)
                    _item(
                      id: 'bookings',
                      icon: Icons.calendar_month_outlined,
                      label: 'Bookings',
                      fontSize: labelSize,
                    ),
                  _item(
                    id: 'search',
                    icon: Icons.search,
                    label: 'Search',
                    fontSize: labelSize,
                  ),
                  _item(
                    id: 'profile',
                    icon: Icons.person_outline,
                    label: 'Profile',
                    fontSize: labelSize,
                  ),
                  if (home.on('wallets'))
                    _item(
                      id: 'wallet',
                      icon: Icons.account_balance_wallet_outlined,
                      label: 'Wallet',
                      fontSize: labelSize,
                    ),
                  if (home.on('notifications'))
                    _item(
                      id: 'notifications',
                      icon: Icons.notifications_none_rounded,
                      label: 'Notifications',
                      badge: NotificationsStore.instance.unread,
                      fontSize: labelSize,
                    ),
                  const SizedBox(height: 8),
                  const Divider(color: Color(0xFF2A2D34), height: 1),
                  const SizedBox(height: 12),
                  if (home.on('chat'))
                    _item(
                      id: 'messages',
                      icon: Icons.chat_bubble_outline_rounded,
                      label: 'Messages',
                      fontSize: labelSize,
                    ),
                  if (home.on('issue_reports'))
                    _item(
                      id: 'report',
                      icon: Icons.help_outline_rounded,
                      label: 'Report an Issue',
                      fontSize: labelSize,
                    ),
                  _item(
                    id: 'settings',
                    icon: Icons.settings_outlined,
                    label: 'App Settings',
                    fontSize: labelSize,
                  ),
                  _item(
                    id: signedIn ? 'signout' : 'signin',
                    icon: signedIn ? Icons.logout_rounded : Icons.login_rounded,
                    label: signedIn ? 'Log Out' : 'Sign In',
                    fontSize: labelSize,
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _item({
    required String id,
    required IconData icon,
    required String label,
    required double fontSize,
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
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: fontSize,
                      height: 1.15,
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
