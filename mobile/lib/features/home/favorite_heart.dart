import 'package:flutter/material.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/login_view.dart';

class FavoriteHeart extends StatelessWidget {
  const FavoriteHeart({
    super.key,
    required this.vendor,
    this.size = 18,
    this.circle = false,
    this.color,
  });

  final VendorCard vendor;
  final double size;
  final bool circle;
  final Color? color;

  Future<void> _onTap(BuildContext context) async {
    if (SessionStore.instance.isGuest) {
      await _promptSignIn(context);
      return;
    }
    await FavoritesStore.instance.toggle(vendor);
  }

  Future<void> _promptSignIn(BuildContext context) async {
    final goLogin = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        key: const Key('guest-favorite-dialog'),
        backgroundColor: const Color(0xFF161412),
        title: const Text(
          "You're not signed in",
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800),
        ),
        content: const Text(
          'Create an account or sign in to save vendors to your favorites.',
          style: TextStyle(color: Color(0xFFD0CBC3), height: 1.4),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Not now'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            style: FilledButton.styleFrom(backgroundColor: LensColors.primary),
            child: const Text('Sign In'),
          ),
        ],
      ),
    );

    if (goLogin != true || !context.mounted) {
      return;
    }

    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (routeContext) => Scaffold(
          backgroundColor: LensColors.charcoal,
          body: Stack(
            children: [
              LoginView(
                onSuccess: () => Navigator.of(routeContext).pop(),
              ),
              SafeArea(
                child: IconButton(
                  tooltip: 'Back',
                  onPressed: () => Navigator.of(routeContext).pop(),
                  icon: const Icon(
                    Icons.chevron_left_rounded,
                    color: Colors.white,
                    size: 30,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: FavoritesStore.instance,
      builder: (context, _) {
        final saved = FavoritesStore.instance.saved(vendor.id);
        final icon = Icon(
          saved
              ? Icons.favorite_rounded
              : (circle ? Icons.favorite_border_rounded : Icons.favorite_border),
          color: saved ? LensColors.primary : (color ?? LensColors.cream),
          size: size,
        );
        final child = circle
            ? Container(
                width: size + 16,
                height: size + 16,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: const Color(0xB3141518),
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0x33FFFFFF)),
                ),
                child: icon,
              )
            : icon;

        return GestureDetector(
          onTap: () => _onTap(context),
          behavior: HitTestBehavior.opaque,
          child: Padding(padding: const EdgeInsets.all(4), child: child),
        );
      },
    );
  }
}
