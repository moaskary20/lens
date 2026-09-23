import 'package:flutter/material.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';

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

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: FavoritesStore.instance,
      builder: (context, _) {
        final saved = FavoritesStore.instance.saved(vendor.id);
        final icon = Icon(
          saved ? Icons.favorite_rounded : (circle ? Icons.favorite_border_rounded : Icons.favorite_border),
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
          onTap: () => FavoritesStore.instance.toggle(vendor),
          behavior: HitTestBehavior.opaque,
          child: Padding(padding: const EdgeInsets.all(4), child: child),
        );
      },
    );
  }
}
