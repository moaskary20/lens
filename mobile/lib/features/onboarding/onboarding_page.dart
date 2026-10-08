import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/widgets/brand_logo.dart';
import 'package:lens/features/auth/user_register_page.dart';
import 'package:lens/features/auth/vendor_register_page.dart';
import 'package:lens/features/shell/app_shell.dart';

class OnboardingPage extends StatefulWidget {
  const OnboardingPage({super.key, required this.bootstrap});

  final Map<String, dynamic> bootstrap;

  @override
  State<OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<OnboardingPage> {
  final _pages = PageController();
  int _index = 0;
  String _role = 'user';

  static const _copy = [
    (
      lead: 'Start with\n',
      accent: 'an idea.',
      body: "Tell Lens what you're imagining. Our AI turns your idea into a creative direction and finds the right team.",
    ),
    (
      lead: 'Book with\n',
      accent: 'confidence.',
      body: 'Your payment stays protected until the agreed work is completed.',
    ),
  ];

  bool get _isRoleStep => _index >= 2;

  void _finish() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute<void>(builder: (_) => AppShell(bootstrap: widget.bootstrap)),
    );
  }

  void _openHome() {
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute<void>(builder: (_) => AppShell(bootstrap: widget.bootstrap)),
      (route) => false,
    );
  }

  void _continueAccount() {
    final onSuccess = _openHome;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _role == 'vendor'
            ? VendorRegisterPage(bootstrap: widget.bootstrap, onSuccess: onSuccess)
            : UserRegisterPage(bootstrap: widget.bootstrap, onSuccess: onSuccess),
      ),
    );
  }

  void _next() {
    if (_isRoleStep) {
      _finish();
      return;
    }
    _pages.nextPage(duration: const Duration(milliseconds: 380), curve: Curves.easeOutCubic);
  }

  @override
  void dispose() {
    _pages.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final copy = _isRoleStep ? _copy.last : _copy[_index];
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: Stack(
        fit: StackFit.expand,
        children: [
          PageView(
            controller: _pages,
            onPageChanged: (index) => setState(() => _index = index),
            children: [
              const _MockupScene(asset: 'lib/assits/onboard1.png'),
              const _MockupScene(asset: 'lib/assits/onboard2.png'),
              SafeArea(
                child: Column(
                  children: [
                    Padding(
                      padding: EdgeInsets.fromLTRB(20, 8, 16, 0),
                      child: Row(
                        children: [
                          Expanded(
                            child: BrandLogo(
                              name: widget.bootstrap['name']?.toString() ?? 'Lens',
                              logoUrl: widget.bootstrap['logo']?.toString(),
                              width: 180,
                              height: 40,
                              fontSize: 32,
                            ),
                          ),
                          _StepMarks(),
                        ],
                      ),
                    ),
                    Expanded(
                      child: _RoleScene(
                        selected: _role,
                        onSelect: (role) => setState(() => _role = role),
                        onContinue: _continueAccount,
                        onGuest: _finish,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (!_isRoleStep)
            SafeArea(
              child: Column(
                children: [
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: _finish,
                      style: TextButton.styleFrom(
                        foregroundColor: Colors.transparent,
                        padding: const EdgeInsets.fromLTRB(20, 10, 20, 10),
                      ),
                      child: const Text(
                        'Skip',
                        style: TextStyle(color: Colors.transparent, fontSize: 15, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ),
                  const Spacer(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(22, 0, 22, 10),
                    child: SizedBox(
                      height: 56,
                      width: double.infinity,
                      child: GestureDetector(
                        onTap: _next,
                        behavior: HitTestBehavior.opaque,
                        child: const Center(
                          child: Text(
                            'Next',
                            style: TextStyle(color: Colors.transparent, fontSize: 16, fontWeight: FontWeight.w800),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          IgnorePointer(
            child: Opacity(
              opacity: 0,
              child: Text('${copy.lead}${copy.accent}'),
            ),
          ),
        ],
      ),
    );
  }
}

class _MockupScene extends StatelessWidget {
  const _MockupScene({required this.asset});

  final String asset;

  @override
  Widget build(BuildContext context) {
    final padding = MediaQuery.paddingOf(context);
    return ColoredBox(
      color: LensColors.charcoal,
      child: Padding(
        padding: EdgeInsets.only(top: padding.top, bottom: padding.bottom),
        child: SizedBox.expand(
          child: Image.asset(
            asset,
            fit: BoxFit.fill,
            alignment: Alignment.topCenter,
            filterQuality: FilterQuality.high,
            errorBuilder: (_, __, ___) => const SizedBox.expand(),
          ),
        ),
      ),
    );
  }
}

class _StepMarks extends StatelessWidget {
  const _StepMarks();

  @override
  Widget build(BuildContext context) {
    return Row(
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
    );
  }
}

class _PillButton extends StatelessWidget {
  const _PillButton({required this.label, required this.onPressed});

  final String label;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 54,
      width: double.infinity,
      child: FilledButton(
        onPressed: onPressed,
        style: FilledButton.styleFrom(
          backgroundColor: LensColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          shape: const StadiumBorder(),
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            Text(label, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
            const Align(
              alignment: Alignment.centerRight,
              child: Icon(Icons.chevron_right_rounded, size: 26),
            ),
          ],
        ),
      ),
    );
  }
}

class _RoleScene extends StatelessWidget {
  const _RoleScene({
    required this.selected,
    required this.onSelect,
    required this.onContinue,
    required this.onGuest,
  });

  final String selected;
  final ValueChanged<String> onSelect;
  final VoidCallback onContinue;
  final VoidCallback onGuest;

  @override
  Widget build(BuildContext context) {
    return Padding(
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
              selected: selected == 'user',
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
              onTap: () => onSelect('user'),
            ),
          ),
          const SizedBox(height: 12),
          Expanded(
            child: _RoleCard(
              selected: selected == 'vendor',
              asset: 'lib/assits/onboard3_2.png',
              icon: Icons.photo_camera_outlined,
              title: 'Join as Vendor',
              subtitle: 'Photographer - videographer - reels maker - model - food stylist - ugc - voice over - AI creator',
              features: const [
                (Icons.tune_rounded, 'Build your profile'),
                (Icons.bookmark_border_rounded, 'Get discovered'),
                (Icons.calendar_today_outlined, 'Manage bookings'),
              ],
              onTap: () => onSelect('vendor'),
            ),
          ),
          const SizedBox(height: 16),
          _PillButton(label: 'Continue', onPressed: onContinue),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 10),
            child: Center(child: Text('— or —', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13))),
          ),
          SizedBox(
            height: 52,
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: onGuest,
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
            border: Border.all(
              color: selected ? LensColors.primary.withValues(alpha: 0.55) : const Color(0x22FF5A1F),
              width: selected ? 1.2 : 0.8,
            ),
            boxShadow: selected
                ? [BoxShadow(color: LensColors.primary.withValues(alpha: 0.22), blurRadius: 22, spreadRadius: -4)]
                : null,
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(21),
            child: Stack(
              fit: StackFit.expand,
              children: [
                ShaderMask(
                  blendMode: BlendMode.dstIn,
                  shaderCallback: (rect) => RadialGradient(
                    center: Alignment.centerRight,
                    radius: 1.15,
                    colors: const [Color(0xFFFFFFFF), Color(0xE6FFFFFF), Color(0x00FFFFFF)],
                    stops: const [0.46, 0.78, 1],
                  ).createShader(rect),
                  child: Image.asset(
                    asset,
                    fit: BoxFit.cover,
                    alignment: Alignment.centerRight,
                    errorBuilder: (_, __, ___) => const ColoredBox(color: Colors.black),
                  ),
                ),
                const DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.centerLeft,
                      end: Alignment.centerRight,
                      colors: [Color(0xF2000000), Color(0x99000000), Color(0x33000000), Color(0x00000000)],
                      stops: [0, 0.38, 0.7, 1],
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
