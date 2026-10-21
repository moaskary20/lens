import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';

class LensNavBar {
  const LensNavBar._();

  static const inactive = Color(0xFFD6D1C8);

  static Widget bar({
    required bool bookingsOn,
    required int index,
    required ValueChanged<int> onSelect,
    required VoidCallback onCreate,
    bool withFab = true,
  }) {
    return BottomAppBar(
      color: LensColors.charcoal,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      padding: EdgeInsets.zero,
      height: 72,
      child: SizedBox(
        height: 72,
        child: Row(
          children: [
            _item(
              selectedIcon: Icons.home_rounded,
              icon: Icons.home_outlined,
              label: 'Home',
              selected: index == 0,
              onTap: () => onSelect(0),
            ),
            if (bookingsOn)
              _item(
                selectedIcon: Icons.calendar_today_rounded,
                icon: Icons.calendar_today_outlined,
                label: 'Bookings',
                selected: index == 1,
                onTap: () => onSelect(1),
              ),
            if (withFab) _createItem(onTap: onCreate),
            _item(
              selectedIcon: Icons.search_rounded,
              icon: Icons.search_rounded,
              label: 'Search',
              selected: index == (bookingsOn ? 2 : 1),
              onTap: () => onSelect(bookingsOn ? 2 : 1),
            ),
            _item(
              selectedIcon: Icons.person_rounded,
              icon: Icons.person_outline_rounded,
              label: 'Profile',
              selected: index == (bookingsOn ? 3 : 2),
              onTap: () => onSelect(bookingsOn ? 3 : 2),
            ),
          ],
        ),
      ),
    );
  }

  static Widget _createItem({required VoidCallback onTap}) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        splashColor: Colors.white.withValues(alpha: 0.16),
        child: Center(
          child: Semantics(
            button: true,
            label: 'Create',
            child: Container(
              width: 44,
              height: 44,
              decoration: const BoxDecoration(
                color: LensColors.primary,
                shape: BoxShape.circle,
              ),
              alignment: Alignment.center,
              child: const Icon(Icons.add, color: Colors.white, size: 28),
            ),
          ),
        ),
      ),
    );
  }

  static Widget _item({
    required IconData icon,
    required IconData selectedIcon,
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    final color = selected ? LensColors.primary : inactive;
    return Expanded(
      child: InkWell(
        onTap: onTap,
        splashColor: LensColors.primary.withValues(alpha: 0.12),
        highlightColor: Colors.transparent,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(selected ? selectedIcon : icon, color: color, size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
