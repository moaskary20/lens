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
    barrierColor: const Color(0x66000000),
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

    return Align(
      alignment: Alignment.bottomCenter,
      child: FractionallySizedBox(
        widthFactor: 1,
        heightFactor: 0.86,
        child: Material(
          color: const Color(0xFF111111),
          borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
          clipBehavior: Clip.antiAlias,
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(22, 20, 22, 10),
              child: Column(
                children: [
                  Expanded(
                    child: SingleChildScrollView(
                      child: Column(
                        children: [
                          Container(
                            width: 58,
                            height: 58,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              border: Border.all(color: LensColors.primary, width: 2.2),
                            ),
                            child: const Icon(Icons.check, color: LensColors.primary, size: 28),
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'Approve this delivery?',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800, height: 1.2),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Approval unlocks your original files and releases the protected payment to the creator.',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.4),
                          ),
                          const SizedBox(height: 20),
                          Row(
                            children: [
                              CircleAvatar(
                                radius: 22,
                                backgroundColor: LensColors.graphite,
                                backgroundImage: LensConfig.useNetwork && vendorPhoto != null && vendorPhoto!.isNotEmpty ? NetworkImage(vendorPhoto!) : null,
                                child: LensConfig.useNetwork && vendorPhoto != null && vendorPhoto!.isNotEmpty
                                    ? null
                                    : Text(name.substring(0, 1).toUpperCase(), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                                    if (role.isNotEmpty) Text(role, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          _row(Icons.description_outlined, 'Booking', project),
                          _row(Icons.credit_card_outlined, 'Total payment', _amount),
                          const SizedBox(height: 4),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
                            decoration: BoxDecoration(color: const Color(0xFF181818), borderRadius: BorderRadius.circular(16)),
                            child: const Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Icon(Icons.info_outline, color: Color(0xFF8E8B84), size: 18),
                                SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    'This action cannot be undone.\nOnce you approve, the files will be permanently unlocked and the payment will be released to the creator.',
                                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, height: 1.35),
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
                          Align(
                            alignment: Alignment.centerRight,
                            child: Icon(Icons.chevron_right_rounded, size: 24),
                          ),
                        ],
                      ),
                    ),
                  ),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      style: TextButton.styleFrom(foregroundColor: LensColors.primary),
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

  Widget _row(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, color: const Color(0xFF8E8B84), size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 15)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
