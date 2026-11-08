import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class PoliciesPage extends StatefulWidget {
  const PoliciesPage({super.key, required this.home, this.initialSlug});

  final HomeData home;
  final String? initialSlug;

  @override
  State<PoliciesPage> createState() => _PoliciesPageState();
}

class _PoliciesPageState extends State<PoliciesPage> {
  String? _openSlug;

  @override
  void initState() {
    super.initState();
    final pages = widget.home.policyPages;
    final initial = widget.initialSlug;
    if (initial != null && pages.any((page) => page.slug == initial)) {
      _openSlug = initial;
    } else if (pages.isNotEmpty) {
      _openSlug = pages.first.slug;
    }
  }

  @override
  Widget build(BuildContext context) {
    final pages = widget.home.policyPages;
    return ProfileScaffold(
      title: 'Policies',
      child: pages.isEmpty
          ? const Padding(
              padding: EdgeInsets.fromLTRB(20, 40, 20, 20),
              child: Text(
                'Policies are loading from Lens. Pull to refresh home, then open again.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Color(0xFF8E8B84), height: 1.4),
              ),
            )
          : ListView.separated(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
              itemCount: pages.length,
              separatorBuilder: (context, index) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final page = pages[index];
                final open = _openSlug == page.slug;
                return AnimatedContainer(
                  duration: const Duration(milliseconds: 180),
                  decoration: BoxDecoration(
                    color: const Color(0xFF161412),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: open
                          ? LensColors.primary.withValues(alpha: 0.45)
                          : const Color(0xFF2A2A2E),
                    ),
                  ),
                  child: Theme(
                    data: Theme.of(context).copyWith(
                      dividerColor: Colors.transparent,
                      splashColor: LensColors.primary.withValues(alpha: 0.08),
                      highlightColor: Colors.transparent,
                    ),
                    child: ExpansionTile(
                      key: ValueKey<String>('policy-${page.slug}-$_openSlug'),
                      initiallyExpanded: open,
                      maintainState: true,
                      onExpansionChanged: (expanded) {
                        setState(() {
                          _openSlug = expanded ? page.slug : null;
                        });
                      },
                      tilePadding: const EdgeInsets.fromLTRB(14, 2, 10, 2),
                      childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                      leading: Icon(
                        open
                            ? Icons.description_rounded
                            : Icons.description_outlined,
                        color: LensColors.primary,
                      ),
                      title: Text(
                        page.title,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      trailing: AnimatedRotation(
                        turns: open ? 0.5 : 0,
                        duration: const Duration(milliseconds: 180),
                        child: const Icon(
                          Icons.expand_more_rounded,
                          color: Color(0xFF8E8B84),
                        ),
                      ),
                      children: [
                        Align(
                          alignment: Alignment.centerLeft,
                          child: Text(
                            page.body.isEmpty
                                ? 'Content unavailable.'
                                : page.body,
                            style: const TextStyle(
                              color: Color(0xFFD0CBC3),
                              height: 1.5,
                              fontSize: 14,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
    );
  }
}

/// Opens Policies with the matching CMS slug expanded.
void openPolicyPage(BuildContext context, HomeData home, String slug) {
  Navigator.of(context).push(
    MaterialPageRoute<void>(
      builder: (_) => PoliciesPage(home: home, initialSlug: slug),
    ),
  );
}
