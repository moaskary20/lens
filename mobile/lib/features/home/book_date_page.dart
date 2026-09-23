import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/book_project_page.dart';
import 'package:lens/features/home/favorite_heart.dart';

class BookDatePage extends StatefulWidget {
  const BookDatePage({super.key, required this.vendor, required this.home});

  final VendorCard vendor;
  final HomeData home;

  @override
  State<BookDatePage> createState() => _BookDatePageState();
}

class _BookDatePageState extends State<BookDatePage> {
  static const _times = ['10:00 AM', '12:00 PM', '2:00 PM', '4:00 PM', '6:00 PM'];
  static const _months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
  ];
  static const _monthShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  static const _weekShort = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  static const _dow = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

  static const _packages = [
    _BookPackage('Standard', '4 Hours • 25 Edited Photos • Retouching Included'),
    _BookPackage('Half day', '5 Hours • 40 Edited Photos • Retouching Included'),
    _BookPackage('Full day', '8 Hours • 80 Edited Photos • Full Gallery'),
  ];

  late DateTime _visible;
  late DateTime _selected;
  String _time = '10:00 AM';
  _BookPackage _package = _packages.first;

  VendorCard get vendor => widget.vendor;

  @override
  void initState() {
    super.initState();
    _visible = DateTime(2026, 9);
    _selected = DateTime(2026, 9, 15);
  }

  String get _location {
    if (vendor.location.isNotEmpty) {
      return vendor.location;
    }
    return vendor.city.isEmpty ? 'Cairo, Egypt' : '${vendor.city}, Egypt';
  }

  String get _dateLabel {
    return '${_weekShort[_selected.weekday - 1]}, ${_monthShort[_selected.month - 1]} ${_selected.day}, ${_selected.year}';
  }

  bool _sameDay(DateTime a, DateTime b) => a.year == b.year && a.month == b.month && a.day == b.day;

  bool _isAvailable(DateTime date) {
    if (date.year == 2026 && date.month == 9) {
      const days = {10, 11, 12, 15, 16, 17, 18, 19, 21, 22, 24, 25, 26, 28, 29, 30};
      return days.contains(date.day);
    }
    final seed = vendor.id + date.day;
    if (date.day <= 9 || date.day == 13 || date.day == 20 || date.day == 23 || date.day == 27) {
      return false;
    }
    return seed % 5 != 0;
  }

  List<DateTime> _cells() {
    final first = DateTime(_visible.year, _visible.month, 1);
    final leading = first.weekday % 7;
    final start = first.subtract(Duration(days: leading));
    final daysInMonth = DateTime(_visible.year, _visible.month + 1, 0).day;
    final total = ((leading + daysInMonth + 6) ~/ 7) * 7;
    return [for (var i = 0; i < total; i++) start.add(Duration(days: i))];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            _header(),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                children: [
                  _vendorCard(),
                  const SizedBox(height: 22),
                  const Text(
                    'Choose Your Date',
                    style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.1),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Select an available date to see time slots',
                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, fontWeight: FontWeight.w500),
                  ),
                  const SizedBox(height: 16),
                  _calendarCard(),
                  const SizedBox(height: 22),
                  _timesHeader(),
                  const SizedBox(height: 12),
                  _timeGrid(),
                  const SizedBox(height: 16),
                  _packageCard(),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
              child: SizedBox(
                height: 54,
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => BookProjectPage(
                        vendor: vendor,
                        home: widget.home,
                        dateLabel: _dateLabel,
                        time: _time,
                        packageName: _package.name,
                        packageDetails: _package.details,
                      ),
                    ),
                  ),
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
                      Align(
                        alignment: Alignment.centerRight,
                        child: Icon(Icons.chevron_right_rounded, size: 26),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _header() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 4, 8, 4),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 32),
          ),
          const Expanded(child: Center(child: _LensMark())),
          FavoriteHeart(vendor: vendor, size: 22),
          IconButton(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile link copied')));
            },
            icon: const Icon(Icons.ios_share_rounded, color: LensColors.cream),
          ),
        ],
      ),
    );
  }

  Widget _vendorCard() {
    final photo = vendor.profilePhotoUrl ?? VendorPhotos.portrait(vendor.vendorType, vendor.id);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 78,
          height: 78,
          decoration: BoxDecoration(
            color: LensColors.charcoal,
            borderRadius: BorderRadius.circular(18),
          ),
          clipBehavior: Clip.antiAlias,
          child: LensConfig.useNetwork
              ? Image.network(
                  photo,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => _photoFallback(),
                )
              : _photoFallback(),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Flexible(
                    child: Text(
                      vendor.displayName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, height: 1.15),
                    ),
                  ),
                  if (vendor.verified) ...[
                    const SizedBox(width: 4),
                    const Icon(Icons.verified, color: Color(0xFF3B82F6), size: 18),
                  ],
                ],
              ),
              const SizedBox(height: 3),
              Text(
                vendor.vendorTypeName,
                style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 13, fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.location_on_outlined, color: Color(0xFF8E8B84), size: 14),
                  const SizedBox(width: 2),
                  Expanded(
                    child: Text(
                      _location,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5, fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
              if (widget.home.on('reviews')) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 16),
                    const SizedBox(width: 3),
                    Text(
                      '${vendor.ratingAvg.toStringAsFixed(1)}  (${vendor.ratingCount} reviews)',
                      style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12.5, fontWeight: FontWeight.w500),
                    ),
                  ],
                ),
              ],
              const SizedBox(height: 6),
              const Row(
                children: [
                  Icon(Icons.circle, color: Color(0xFF3DDC84), size: 8),
                  SizedBox(width: 6),
                  Text(
                    'Available this weekend',
                    style: TextStyle(color: Color(0xFF4ADE80), fontSize: 12.5, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _photoFallback() {
    return ColoredBox(
      color: LensColors.graphite,
      child: Center(
        child: Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800)),
      ),
    );
  }

  Widget _calendarCard() {
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
      decoration: BoxDecoration(
        color: const Color(0xFF141416),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFF242428)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${_months[_visible.month - 1]} ${_visible.year}',
                  style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800),
                ),
              ),
              _monthBtn(Icons.chevron_left_rounded, () {
                setState(() => _visible = DateTime(_visible.year, _visible.month - 1));
              }),
              const SizedBox(width: 4),
              _monthBtn(Icons.chevron_right_rounded, () {
                setState(() => _visible = DateTime(_visible.year, _visible.month + 1));
              }),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              for (final label in _dow)
                Expanded(
                  child: Text(
                    label,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Color(0xFF6B6B70), fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.4),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 8),
          GridView.count(
            crossAxisCount: 7,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            childAspectRatio: 0.92,
            children: _cells().map(_dayCell).toList(),
          ),
          const SizedBox(height: 8),
          const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _LegendDot(color: LensColors.primary, label: 'Selected'),
              SizedBox(width: 18),
              _LegendDot(color: LensColors.primary, label: 'Available'),
              SizedBox(width: 18),
              _LegendDot(color: Color(0xFF5C5F66), label: 'Unavailable'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _monthBtn(IconData icon, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFF3A3A3E)),
        ),
        child: Icon(icon, color: LensColors.cream, size: 22),
      ),
    );
  }

  Widget _dayCell(DateTime date) {
    final inMonth = date.month == _visible.month;
    final available = inMonth && _isAvailable(date);
    final selected = inMonth && _sameDay(date, _selected);

    Color textColor = const Color(0xFF3A3A3E);
    if (inMonth && selected) {
      textColor = Colors.white;
    } else if (inMonth && available) {
      textColor = const Color(0xFFE8E4DC);
    } else if (inMonth) {
      textColor = const Color(0xFF5C5F66);
    }

    return GestureDetector(
      onTap: available
          ? () => setState(() {
                _selected = date;
              })
          : null,
      child: Center(
        child: Container(
          width: 38,
          height: 38,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: selected
                ? LensColors.primary
                : available
                    ? const Color(0xFF2A1C14)
                    : Colors.transparent,
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                '${date.day}',
                style: TextStyle(color: textColor, fontSize: 13, fontWeight: FontWeight.w700, height: 1),
              ),
              const SizedBox(height: 3),
              if (selected || available)
                Container(
                  width: 4,
                  height: 4,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: selected ? const Color(0xFFFFE0C8) : LensColors.primary,
                  ),
                )
              else
                const SizedBox(height: 4),
            ],
          ),
        ),
      ),
    );
  }

  Widget _timesHeader() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Available Times', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
              SizedBox(height: 4),
              Text(
                'Select a time for your session',
                style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.location_on_outlined, color: Color(0xFF8E8B84), size: 14),
              const SizedBox(width: 4),
              Text(
                '$_location (GMT+2)',
                style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12, fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _timeGrid() {
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 2.45,
      children: [
        for (final slot in _times)
          GestureDetector(
            onTap: () => setState(() => _time = slot),
            child: Container(
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: slot == _time ? LensColors.primary : const Color(0xFF141416),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: slot == _time ? LensColors.primary : const Color(0xFF3A3A3E), width: 1.2),
              ),
              child: Text(
                slot,
                style: TextStyle(
                  color: slot == _time ? const Color(0xFF1A0E08) : const Color(0xFFE8E4DC),
                  fontWeight: FontWeight.w800,
                  fontSize: 13,
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _packageCard() {
    final cover = vendor.coverUrl ?? VendorPhotos.cover(vendor.vendorType, vendor.id);

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF141416),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFF242428)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 54,
                height: 54,
                decoration: BoxDecoration(color: LensColors.graphite, borderRadius: BorderRadius.circular(12)),
                clipBehavior: Clip.antiAlias,
                child: LensConfig.useNetwork
                    ? Image.network(cover, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite))
                    : const ColoredBox(color: LensColors.graphite),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Selected Package', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12, fontWeight: FontWeight.w500)),
                    const SizedBox(height: 2),
                    Text(_package.name, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                    const SizedBox(height: 2),
                    Text(_package.details, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 11.5, height: 1.3)),
                  ],
                ),
              ),
              GestureDetector(
                onTap: _pickPackage,
                child: const Text('Edit', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 14)),
              ),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(height: 1, thickness: 1, color: Color(0xFF2A2A2E)),
          ),
          Row(
            children: [
              Expanded(child: _meta(Icons.calendar_today_outlined, 'Date', _dateLabel)),
              Expanded(child: _meta(Icons.access_time_rounded, 'Time', _time)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _meta(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, color: const Color(0xFF8E8B84), size: 18),
        const SizedBox(width: 8),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
            Text(value, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w700)),
          ],
        ),
      ],
    );
  }

  void _pickPackage() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                for (final item in _packages)
                  ListTile(
                    title: Text(item.name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                    subtitle: Text(item.details, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                    trailing: item.name == _package.name ? const Icon(Icons.check, color: LensColors.primary) : null,
                    onTap: () {
                      setState(() => _package = item);
                      Navigator.pop(context);
                    },
                  ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _BookPackage {
  const _BookPackage(this.name, this.details);

  final String name;
  final String details;
}

class _LegendDot extends StatelessWidget {
  const _LegendDot({required this.color, required this.label});

  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(width: 9, height: 9, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
        const SizedBox(width: 6),
        Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12, fontWeight: FontWeight.w500)),
      ],
    );
  }
}

class _LensMark extends StatelessWidget {
  const _LensMark();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 36,
      child: Stack(
        alignment: Alignment.topCenter,
        clipBehavior: Clip.none,
        children: [
          Positioned(
            top: -2,
            child: CustomPaint(size: const Size(22, 14), painter: _SunburstPainter()),
          ),
          const Padding(
            padding: EdgeInsets.only(top: 8),
            child: Text(
              'lens',
              style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700, letterSpacing: 0.2, height: 1),
            ),
          ),
        ],
      ),
    );
  }
}

class _SunburstPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = LensColors.primary
      ..strokeWidth = 1.5
      ..strokeCap = StrokeCap.round;
    final origin = Offset(size.width / 2, size.height);
    for (var i = 0; i < 7; i++) {
      final angle = -math.pi + (i * math.pi / 6);
      canvas.drawLine(
        origin + Offset(math.cos(angle) * 2, math.sin(angle) * 2),
        origin + Offset(math.cos(angle) * 9, math.sin(angle) * 9),
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
