import 'package:flutter/material.dart';

class BookMoodColor {
  const BookMoodColor({required this.name, required this.hex});

  final String name;
  final String hex;

  Color get color {
    final raw = hex.replaceFirst('#', '');
    if (raw.length == 6) {
      return Color(int.parse('FF$raw', radix: 16));
    }
    return const Color(0xFFFF5A1F);
  }

  Map<String, String> toJson() => {'name': name, 'hex': hex};
}

class BookMoodboard {
  BookMoodboard({
    required this.title,
    required this.style,
    required this.mood,
    required this.visualDirection,
    required this.colors,
    required this.shotList,
    required this.script,
    required this.musicTone,
    List<String>? images,
    this.source = 'generated',
    this.attached = false,
  }) : images = images ?? <String>[];

  factory BookMoodboard.fromTemplate(String brief) {
    final food = brief.toLowerCase().contains('food') || brief.contains('طعام');
    if (food) {
      return BookMoodboard(
        title: 'Commercial food story',
        style: 'Warm commercial food photography, shallow depth, natural window light plus fill',
        mood: 'Appetizing, fresh, inviting',
        visualDirection:
            'Hero plate first, steam and texture close-ups, hands plating, environment of a working kitchen.',
        colors: const [
          BookMoodColor(name: 'Paprika', hex: '#FF5A1F'),
          BookMoodColor(name: 'Cream', hex: '#F2EFE9'),
          BookMoodColor(name: 'Charcoal', hex: '#0D0D0F'),
          BookMoodColor(name: 'Olive', hex: '#3D8B5F'),
          BookMoodColor(name: 'Gold', hex: '#F79646'),
        ],
        shotList: const [
          'Hero dish 50mm',
          'Texture macro',
          'Hands plating',
          'Kitchen environment',
          'Steam / pour insert',
          'Final table spread',
        ],
        script:
            'Open on the empty plate. Hands enter with the hero dish. Cut to steam and texture. Pull back to the kitchen. End on the styled table, ready to serve.',
        musicTone: 'Soft acoustic, warm percussion',
        source: 'generated',
      );
    }
    return BookMoodboard(
      title: 'Project visual direction',
      style: 'Clean editorial, natural tones, confident subject',
      mood: 'Cinematic and approachable',
      visualDirection: 'Wide establishing frame, mid portraits, detail inserts, and a closing hero image.',
      colors: const [
        BookMoodColor(name: 'Lens orange', hex: '#FF5A1F'),
        BookMoodColor(name: 'Graphite', hex: '#2A2D34'),
        BookMoodColor(name: 'Cream', hex: '#F2EFE9'),
        BookMoodColor(name: 'Slate', hex: '#5C5F66'),
        BookMoodColor(name: 'Ink', hex: '#0D0D0F'),
      ],
      shotList: const [
        'Establishing wide',
        'Subject mid-shot',
        'Detail insert',
        'Movement / candid',
        'Hero portrait',
        'Closing frame',
      ],
      script:
          'Open wide on the space. Move in on the subject. Capture one honest candid. Close on the hero portrait held for two beats.',
      musicTone: 'Minimal piano with a light pulse',
      source: 'generated',
    );
  }

  String title;
  String style;
  String mood;
  String visualDirection;
  List<BookMoodColor> colors;
  List<String> shotList;
  String script;
  String musicTone;
  List<String> images;
  String source;
  bool attached;

  bool get isAttached => attached && (images.isNotEmpty || title.isNotEmpty);
  bool get hasImages => images.isNotEmpty;
  bool get hasDirection => title.isNotEmpty && visualDirection.isNotEmpty;

  String get summaryLine {
    if (hasDirection && hasImages) {
      return '$title · ${images.length} refs';
    }
    if (hasImages) {
      return '${images.length} moodboard image${images.length == 1 ? '' : 's'}';
    }
    return title.isEmpty ? 'Moodboard ready' : title;
  }

  String toNotesBlock() {
    final lines = <String>[
      '— Lens AI Moodboard —',
      if (title.isNotEmpty) 'Title: $title',
      if (mood.isNotEmpty) 'Mood: $mood',
      if (style.isNotEmpty) 'Style: $style',
      if (visualDirection.isNotEmpty) 'Direction: $visualDirection',
      if (colors.isNotEmpty) 'Palette: ${colors.map((c) => '${c.name} (${c.hex})').join(', ')}',
      if (shotList.isNotEmpty) 'Shots: ${shotList.join(' · ')}',
      if (musicTone.isNotEmpty) 'Music: $musicTone',
      if (script.isNotEmpty) 'Script: $script',
      if (images.isNotEmpty) 'Reference images attached: ${images.length}',
    ];
    return lines.join('\n');
  }

  BookMoodboard copy() {
    return BookMoodboard(
      title: title,
      style: style,
      mood: mood,
      visualDirection: visualDirection,
      colors: List<BookMoodColor>.from(colors),
      shotList: List<String>.from(shotList),
      script: script,
      musicTone: musicTone,
      images: List<String>.from(images),
      source: source,
      attached: attached,
    );
  }
}
