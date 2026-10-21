import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';

class BrandLogo extends StatelessWidget {
  const BrandLogo({
    super.key,
    this.name = 'Lens',
    this.logoUrl,
    this.width = 132,
    this.height = 36,
    this.fontSize = 30,
    this.color = LensColors.primary,
    this.alignment = Alignment.centerLeft,
  });

  static const assetPath = 'lib/assits/lenslogo_mark.png';

  final String name;
  final String? logoUrl;
  final double width;
  final double height;
  final double fontSize;
  final Color color;
  final Alignment alignment;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      assetPath,
      width: width,
      height: height,
      fit: BoxFit.contain,
      alignment: alignment,
      semanticLabel: name,
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
            decoration: TextDecoration.none,
          ),
        ),
      ),
    ),
  );
}

class BrandHeader extends StatelessWidget {
  const BrandHeader({
    super.key,
    this.height = 34,
  });

  final double height;

  @override
  Widget build(BuildContext context) {
    return BrandLogo(width: height * 3.6, height: height);
  }
}
