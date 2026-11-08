import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class PoliciesPage extends StatelessWidget {
  const PoliciesPage({super.key, required this.home, this.initialSlug});

  final HomeData home;
  final String? initialSlug;

  @override
  Widget build(BuildContext context) {
    final pages = home.policyPages;
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
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final page = pages[index];
                return Material(
                  color: const Color(0xFF161412),
                  borderRadius: BorderRadius.circular(16),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(16),
                    onTap: () => _open(context, page),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 15,
                      ),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.description_outlined,
                            color: LensColors.primary,
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Text(
                              page.title,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
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
                );
              },
            ),
    );
  }

  void _open(BuildContext context, PolicyPageItem page) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _PolicyDetailPage(page: page),
      ),
    );
  }
}

class _PolicyDetailPage extends StatelessWidget {
  const _PolicyDetailPage({required this.page});

  final PolicyPageItem page;

  @override
  Widget build(BuildContext context) {
    return ProfileScaffold(
      title: page.title,
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        child: Text(
          page.body.isEmpty ? 'Content unavailable.' : page.body,
          style: const TextStyle(
            color: Color(0xFFD0CBC3),
            height: 1.5,
            fontSize: 14.5,
          ),
        ),
      ),
    );
  }
}

/// Opens a CMS policy by slug when available from bootstrap.
void openPolicyPage(BuildContext context, HomeData home, String slug) {
  final match = home.policyPages.cast<PolicyPageItem?>().firstWhere(
        (item) => item?.slug == slug,
        orElse: () => null,
      );
  if (match == null) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => PoliciesPage(home: home)),
    );
    return;
  }
  Navigator.of(context).push(
    MaterialPageRoute<void>(
      builder: (_) => _PolicyDetailPage(page: match),
    ),
  );
}
