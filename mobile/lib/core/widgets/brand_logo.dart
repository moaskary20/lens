import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';

class BrandLogo extends StatelessWidget {
  const BrandLogo({
    super.key,
    required this.name,
    required this.logoUrl,
    required this.width,
    required this.height,
    this.fontSize = 30,
    this.color = LensColors.primary,
    this.alignment = Alignment.centerLeft,
  });

  final String name;
  final String? logoUrl;
  final double width;
  final double height;
  final double fontSize;
  final Color color;
  final Alignment alignment;

  @override
  Widget build(BuildContext context) {
    final url = logoUrl;
    if (url == null || url.isEmpty) {
      return _fallback();
    }

    return Image.network(
      url,
      width: width,
      height: height,
      fit: BoxFit.contain,
      alignment: alignment,
      errorBuilder: (context, error, stackTrace) => _fallback(),
    );
  }

  Widget _fallback() => SizedBox(
    width: width,
    height: height,
    child: Align(
      alignment: alignment,
      child: FittedBox(
        fit: BoxFit.scaleDown,
        alignment: alignment,
        child: Text(
          name,
          style: TextStyle(
            color: color,
            fontSize: fontSize,
            fontWeight: FontWeight.w800,
            height: 1,
          ),
        ),
      ),
    ),
  );
}
