import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/auth_page.dart';
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

  void _continueAccount() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute<void>(
        builder: (_) => AuthPage(
          bootstrap: widget.bootstrap,
          intendedRole: _role == 'vendor' ? 'vendor' : 'client',
        ),
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
                  if (_isRoleStep)
                    const _StepMarks()
                  else
                    TextButton(
                      onPressed: _finish,
                      child: const Text('Skip', style: TextStyle(color: Color(0xFFB0ABA3), fontSize: 15, fontWeight: FontWeight.w600)),
                    ),
                ],
              ),
            ),
            Expanded(
              child: PageView(
                controller: _pages,
                onPageChanged: (index) => setState(() => _index = index),
                children: [
                  const _IdeaScene(),
                  const _TrustScene(),
                  _RoleScene(
                    selected: _role,
                    onSelect: (role) => setState(() => _role = role),
                    onContinue: _continueAccount,
                    onGuest: _finish,
                  ),
                ],
              ),
            ),
            if (!_isRoleStep) ...[
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text.rich(
                      TextSpan(
                        children: [
                          TextSpan(
                            text: copy.lead,
                            style: const TextStyle(color: Colors.white, fontSize: 34, fontWeight: FontWeight.w800, height: 1.08),
                          ),
                          TextSpan(
                            text: copy.accent,
                            style: const TextStyle(color: LensColors.primary, fontSize: 34, fontWeight: FontWeight.w800, height: 1.08),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(copy.body, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 15, height: 1.45)),
                  ],
                ),
              ),
              const SizedBox(height: 18),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  for (var i = 0; i < 3; i++)
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 220),
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      width: i == _index ? 22 : 7,
                      height: 7,
                      decoration: BoxDecoration(
                        color: i == _index ? LensColors.primary : const Color(0xFF3A3A3E),
                        borderRadius: BorderRadius.circular(99),
                      ),
                    ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 14),
                child: _PillButton(label: 'Next', onPressed: _next),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _IdeaScene extends StatelessWidget {
  const _IdeaScene();

  static const _asset = 'lib/assits/onboard1.png';

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 4, 8, 0),
      child: Image.asset(
        _asset,
        fit: BoxFit.contain,
        alignment: Alignment.center,
        filterQuality: FilterQuality.high,
        errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite),
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

class _TrustScene extends StatelessWidget {
  const _TrustScene();

  static const _asset = 'lib/assits/onboard2.png';

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 4, 8, 0),
      child: Image.asset(
        _asset,
        fit: BoxFit.contain,
        alignment: Alignment.center,
        filterQuality: FilterQuality.high,
        errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite),
      ),
    );
  }
}
