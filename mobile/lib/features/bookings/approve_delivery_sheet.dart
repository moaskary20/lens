import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/theme/lens_colors.dart';

Future<bool> showApproveDeliverySheet(
  BuildContext context, {
  required String vendorName,
  required String vendorRole,
  required String projectName,
  required double total,
  String? vendorPhoto,
}) async {
  final result = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    barrierColor: const Color(0x99000000),
    builder: (sheetContext) => ApproveDeliverySheet(
      vendorName: vendorName,
      vendorRole: vendorRole,
      vendorPhoto: vendorPhoto,
      projectName: projectName,
      total: total,
    ),
  );
  return result ?? false;
}

class ApproveDeliverySheet extends StatelessWidget {
  const ApproveDeliverySheet({
    super.key,
    required this.vendorName,
    required this.vendorRole,
    required this.projectName,
    required this.total,
    this.vendorPhoto,
  });

  final String vendorName;
  final String vendorRole;
  final String projectName;
  final double total;
  final String? vendorPhoto;

  static const _sheet = Color(0xFF101318);
  static const _card = Color(0xFF161A20);
  static const _stroke = Color(0xFF2C3138);
  static const _muted = Color(0xFF9AA0A8);
  static const _icon = Color(0xFFC5C9CF);

  String get _amount {
    final digits = total.round().abs().toString();
    final buffer = StringBuffer();
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && (digits.length - i) % 3 == 0) {
        buffer.write(',');
      }
      buffer.write(digits[i]);
    }
    return 'EGP $buffer';
  }

  @override
  Widget build(BuildContext context) {
    final name = vendorName.trim().isEmpty ? 'Creator' : vendorName;
    final role = vendorRole.trim();
    final project = projectName.trim().isEmpty ? 'Session' : projectName;
    final photo = vendorPhoto;

    return Align(
      alignment: Alignment.bottomCenter,
      child: FractionallySizedBox(
        widthFactor: 1,
        heightFactor: 0.82,
        child: Material(
          color: _sheet,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
          clipBehavior: Clip.antiAlias,
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 10, 20, 12),
              child: Column(
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(color: const Color(0xFF3A4048), borderRadius: BorderRadius.circular(99)),
                  ),
                  const SizedBox(height: 18),
                  Expanded(
                    child: SingleChildScrollView(
                      child: Column(
                        children: [
                          Container(
                            width: 56,
                            height: 56,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: const Color(0xFF1A120E),
                              border: Border.all(color: LensColors.primary, width: 2.4),
                            ),
                            child: const Icon(Icons.check_rounded, color: LensColors.primary, size: 28),
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'Approve this delivery?',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800, height: 1.15),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Approval unlocks your original files and releases the protected payment to the creator.',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: _muted, fontSize: 14, height: 1.4),
                          ),
                          const SizedBox(height: 18),
                          _cardBox(
                            child: Column(
                              children: [
                                Row(
                                  children: [
                                    CircleAvatar(
                                      radius: 22,
                                      backgroundColor: LensColors.graphite,
                                      backgroundImage: LensConfig.useNetwork && photo != null && photo.isNotEmpty ? NetworkImage(photo) : null,
                                      child: LensConfig.useNetwork && photo != null && photo.isNotEmpty
                                          ? null
                                          : Text(name.substring(0, 1).toUpperCase(), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                                          if (role.isNotEmpty) Text(role, style: const TextStyle(color: _muted, fontSize: 13)),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                                const Padding(
                                  padding: EdgeInsets.symmetric(vertical: 12),
                                  child: Divider(height: 1, color: _stroke),
                                ),
                                _meta(Icons.description_outlined, 'Booking', project),
                                const Padding(
                                  padding: EdgeInsets.symmetric(vertical: 12),
                                  child: Divider(height: 1, color: _stroke),
                                ),
                                _meta(Icons.credit_card_outlined, 'Total payment', _amount),
                              ],
                            ),
                          ),
                          const SizedBox(height: 12),
                          _cardBox(
                            child: const Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Icon(Icons.info_outline, color: _icon, size: 18),
                                SizedBox(width: 10),
                                Expanded(
                                  child: Text.rich(
                                    TextSpan(
                                      children: [
                                        TextSpan(
                                          text: 'This action cannot be undone.\n',
                                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 13, height: 1.35),
                                        ),
                                        TextSpan(
                                          text: 'Once you approve, the files will be permanently unlocked and the payment will be released to the creator.',
                                          style: TextStyle(color: _muted, fontSize: 13, height: 1.35),
                                        ),
                                      ],
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
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    height: 54,
                    child: FilledButton(
                      onPressed: () => Navigator.pop(context, true),
                      style: FilledButton.styleFrom(
                        backgroundColor: LensColors.primary,
                        foregroundColor: Colors.white,
                        shape: const StadiumBorder(),
                        padding: const EdgeInsets.symmetric(horizontal: 22),
                      ),
                      child: const Stack(
                        alignment: Alignment.center,
                        children: [
                          Text('Approve & Release Funds', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                          Align(alignment: Alignment.centerRight, child: Icon(Icons.chevron_right_rounded, size: 24)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: OutlinedButton(
                      onPressed: () => Navigator.pop(context, false),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: LensColors.primary,
                        side: const BorderSide(color: LensColors.primary, width: 1.5),
                        shape: const StadiumBorder(),
                      ),
                      child: const Text('Keep Reviewing', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _cardBox({required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
      decoration: BoxDecoration(
        color: _card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: _stroke),
      ),
      child: child,
    );
  }

  Widget _meta(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, color: _icon, size: 22),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(color: _muted, fontSize: 13)),
              Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 15)),
            ],
          ),
        ),
      ],
    );
  }
}
