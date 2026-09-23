import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';

class SortPage extends StatefulWidget {
  const SortPage({super.key, this.initial = 'rating'});

  final String initial;

  @override
  State<SortPage> createState() => _SortPageState();
}

class _SortPageState extends State<SortPage> {
  late String _selected;

  @override
  void initState() {
    super.initState();
    _selected = widget.initial == 'recommended' ? 'rating' : widget.initial;
  }

  bool get _priceSelected => _selected == 'price_asc' || _selected == 'price_desc';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            const SizedBox(height: 8),
            Container(
              width: 42,
              height: 4,
              decoration: BoxDecoration(color: const Color(0xFF3A3D44), borderRadius: BorderRadius.circular(99)),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 8, 8),
              child: Row(
                children: [
                  const Expanded(
                    child: Text('Sort', style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800)),
                  ),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close, color: LensColors.cream, size: 26),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                children: [
                  _optionCard(
                    icon: Icons.star_border_rounded,
                    title: 'Top Rated',
                    subtitle: 'Highest rated professionals',
                    value: 'rating',
                  ),
                  _optionCard(
                    icon: Icons.chat_bubble_outline_rounded,
                    title: 'Best Review',
                    subtitle: 'Most positive client reviews',
                    value: 'reviews',
                  ),
                  _optionCard(
                    icon: Icons.location_on_outlined,
                    title: 'Location',
                    subtitle: 'Nearest to you',
                    value: 'location',
                  ),
                  _priceCard(),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
              child: SizedBox(
                width: double.infinity,
                height: 54,
                child: FilledButton(
                  onPressed: () => Navigator.of(context).pop(_selected),
                  style: FilledButton.styleFrom(
                    backgroundColor: LensColors.primary,
                    foregroundColor: Colors.white,
                    shape: const StadiumBorder(),
                  ),
                  child: const Text('Apply', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                ),
              ),
            ),
            TextButton(
              onPressed: () => setState(() => _selected = 'rating'),
              child: const Text('Clear All', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 15)),
            ),
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }

  Widget _optionCard({
    required IconData icon,
    required String title,
    required String subtitle,
    required String value,
  }) {
    final selected = _selected == value;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => setState(() => _selected = value),
          borderRadius: BorderRadius.circular(18),
          child: Container(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
            decoration: BoxDecoration(
              color: const Color(0xFF141518),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: const Color(0xFF2A2D34)),
            ),
            child: Row(
              children: [
                Icon(icon, color: selected ? LensColors.primary : const Color(0xFFD6D1C8), size: 22),
                const SizedBox(width: 12),
                Expanded(child: _labels(title, subtitle)),
                _radio(selected),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _priceCard() {
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
      decoration: BoxDecoration(
        color: const Color(0xFF141518),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF2A2D34)),
      ),
      child: Column(
        children: [
          InkWell(
            onTap: () => setState(() => _selected = 'price_asc'),
            child: Row(
              children: [
                Icon(Icons.toll_outlined, color: _priceSelected ? LensColors.primary : const Color(0xFFD6D1C8), size: 22),
                const SizedBox(width: 12),
                const Expanded(child: _SortLabels(title: 'Price', subtitle: 'Sort by project price')),
                _radio(_priceSelected),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFF101114),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFF22242A)),
            ),
            child: Column(
              children: [
                _nested('Lowest to Highest', 'price_asc'),
                const Divider(height: 1, color: Color(0xFF22242A)),
                _nested('Highest to Lowest', 'price_desc'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _nested(String title, String value) {
    return InkWell(
      onTap: () => setState(() => _selected = value),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 14, 12, 14),
        child: Row(
          children: [
            Expanded(
              child: Text(title, style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w600)),
            ),
            _radio(_selected == value),
          ],
        ),
      ),
    );
  }

  Widget _labels(String title, String subtitle) => _SortLabels(title: title, subtitle: subtitle);

  Widget _radio(bool selected) {
    return Container(
      width: 22,
      height: 22,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: selected ? LensColors.primary : const Color(0xFF6B6E76), width: 2),
        color: selected ? LensColors.primary : Colors.transparent,
      ),
    );
  }
}

class _SortLabels extends StatelessWidget {
  const _SortLabels({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
        const SizedBox(height: 2),
        Text(subtitle, style: const TextStyle(color: LensColors.slate, fontSize: 12, fontWeight: FontWeight.w500)),
      ],
    );
  }
}

List<VendorCard> sortVendors(List<VendorCard> input, String sort) {
  final items = List<VendorCard>.from(input);
  int byRating(VendorCard a, VendorCard b) {
    final rating = b.ratingAvg.compareTo(a.ratingAvg);
    return rating != 0 ? rating : b.ratingCount.compareTo(a.ratingCount);
  }

  switch (sort) {
    case 'rating':
      items.sort(byRating);
    case 'reviews':
      items.sort((a, b) {
        final count = b.ratingCount.compareTo(a.ratingCount);
        return count != 0 ? count : b.ratingAvg.compareTo(a.ratingAvg);
      });
    case 'location':
      items.sort((a, b) => _distance(a).compareTo(_distance(b)));
    case 'price_asc':
      items.sort((a, b) => (a.startingFrom ?? 1e12).compareTo(b.startingFrom ?? 1e12));
    case 'price_desc':
      items.sort((a, b) => (b.startingFrom ?? 0).compareTo(a.startingFrom ?? 0));
  }
  return items;
}

double _distance(VendorCard vendor) {
  const originLat = 30.0444;
  const originLng = 31.2357;
  final lat = (vendor.latitude ?? originLat) - originLat;
  final lng = (vendor.longitude ?? originLng) - originLng;
  return lat * lat + lng * lng;
}
