import 'package:lens/core/vendor_photos.dart';

class DemoBootstrap {
  const DemoBootstrap._();

  static Map<String, dynamic> payload() {
    return {
      'name': 'Lens',
      'tagline': 'Find. Book. Create.',
      'ai_prompt': 'What will you create today?',
      'ai_helper': 'Describe your idea and let AI find the right creatives for you.',
      'search_placeholder': 'Search photographers, studios, models...',
      'unread_notifications': 3,
      'features': {
        'ai_assistant': true,
        'bookings': true,
        'notifications': true,
        'favorites': true,
        'filters': true,
        'badges': true,
        'reviews': true,
      },
      'vendor_types': const [
        {'slug': 'photographer', 'label': 'Photographers'},
        {'slug': 'videographer', 'label': 'Videographers'},
        {'slug': 'reels', 'label': 'Reels Creators'},
        {'slug': 'model', 'label': 'Models'},
        {'slug': 'studio', 'label': 'Studios'},
        {'slug': 'ugc', 'label': 'UGC Creators'},
        {'slug': 'food_stylist', 'label': 'Food Stylists'},
      ],
      'cities': const [
        {'id': 1, 'name': 'Cairo'},
        {'id': 2, 'name': 'Giza'},
        {'id': 3, 'name': 'Alexandria'},
      ],
      'banks': const [
        {'id': 'Banque Misr', 'label': 'Banque Misr'},
        {'id': 'CIB', 'label': 'Commercial International Bank (CIB)'},
      ],
      'telecom_wallets': const [
        {'id': 'vodafone', 'label': 'Vodafone Cash'},
        {'id': 'orange', 'label': 'Orange Cash'},
        {'id': 'etisalat', 'label': 'e& cash (Etisalat)'},
        {'id': 'we', 'label': 'WE Pay'},
      ],
      'popular': [
        {
          'slug': 'photographer',
          'title': 'Popular Photographer',
          'vendors': [
            _vendor(1, 'Fahad Studio Light', 'photographer', 'Photographer', 'FS', 4.9, 37, ['F&B', 'Events', 'Product']),
            _vendor(2, 'Cairo Frame Co.', 'photographer', 'Photographer', 'CF', 4.8, 24, ['Portrait', 'Fashion']),
            _vendor(11, 'Ahmed Khaled', 'photographer', 'Photographer', 'AK', 4.9, 128, ['Portrait', 'Lifestyle'], price: 2500),
          ],
        },
        {
          'slug': 'videographer',
          'title': 'AI Videos & Motion',
          'vendors': [
            _vendor(3, 'Nile Motion', 'videographer', 'Videographer', 'NM', 4.7, 19, ['Reels', 'Ads']),
            _vendor(12, 'Sara Hassan', 'videographer', 'Videographer', 'SH', 4.8, 96, ['Film', 'Commercial'], price: 3000),
          ],
        },
        {
          'slug': 'reels',
          'title': 'Reels Creators',
          'vendors': [
            _vendor(13, 'Omar Nabil', 'reels', 'Reels Creator', 'ON', 4.7, 84, ['Reels', 'Social'], price: 1500),
          ],
        },
        {
          'slug': 'studio',
          'title': 'Featured Studios',
          'vendors': [
            _vendor(
              15,
              'Frame Studio',
              'studio',
              'Studio',
              'FR',
              4.9,
              320,
              ['Photo', 'Cyclorama', 'Equipment'],
              city: 'Cairo',
              location: 'Zamalek, Cairo',
              latitude: 30.062,
              longitude: 31.219,
              price: 600,
            ),
            _vendor(
              4,
              'Nile Loft Studio',
              'studio',
              'Studio',
              'NL',
              4.8,
              215,
              ['Lifestyle', 'Photo', 'Video'],
              city: 'Cairo',
              location: 'Maadi, Cairo',
              latitude: 29.960,
              longitude: 31.257,
              price: 500,
            ),
            _vendor(
              20,
              'Studio 77',
              'studio',
              'Studio',
              'S7',
              4.7,
              180,
              ['Photo', 'Green Screen', 'Equipment'],
              city: 'Cairo',
              location: '6th of October',
              latitude: 29.972,
              longitude: 30.943,
              price: 700,
            ),
            _vendor(
              16,
              'Northlight Studio',
              'studio',
              'Studio',
              'NS',
              4.6,
              142,
              ['Natural Light', 'Photo', 'Portrait'],
              city: 'Cairo',
              location: 'New Cairo',
              latitude: 30.007,
              longitude: 31.437,
              price: 450,
            ),
            _vendor(
              14,
              'Aspect Studio',
              'studio',
              'Studio',
              'AS',
              4.9,
              101,
              ['Daylight', 'Photo'],
              city: 'Cairo',
              location: 'Zamalek, Cairo',
              latitude: 30.068,
              longitude: 31.224,
              price: 550,
            ),
            _vendor(
              21,
              'Pixel Studio',
              'studio',
              'Studio',
              'PX',
              4.5,
              88,
              ['Photo', 'Video'],
              city: 'Cairo',
              location: 'Heliopolis',
              latitude: 30.091,
              longitude: 31.324,
              price: 480,
            ),
          ],
        },
        {
          'slug': 'model',
          'title': 'Popular Models',
          'vendors': [
            _vendor(5, 'Laila Cast', 'model', 'Model', 'LC', 4.9, 28, ['Fashion', 'Commercial'], filterTags: ['model-female', 'fashion', 'commercial', 'city-cairo']),
            _vendor(17, 'Maya Adel', 'model', 'Model', 'MA', 5.0, 72, ['Fashion'], price: 2000, filterTags: ['model-female', 'fashion', 'city-cairo']),
            _vendor(31, 'Adam Runway', 'model', 'Model', 'AR', 4.7, 20, ['Fashion', 'Commercial'], city: 'Alexandria', price: 2300, filterTags: ['model-male', 'fashion', 'commercial', 'city-alexandria']),
          ],
        },
        {
          'slug': 'ugc',
          'title': 'UGC Creators',
          'vendors': [
            _vendor(18, 'Youssef Ramy', 'ugc', 'UGC Creator', 'YR', 4.8, 91, ['Lifestyle', 'Social'], price: 1200),
          ],
        },
        {
          'slug': 'food_stylist',
          'title': 'Food Stylists',
          'vendors': [
            _vendor(19, 'Karim Atef', 'food_stylist', 'Food Stylist', 'KA', 4.9, 60, ['Food', 'Product'], price: 2500),
          ],
        },
      ],
    };
  }

  static Map<String, dynamic> _vendor(
    int id,
    String name,
    String type,
    String typeName,
    String initials,
    double rating,
    int reviews,
    List<String> tags, {
    String city = 'Cairo',
    String? location,
    double? latitude,
    double? longitude,
    double price = 2500,
    List<String> filterTags = const [],
  }) {
    return {
      'id': id,
      'display_name': name,
      'vendor_type': type,
      'vendor_type_name': typeName,
      'city': city,
      'location': location ?? '$city, Egypt',
      'rating_avg': rating,
      'rating_count': reviews,
      'badges': ['top-rated', 'verified'],
      'initials': initials,
      'tags': tags,
      'filter_tags': filterTags,
      'starting_from': price,
      'verified': true,
      'cover_url': VendorPhotos.cover(type, id),
      'profile_photo_url': VendorPhotos.portrait(type, id),
      'latitude': latitude ?? _spread(city == 'Alexandria' ? 31.2001 : 30.0444, id, 0.04),
      'longitude': longitude ?? _spread(city == 'Alexandria' ? 29.9187 : 31.2357, id + 5, 0.05),
    };
  }

  static double _spread(double start, int seed, double spread) {
    return start + (((seed % 9) - 4) * (spread / 4));
  }
}
