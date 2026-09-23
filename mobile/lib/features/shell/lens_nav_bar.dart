import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';

class LensNavBar {
  const LensNavBar._();

  static const inactive = Color(0xFFD6D1C8);

  static Widget fab({required VoidCallback onPressed}) {
    return FloatingActionButton(
      onPressed: onPressed,
      backgroundColor: LensColors.primary,
      foregroundColor: Colors.white,
      elevation: 3,
      highlightElevation: 6,
      focusElevation: 3,
      hoverElevation: 4,
      shape: const CircleBorder(),
      child: const Icon(Icons.add, size: 30, weight: 500),
    );
  }

  static Widget bar({
    required bool bookingsOn,
    required int index,
    required ValueChanged<int> onSelect,
  }) {
    return BottomAppBar(
      color: const Color(0xFF111114),
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      padding: EdgeInsets.zero,
      height: 72,
      shape: const CircularNotchedRectangle(),
      notchMargin: 7,
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
            const SizedBox(width: 72),
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
