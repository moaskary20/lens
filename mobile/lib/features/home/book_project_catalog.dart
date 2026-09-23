import 'package:lens/core/models/home_data.dart';
import 'package:lens/features/home/book_draft.dart';

class BookProjectField {
  const BookProjectField({
    required this.key,
    required this.label,
    required this.options,
    this.required = true,
  });

  final String key;
  final String label;
  final List<String> options;
  final bool required;
}

class BookProjectCatalog {
  const BookProjectCatalog._();

  static String typeLabel(String vendorType) {
    return switch (vendorType) {
      'photographer' => 'Shoot type',
      'videographer' => 'Video type',
      'reels' => 'Platform',
      'studio' => 'Studio use',
      'model' => 'Campaign type',
      'ugc' => 'Content niche',
      'food_stylist' => 'Styling expertise',
      _ => 'Project type',
    };
  }

  static List<String> typeOptions(String vendorType) {
    return switch (vendorType) {
      'photographer' => const ['Wedding', 'Product', 'Editorial', 'Corporate', 'Food & drinks', 'Event', 'Personal'],
      'videographer' => const ['Wedding film', 'Brand film', 'Event recap', 'Documentary', 'Social ad'],
      'reels' => const ['Instagram Reels', 'TikTok', 'Snapchat', 'YouTube Shorts'],
      'studio' => const ['Photo session', 'Video shoot', 'Podcast', 'Event / launch', 'Cyclorama'],
      'model' => const ['Fashion', 'Commercial', 'Lifestyle', 'Fitness', 'Acting'],
      'ugc' => const ['Beauty', 'Fashion', 'Food & beverages', 'Travel', 'Lifestyle', 'Unboxing'],
      'food_stylist' => const ['Recipe development', 'Food styling', 'Food photography', 'Props & surfaces'],
      _ => const ['Brand Campaign', 'Product', 'Editorial', 'Social Content', 'Event', 'Personal'],
    };
  }

  static List<BookProjectField> extraFields(String vendorType) {
    return switch (vendorType) {
      'photographer' => const [
        BookProjectField(key: 'style', label: 'Visual style', options: ['Natural light', 'Studio strobes', 'Lifestyle', 'Editorial dark']),
        BookProjectField(key: 'deliverables', label: 'Deliverables', options: ['25 edited photos', '50 edited photos', 'Full gallery', 'Same-day preview']),
      ],
      'videographer' => const [
        BookProjectField(key: 'runtime', label: 'Runtime', options: ['30–60 seconds', '1–3 minutes', '3–8 minutes', 'Full recap']),
        BookProjectField(key: 'delivery_format', label: 'Delivery format', options: ['4K horizontal', 'Vertical 9:16', 'ProRes', 'Social cutdowns']),
      ],
      'reels' => const [
        BookProjectField(key: 'length', label: 'Clip length', options: ['15 seconds', '30 seconds', '60 seconds', 'Series of 3']),
        BookProjectField(key: 'concept', label: 'Concept', options: ['Talking head', 'B-roll story', 'Product demo', 'Trend audio']),
      ],
      'studio' => const [
        BookProjectField(key: 'set', label: 'Set / room', options: ['Cyclorama', 'Kitchen', 'Bedroom', 'Podcast room', 'Industrial brick']),
        BookProjectField(key: 'hours', label: 'Hours needed', options: ['2 hours', '4 hours', '6 hours', 'Full day']),
      ],
      'model' => const [
        BookProjectField(key: 'look', label: 'Look', options: ['Commercial clean', 'Fashion editorial', 'Casual lifestyle', 'Fitness']),
        BookProjectField(key: 'wardrobe', label: 'Wardrobe', options: ['Talent brings looks', 'Client provides', 'Mixed'], required: false),
      ],
      'ugc' => const [
        BookProjectField(key: 'platforms', label: 'Posting platforms', options: ['TikTok', 'Instagram', 'Both', 'Client will post']),
        BookProjectField(key: 'content_type', label: 'Content type', options: ['Talking review', 'Unboxing', 'Lifestyle usage', 'Recipe / how-to']),
      ],
      'food_stylist' => const [
        BookProjectField(key: 'cuisine', label: 'Cuisine / food type', options: ['Egyptian', 'Levantine', 'Pastry', 'Beverages', 'Mixed menu']),
        BookProjectField(key: 'dishes', label: 'Number of dishes', options: ['1–3 dishes', '4–6 dishes', 'Full menu']),
      ],
      _ => const [],
    };
  }

  static List<BookPriceOption> pricesFor(VendorCard vendor, {List<BookPriceOption> remote = const []}) {
    if (remote.isNotEmpty) {
      return remote;
    }
    final start = vendor.startingFrom ?? 1800;
    return switch (vendor.vendorType) {
      'studio' => [
        BookPriceOption(key: 'hourly', label: 'Price per hour per location', price: start, durationHours: 1),
        BookPriceOption(key: 'hourly_4', label: '4-hour studio block', price: start * 4, durationHours: 4),
      ],
      'ugc' || 'reels' => [
        BookPriceOption(key: 'per_video', label: 'Price per video', price: start, durationHours: 2),
        BookPriceOption(key: 'per_video_3', label: '3-video pack', price: start * 2.6),
      ],
      _ => [
        BookPriceOption(key: 'half_day', label: 'Half-day price (6 hours)', price: start, durationHours: 6),
        BookPriceOption(key: 'full_day', label: 'Full-day price (12 hours)', price: (start * 1.7).roundToDouble(), durationHours: 12),
      ],
    };
  }
}
