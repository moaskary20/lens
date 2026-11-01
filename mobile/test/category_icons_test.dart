import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:lens/features/home/category_catalog.dart';

void main() {
  test('category icons stay indicative for voice, ai motion, and bloggers', () {
    expect(
      CategoryCatalog.iconFor('voive', null, 'voice over'),
      Icons.record_voice_over_rounded,
    );
    expect(
      CategoryCatalog.iconFor('voice'),
      Icons.record_voice_over_rounded,
    );
    expect(
      CategoryCatalog.iconFor('Ai & Motion videos'),
      Icons.auto_awesome_motion,
    );
    expect(
      CategoryCatalog.iconFor('ai_motion'),
      Icons.auto_awesome_motion,
    );
    expect(
      CategoryCatalog.iconFor('bloggers and influencers'),
      Icons.campaign_outlined,
    );
    expect(
      CategoryCatalog.iconFor('bloggers'),
      Icons.campaign_outlined,
    );
  });
}
