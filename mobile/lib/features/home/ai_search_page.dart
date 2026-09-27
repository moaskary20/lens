import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/vendor_profile_page.dart';
import 'package:lens/features/shell/app_shell.dart';

void openAiSearch(BuildContext context, HomeData home) {
  Navigator.of(context).push(
    PageRouteBuilder<void>(
      transitionDuration: const Duration(milliseconds: 560),
      reverseTransitionDuration: const Duration(milliseconds: 340),
      pageBuilder: (context, animation, secondaryAnimation) => AiSearchPage(home: home),
      transitionsBuilder: (context, animation, secondaryAnimation, child) {
        final curved = CurvedAnimation(parent: animation, curve: Curves.easeOutCubic);
        return FadeTransition(
          opacity: curved,
          child: SlideTransition(
            position: Tween<Offset>(begin: const Offset(0, 0.06), end: Offset.zero).animate(curved),
            child: ScaleTransition(scale: Tween<double>(begin: 0.96, end: 1).animate(curved), child: child),
          ),
        );
      },
    ),
  );
}

class AiSearchPage extends StatefulWidget {
  const AiSearchPage({super.key, required this.home});

  final HomeData home;

  @override
  State<AiSearchPage> createState() => _AiSearchPageState();
}

class _AiSearchPageState extends State<AiSearchPage> with SingleTickerProviderStateMixin {
  final _api = ApiClient();
  final _input = TextEditingController();
  final _scroll = ScrollController();
  late final AnimationController _enter;
  final List<_ChatLine> _lines = [];
  String? _conversationId;
  String? _ask;
  String _status = 'gathering';
  String _provider = 'lexicon';
  Map<String, dynamic> _slots = {};
  List<VendorCard> _vendors = [];
  List<Map<String, dynamic>> _comparison = [];
  Map<String, dynamic>? _pick;
  List<Map<String, dynamic>> _addOns = [];
  bool _busy = false;

  HomeData get home => widget.home;
  bool get _live => LensConfig.useNetwork && home.on('ai_assistant');

  @override
  void initState() {
    super.initState();
    _enter = AnimationController(vsync: this, duration: const Duration(milliseconds: 900))..forward();
    _lines.add(_ChatLine(
      role: 'assistant',
      body: 'I ask location, then date, then budget — the same Lens assistant from the admin search engine. Then I compare real creators and help you pick.',
    ));
  }

  @override
  void dispose() {
    _enter.dispose();
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _send([String? preset]) async {
    final text = (preset ?? _input.text).trim();
    if (text.isEmpty || _busy) {
      return;
    }
    _input.clear();
    setState(() {
      _lines.add(_ChatLine(role: 'user', body: text));
      _busy = true;
    });
    _jump();
    try {
      final turn = _live ? await _liveTurn(text) : _demoTurn(text);
      if (!mounted) {
        return;
      }
      setState(() {
        _conversationId = turn.conversationId ?? _conversationId;
        _ask = turn.ask;
        _status = turn.status;
        _provider = turn.provider;
        _slots = turn.slots;
        _vendors = turn.vendors;
        _comparison = turn.comparison;
        _pick = turn.pick;
        _addOns = turn.addOns;
        _lines.add(_ChatLine(role: 'assistant', body: turn.reply));
        _busy = false;
      });
      _jump();
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _busy = false;
        _lines.add(_ChatLine(role: 'assistant', body: error.toString().replaceFirst('ApiException: ', '')));
      });
    }
  }

  Future<_Turn> _liveTurn(String message) async {
    final payload = await _api.postJson('/search/assistant/chat', {
      'message': message,
      'locale': SessionStore.instance.locale,
      if (_conversationId != null) 'conversation_id': _conversationId,
    });
    return _Turn.fromJson(payload, home);
  }

  _Turn _demoTurn(String message) {
    final slots = _mergeDemoSlots(_slots, message, home);
    final ask = _nextAsk(slots);
    if (ask != null) {
      return _Turn(
        conversationId: 'demo',
        provider: 'lexicon',
        status: 'gathering',
        ask: ask,
        slots: slots,
        reply: _question(ask, SessionStore.instance.locale),
      );
    }
    final type = ((slots['vendor_type_slugs'] as List?)?.cast<String>() ?? const ['photographer']).first;
    final vendors = home.popular.where((section) => section.slug == type).expand((section) => section.vendors).toList();
    final pool = vendors.isNotEmpty ? vendors : home.popular.expand((section) => section.vendors).toList();
    final comparison = [
      for (var i = 0; i < pool.take(3).length; i++)
        {
          'rank': i + 1,
          'id': pool[i].id,
          'display_name': pool[i].displayName,
          'city': pool[i].city,
          'rating_avg': pool[i].ratingAvg,
          'price': pool[i].startingFrom,
          'currency': 'EGP',
          'reasons': ['Matches your brief', 'Strong rating'],
        },
    ];
    final pick = pool.isEmpty
        ? null
        : {
            'id': pool.first.id,
            'display_name': pool.first.displayName,
            'why': '${pool.first.displayName} is the best fit: ${pool.first.ratingAvg} stars in ${pool.first.city}.',
          };
    final locale = SessionStore.instance.locale;
    final reply = pool.isEmpty
        ? (locale == 'ar' ? 'ما لقيتش مقدم مناسب. نقدر نوسّع البحث.' : 'I could not find a matching vendor. We can widen the search.')
        : (locale == 'ar'
            ? 'دي مقارنة سريعة. ترشيحي: ${pick?['why']}'
            : 'Here is a quick comparison. My pick: ${pick?['why']} Open their profile and book when you are ready.');
    return _Turn(
      conversationId: 'demo',
      provider: 'lexicon',
      status: 'ready',
      slots: slots,
      vendors: pool.take(3).toList(),
      comparison: comparison,
      pick: pick,
      addOns: _demoAddOns(slots),
      reply: reply,
    );
  }

  void _reset() {
    setState(() {
      _conversationId = null;
      _ask = null;
      _status = 'gathering';
      _slots = {};
      _vendors = [];
      _comparison = [];
      _pick = null;
      _addOns = [];
      _lines
        ..clear()
        ..add(const _ChatLine(
          role: 'assistant',
          body: 'New brief. Tell me what you want to create.',
        ));
    });
  }

  void _jump() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) {
        return;
      }
      _scroll.animateTo(_scroll.position.maxScrollExtent + 80, duration: const Duration(milliseconds: 280), curve: Curves.easeOut);
    });
  }

  Future<void> _voice() async {
    final phrase = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: const Color(0xFF141210),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(26))),
      builder: (context) => _VoiceSheet(prompt: home.aiPrompt),
    );
    if (phrase != null) {
      await _send(phrase);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: AppShell.navFab(context),
      bottomNavigationBar: AppShell.navBar(context, index: AppShell.searchIndex),
      body: Stack(
        children: [
          const _Glow(),
          SafeArea(
            child: Column(
              children: [
                _bar(),
                _stepper(),
                Expanded(
                  child: ListView(
                    controller: _scroll,
                    padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
                    children: [
                      _Reveal(animation: _enter, start: 0, child: _hero()),
                      const SizedBox(height: 16),
                      for (var i = 0; i < _lines.length; i++) _bubble(_lines[i]),
                      if (_busy) const Padding(padding: EdgeInsets.only(top: 8), child: LinearProgressIndicator(color: LensColors.primary, minHeight: 2)),
                      if (_status == 'ready') ...[
                        const SizedBox(height: 16),
                        if (_pick != null) _pickCard(),
                        if (_comparison.isNotEmpty) _compareCard(),
                        if (_vendors.isNotEmpty) _vendorList(),
                        if (_addOns.isNotEmpty) _addOnCard(),
                        if (home.moodboardOn) _moodboardCard(),
                      ],
                    ],
                  ),
                ),
                if (_ask != null) _quickReplies(),
                _composer(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _bar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 2, 8, 0),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
          ),
          const Expanded(
            child: Text('AI Search', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(color: const Color(0xFF24140E), borderRadius: BorderRadius.circular(99), border: Border.all(color: const Color(0x55FF5A1F))),
            child: Text(_provider.toUpperCase(), style: const TextStyle(color: LensColors.primary, fontSize: 10, fontWeight: FontWeight.w800)),
          ),
          IconButton(
            tooltip: 'New chat',
            onPressed: _reset,
            icon: const Icon(Icons.refresh_rounded, color: LensColors.cream),
          ),
        ],
      ),
    );
  }

  Widget _stepper() {
    const steps = ['location', 'date', 'budget'];
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 8),
      child: Row(
        children: [
          for (var i = 0; i < steps.length; i++) ...[
            if (i > 0) Expanded(child: Container(height: 2, color: _slotFilled(steps[i - 1]) ? LensColors.primary : const Color(0xFF2A2A2E))),
            _dot(steps[i]),
          ],
        ],
      ),
    );
  }

  Widget _dot(String slot) {
    final on = _slotFilled(slot);
    final current = _ask == slot;
    return Column(
      children: [
        AnimatedContainer(
          duration: const Duration(milliseconds: 240),
          width: current ? 16 : 10,
          height: current ? 16 : 10,
          decoration: BoxDecoration(
            color: on || current ? LensColors.primary : const Color(0xFF2A2A2E),
            shape: BoxShape.circle,
            boxShadow: current ? const [BoxShadow(color: Color(0x66FF5A1F), blurRadius: 10)] : null,
          ),
        ),
        const SizedBox(height: 4),
        Text(slot[0].toUpperCase() + slot.substring(1), style: TextStyle(color: on || current ? LensColors.primary : const Color(0xFF8E8B84), fontSize: 10, fontWeight: FontWeight.w700)),
      ],
    );
  }

  bool _slotFilled(String slot) {
    return switch (slot) {
      'location' => _slots['governorate'] != null || _slots['city_id'] != null,
      'date' => _slots['available_on'] != null || _slots['relative_date'] != null,
      _ => _slots['budget_band'] != null || _slots['min_price'] != null || _slots['max_price'] != null,
    };
  }

  Widget _hero() {
    return Container(
      padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFF3A1A10), Color(0xFF161210)]),
        border: Border.all(color: const Color(0x66FF5A1F)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.auto_awesome, color: LensColors.warning, size: 18),
              SizedBox(width: 6),
              Text('LENS ASSISTANT', style: TextStyle(color: LensColors.primary, fontSize: 11, letterSpacing: 1.3, fontWeight: FontWeight.w800)),
            ],
          ),
          const SizedBox(height: 8),
          Text(home.aiPrompt, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w800, height: 1.15)),
          const SizedBox(height: 6),
          Text(home.aiHelper, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 13, height: 1.35)),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _tag('Chat'),
              if (home.voiceInput) _tag('Voice'),
              _tag('Compare'),
              if (home.moodboardOn) _tag(home.moodboardLocked ? 'Moodboard after pay' : 'Moodboard'),
            ],
          ),
          if (_lines.length <= 1) ...[
            const SizedBox(height: 14),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _suggest('Wedding photographer next month, mid budget'),
                _suggest('Food photoshoot in Cairo'),
                _suggest('Studio for a half day this weekend'),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _tag(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(color: const Color(0x3324140E), borderRadius: BorderRadius.circular(99), border: Border.all(color: const Color(0x55FF5A1F))),
      child: Text(label, style: const TextStyle(color: LensColors.primary, fontSize: 11, fontWeight: FontWeight.w800)),
    );
  }

  Widget _suggest(String label) {
    return GestureDetector(
      onTap: () => _send(label),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(color: const Color(0xFF1C1614), borderRadius: BorderRadius.circular(99), border: Border.all(color: const Color(0xFF2A2A2E))),
        child: Text(label, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700)),
      ),
    );
  }

  Widget _bubble(_ChatLine line) {
    final mine = line.role == 'user';
    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 10),
        constraints: const BoxConstraints(maxWidth: 320),
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
        decoration: BoxDecoration(
          color: mine ? LensColors.primary : const Color(0xFF161412),
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(18),
            topRight: const Radius.circular(18),
            bottomLeft: Radius.circular(mine ? 18 : 6),
            bottomRight: Radius.circular(mine ? 6 : 18),
          ),
        ),
        child: Text(line.body, style: TextStyle(color: mine ? Colors.white : const Color(0xFFE8E4DC), height: 1.35)),
      ),
    );
  }

  Widget _pickCard() {
    final why = _pick?['why']?.toString() ?? '';
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(22),
          gradient: const LinearGradient(colors: [Color(0xFFFF5A1F), Color(0xFFB73A0F)]),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('MY PICK', style: TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1.1)),
            const SizedBox(height: 6),
            Text(_pick?['display_name']?.toString() ?? 'Creator', style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            Text(why, style: const TextStyle(color: Colors.white, height: 1.35)),
          ],
        ),
      ),
    );
  }

  Widget _compareCard() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: const Color(0xFF161412), borderRadius: BorderRadius.circular(20), border: Border.all(color: const Color(0xFF2A2A2E))),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Comparison', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: 10),
            for (final row in _comparison)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  children: [
                    Text('${row['rank']}', style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w800)),
                    const SizedBox(width: 8),
                    Expanded(child: Text('${row['display_name']}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700))),
                    Text('${row['city'] ?? ''}', style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _vendorList() {
    return Column(
      children: [
        for (final vendor in _vendors)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Material(
              color: const Color(0xFF161412),
              borderRadius: BorderRadius.circular(18),
              child: InkWell(
                onTap: () => openVendorProfile(context, vendor, home),
                borderRadius: BorderRadius.circular(18),
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 22,
                        backgroundColor: const Color(0xFF24140E),
                        child: Text(vendor.initials, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(vendor.displayName, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                            Text('${vendor.vendorTypeName} · ${vendor.city}', style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                          ],
                        ),
                      ),
                      const Icon(Icons.chevron_right_rounded, color: LensColors.primary),
                    ],
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _addOnCard() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: const Color(0xFF161412), borderRadius: BorderRadius.circular(20)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Add-ons', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
            const SizedBox(height: 8),
            for (final item in _addOns)
              Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: Text('${item['name'] ?? item['vendor_type_name'] ?? 'Add-on'} — ${item['message'] ?? ''}', style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12.5, height: 1.35)),
              ),
          ],
        ),
      ),
    );
  }

  Widget _moodboardCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF161412),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: home.moodboardLocked ? const Color(0x55F79646) : const Color(0x55FF5A1F)),
      ),
      child: Row(
        children: [
          Icon(home.moodboardLocked ? Icons.lock_outline_rounded : Icons.auto_awesome, color: home.moodboardLocked ? LensColors.warning : LensColors.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              home.moodboardLocked ? 'Moodboard and shoot script unlock after payment.' : 'Moodboard can be generated from this brief.',
              style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _quickReplies() {
    final chips = switch (_ask) {
      'location' => home.cities.map((item) => item['name']?.toString() ?? '').where((item) => item.isNotEmpty).take(6).toList(),
      'date' => const ['This weekend', 'Next week', 'Next month'],
      'budget' => const ['Low', 'Mid', 'High'],
      _ => const <String>[],
    };
    if (chips.isEmpty) {
      return const SizedBox.shrink();
    }
    return SizedBox(
      height: 44,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
        itemCount: chips.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, index) => ActionChip(
          label: Text(chips[index]),
          onPressed: () => _send(chips[index]),
          backgroundColor: const Color(0xFF24140E),
          labelStyle: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
          side: const BorderSide(color: Color(0x55FF5A1F)),
        ),
      ),
    );
  }

  Widget _composer() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
      child: Row(
        children: [
          if (home.voiceInput)
            IconButton(
              tooltip: 'Voice input',
              onPressed: _busy ? null : _voice,
              icon: const Icon(Icons.mic_none_rounded, color: LensColors.primary),
            ),
          Expanded(
            child: TextField(
              controller: _input,
              enabled: !_busy,
              style: const TextStyle(color: Colors.white),
              onSubmitted: (_) => _send(),
              decoration: InputDecoration(
                hintText: home.aiPrompt,
                hintStyle: const TextStyle(color: Color(0xFF8E8B84), fontSize: 14),
                filled: true,
                fillColor: const Color(0xFF161412),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(99), borderSide: BorderSide.none),
              ),
            ),
          ),
          const SizedBox(width: 8),
          CircleAvatar(
            backgroundColor: LensColors.primary,
            child: IconButton(
              onPressed: _busy ? null : () => _send(),
              icon: const Icon(Icons.arrow_upward_rounded, color: Colors.white, size: 20),
            ),
          ),
        ],
      ),
    );
  }
}

class _Glow extends StatelessWidget {
  const _Glow();

  @override
  Widget build(BuildContext context) {
    return const IgnorePointer(
      child: Stack(
        children: [
          Positioned(top: -70, right: -40, child: _Blob(size: 210, color: Color(0x33FF5A1F))),
          Positioned(bottom: 120, left: -80, child: _Blob(size: 180, color: Color(0x18FF5A1F))),
        ],
      ),
    );
  }
}

class _Blob extends StatelessWidget {
  const _Blob({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(width: size, height: size, decoration: BoxDecoration(shape: BoxShape.circle, color: color));
  }
}

class _Reveal extends StatelessWidget {
  const _Reveal({required this.animation, required this.start, required this.child});

  final Animation<double> animation;
  final double start;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final curved = CurvedAnimation(parent: animation, curve: Interval(start, (start + 0.4).clamp(0.2, 1), curve: Curves.easeOutCubic));
    return FadeTransition(
      opacity: curved,
      child: SlideTransition(position: Tween<Offset>(begin: const Offset(0, 0.08), end: Offset.zero).animate(curved), child: child),
    );
  }
}

class _VoiceSheet extends StatelessWidget {
  const _VoiceSheet({required this.prompt});

  final String prompt;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(22, 18, 22, 28),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('Voice input', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          Text(prompt, style: const TextStyle(color: Color(0xFF8E8B84))),
          const SizedBox(height: 18),
          Container(
            width: 88,
            height: 88,
            decoration: BoxDecoration(shape: BoxShape.circle, color: LensColors.primary.withValues(alpha: 0.16), border: Border.all(color: LensColors.primary, width: 2)),
            child: const Icon(Icons.mic_rounded, color: LensColors.primary, size: 36),
          ),
          const SizedBox(height: 16),
          const Text('Tap a phrase or type after closing.', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5)),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            alignment: WrapAlignment.center,
            children: [
              for (final phrase in const ['Wedding in Cairo next month mid budget', 'Food shoot this weekend', 'Need a studio in Giza'])
                ActionChip(
                  label: Text(phrase),
                  onPressed: () => Navigator.pop(context, phrase),
                  backgroundColor: const Color(0xFF24140E),
                  labelStyle: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12),
                  side: const BorderSide(color: Color(0x55FF5A1F)),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ChatLine {
  const _ChatLine({required this.role, required this.body});

  final String role;
  final String body;
}

class _Turn {
  const _Turn({
    required this.reply,
    this.conversationId,
    this.provider = 'lexicon',
    this.status = 'gathering',
    this.ask,
    this.slots = const {},
    this.vendors = const [],
    this.comparison = const [],
    this.pick,
    this.addOns = const [],
  });

  factory _Turn.fromJson(Map<String, dynamic> json, HomeData home) {
    final vendors = (json['vendors'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => VendorCard.fromJson({
              ...Map<String, dynamic>.from(item),
              'location': item['city'],
              'starting_from': item['half_day_price'] ?? item['hourly_price'] ?? item['full_day_price'],
              'initials': (item['display_name']?.toString() ?? 'L').substring(0, 1).toUpperCase(),
            }))
        .toList();
    return _Turn(
      conversationId: json['conversation_id']?.toString(),
      provider: json['provider']?.toString() ?? 'lexicon',
      status: json['status']?.toString() ?? 'gathering',
      ask: json['ask']?.toString(),
      slots: json['slots'] is Map ? Map<String, dynamic>.from(json['slots'] as Map) : const {},
      vendors: vendors,
      comparison: (json['comparison'] as List<dynamic>? ?? const []).whereType<Map>().map(Map<String, dynamic>.from).toList(),
      pick: json['pick'] is Map ? Map<String, dynamic>.from(json['pick'] as Map) : null,
      addOns: (json['add_ons'] as List<dynamic>? ?? const []).whereType<Map>().map(Map<String, dynamic>.from).toList(),
      reply: json['reply']?.toString() ?? json['message']?.toString() ?? 'Tell me more.',
    );
  }

  final String? conversationId;
  final String provider;
  final String status;
  final String? ask;
  final Map<String, dynamic> slots;
  final List<VendorCard> vendors;
  final List<Map<String, dynamic>> comparison;
  final Map<String, dynamic>? pick;
  final List<Map<String, dynamic>> addOns;
  final String reply;
}

Map<String, dynamic> _mergeDemoSlots(Map<String, dynamic> current, String message, HomeData home) {
  final next = Map<String, dynamic>.from(current);
  final text = message.toLowerCase();
  if (text.contains('wedding') || text.contains('فرح')) {
    next['category_slugs'] = ['wedding'];
    next['vendor_type_slugs'] = ['photographer'];
  }
  if (text.contains('food') || text.contains('أكل') || text.contains('طعام')) {
    next['category_slugs'] = ['fnb'];
    next['vendor_type_slugs'] = ['photographer'];
  }
  if (text.contains('studio') || text.contains('ستوديو')) {
    next['vendor_type_slugs'] = ['studio'];
  }
  if (text.contains('video') || text.contains('فيديو')) {
    next['vendor_type_slugs'] = ['videographer'];
  }
  for (final city in home.cities) {
    final name = city['name']?.toString() ?? '';
    if (name.isNotEmpty && text.contains(name.toLowerCase())) {
      next['governorate'] = name;
      next['city_id'] = city['id'];
    }
  }
  if (text.contains('cairo') || text.contains('القاهرة')) {
    next['governorate'] = 'Cairo';
  }
  if (text.contains('giza') || text.contains('الجيزة')) {
    next['governorate'] = 'Giza';
  }
  if (text.contains('next month') || text.contains('الشهر')) {
    next['relative_date'] = 'next_month';
  }
  if (text.contains('weekend') || text.contains('ويكند')) {
    next['relative_date'] = 'weekend';
  }
  if (text.contains('next week') || text.contains('أسبوع')) {
    next['relative_date'] = 'next_week';
  }
  if (text.contains('mid') || text.contains('متوسط')) {
    next['budget_band'] = 'mid';
  }
  if (text.contains('low') || text.contains('رخيص')) {
    next['budget_band'] = 'low';
  }
  if (text.contains('high') || text.contains('غالي')) {
    next['budget_band'] = 'high';
  }
  return next;
}

String? _nextAsk(Map<String, dynamic> slots) {
  if (slots['governorate'] == null && slots['city_id'] == null) {
    return 'location';
  }
  if (slots['available_on'] == null && slots['relative_date'] == null) {
    return 'date';
  }
  if (slots['budget_band'] == null && slots['min_price'] == null && slots['max_price'] == null) {
    return 'budget';
  }
  return null;
}

String _question(String ask, String locale) {
  if (locale == 'ar') {
    return switch (ask) {
      'location' => 'تمام. التصوير هيكون في أنهي محافظة؟',
      'date' => 'تحب الجلسة يوم أنهي تاريخ تقريباً؟',
      _ => 'ميزانيتك كام تقريباً؟ رخيص، متوسط، ولا أعلى؟',
    };
  }
  return switch (ask) {
    'location' => 'Which Egyptian governorate should the shoot be in?',
    'date' => 'Which date works for the session?',
    _ => 'What budget range should I stay within (EGP)? Low, mid, or high is enough.',
  };
}

List<Map<String, dynamic>> _demoAddOns(Map<String, dynamic> slots) {
  final categories = (slots['category_slugs'] as List?)?.cast<String>() ?? const [];
  if (categories.contains('wedding')) {
    return [
      {'name': 'Videographer', 'message': 'Weddings often need a second shooter on video.'},
    ];
  }
  return const [];
}
