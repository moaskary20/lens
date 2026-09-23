import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';

class RoleChoicePage extends StatefulWidget {
  const RoleChoicePage({super.key, this.initialRole = 'user'});

  final String initialRole;

  @override
  State<RoleChoicePage> createState() => _RoleChoicePageState();
}

class _RoleChoicePageState extends State<RoleChoicePage> {
  late String _role;

  @override
  void initState() {
    super.initState();
    _role = widget.initialRole == 'vendor' ? 'vendor' : 'user';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF070707),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 16, 0),
              child: Row(
                children: [
                  const Expanded(
                    child: Text('Lens', style: TextStyle(color: LensColors.primary, fontSize: 32, fontWeight: FontWeight.w800, height: 1)),
                  ),
                  Row(
                    children: [
                      Container(
                        width: 22,
                        height: 4,
                        decoration: BoxDecoration(color: LensColors.primary, borderRadius: BorderRadius.circular(99)),
                      ),
                      const SizedBox(width: 6),
                      Container(
                        width: 22,
                        height: 4,
                        decoration: BoxDecoration(color: LensColors.primary, borderRadius: BorderRadius.circular(99)),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text.rich(
                      TextSpan(
                        style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.15),
                        children: const [
                          TextSpan(text: 'How will you use '),
                          TextSpan(text: 'Lens', style: TextStyle(color: LensColors.primary)),
                          TextSpan(text: '?'),
                        ],
                      ),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'Choose the option that best describes you.',
                      style: TextStyle(color: Color(0xFFB0ABA3), fontSize: 14.5, height: 1.35),
                    ),
                    const SizedBox(height: 16),
                    Expanded(
                      child: _RoleCard(
                        selected: _role == 'user',
                        asset: 'lib/assits/onboard3_1.png',
                        icon: Icons.person_outline_rounded,
                        title: 'Join as User',
                        subtitle: 'Finding and booking the right creative team.',
                        features: const [
                          (Icons.person_outline_rounded, 'Add client'),
                          (Icons.search_rounded, 'Browse portfolios'),
                          (Icons.calendar_today_outlined, 'Book with ease'),
                          (Icons.lock_outline_rounded, 'Secure payments'),
                        ],
                        onTap: () => setState(() => _role = 'user'),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Expanded(
                      child: _RoleCard(
                        selected: _role == 'vendor',
                        asset: 'lib/assits/onboard3_2.png',
                        icon: Icons.photo_camera_outlined,
                        title: 'Join as Vendor',
                        subtitle: 'Photographer - videographer - reels maker - model - food stylist - ugc - voice over - AI creator',
                        features: const [
                          (Icons.tune_rounded, 'Build your profile'),
                          (Icons.bookmark_border_rounded, 'Get discovered'),
                          (Icons.calendar_today_outlined, 'Manage bookings'),
                        ],
                        onTap: () => setState(() => _role = 'vendor'),
                      ),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      height: 54,
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: () => Navigator.of(context).pop(_role),
                        style: FilledButton.styleFrom(
                          backgroundColor: LensColors.primary,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: const StadiumBorder(),
                        ),
                        child: const Stack(
                          alignment: Alignment.center,
                          children: [
                            Text('Continue', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                            Align(alignment: Alignment.centerRight, child: Icon(Icons.chevron_right_rounded, size: 26)),
                          ],
                        ),
                      ),
                    ),
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 10),
                      child: Center(child: Text('— or —', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13))),
                    ),
                    SizedBox(
                      height: 52,
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: () => Navigator.of(context).pop(),
                        icon: const Icon(Icons.person_outline_rounded, size: 18),
                        label: const Text('Continue as a guest', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.white,
                          side: const BorderSide(color: Color(0xFF2E2A26)),
                          backgroundColor: const Color(0xFF141210),
                          shape: const StadiumBorder(),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Center(
                      child: Text(
                        'Explore Lens without creating an account.',
                        style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RoleCard extends StatelessWidget {
  const _RoleCard({
    required this.selected,
    required this.asset,
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.features,
    required this.onTap,
  });

  final bool selected;
  final String asset;
  final IconData icon;
  final String title;
  final String subtitle;
  final List<(IconData, String)> features;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: LensColors.primary, width: selected ? 1.8 : 1.2),
            boxShadow: selected
                ? [BoxShadow(color: LensColors.primary.withValues(alpha: 0.28), blurRadius: 16)]
                : null,
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20.4),
            child: Stack(
              fit: StackFit.expand,
              children: [
                Image.asset(
                  asset,
                  fit: BoxFit.cover,
                  alignment: Alignment.centerRight,
                  errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite),
                ),
                const DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.centerLeft,
                      end: Alignment.centerRight,
                      colors: [Color(0xF2000000), Color(0xC2000000), Color(0x66000000), Color(0x14000000)],
                      stops: [0, 0.42, 0.68, 1],
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 12, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            width: 34,
                            height: 34,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              border: Border.all(color: Colors.white.withValues(alpha: 0.85)),
                            ),
                            child: Icon(icon, color: Colors.white, size: 18),
                          ),
                          const Spacer(),
                          if (selected)
                            Container(
                              width: 22,
                              height: 22,
                              decoration: const BoxDecoration(color: LensColors.primary, shape: BoxShape.circle),
                              child: const Icon(Icons.check_rounded, color: Colors.white, size: 14),
                            ),
                        ],
                      ),
                      Expanded(
                        child: Align(
                          alignment: Alignment.bottomLeft,
                          child: FittedBox(
                            alignment: Alignment.bottomLeft,
                            fit: BoxFit.scaleDown,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 20)),
                                const SizedBox(height: 4),
                                SizedBox(
                                  width: 200,
                                  child: Text(subtitle, style: const TextStyle(color: Color(0xFFD8D3CC), fontSize: 13, height: 1.3)),
                                ),
                                const SizedBox(height: 8),
                                Container(width: 28, height: 2, color: LensColors.primary),
                                const SizedBox(height: 8),
                                for (final feature in features)
                                  Padding(
                                    padding: const EdgeInsets.only(bottom: 4),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(feature.$1, color: Colors.white, size: 15),
                                        const SizedBox(width: 8),
                                        Text(feature.$2, style: const TextStyle(color: Color(0xFFE8E4DE), fontSize: 13)),
                                      ],
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
