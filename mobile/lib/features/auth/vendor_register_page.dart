import 'dart:io';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/egypt_phone.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/register_catalog.dart';
import 'package:lens/features/auth/register_scaffold.dart';
import 'package:lens/features/home/book_draft.dart';
import 'package:lens/features/home/book_map_picker_page.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class VendorRegisterPage extends StatefulWidget {
  const VendorRegisterPage({
    super.key,
    required this.bootstrap,
    this.onSuccess,
    this.editing = false,
  });

  final Map<String, dynamic> bootstrap;
  final VoidCallback? onSuccess;
  final bool editing;

  @override
  State<VendorRegisterPage> createState() => _VendorRegisterPageState();
}

class _VendorRegisterPageState extends State<VendorRegisterPage> {
  static const _titles = ['Identity', 'Type profile', 'Previous projects', 'Pricing', 'Bank & payouts'];
  static const _subtitles = [
    'Account, vendor type, and identity fields from Create Vendor.',
    'Choosing a vendor type loads the matching profile fields.',
    'Past jobs already delivered — photos, reels, UGC clips, or social links.',
    'Admin-defined price fields for the selected vendor type.',
    'The same payout options as Create Vendor in admin.',
  ];

  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _phone = TextEditingController();
  final _displayName = TextEditingController();
  final _profession = TextEditingController();
  final _bio = TextEditingController();
  final _contactEmail = TextEditingController();
  final _whatsapp = TextEditingController();
  final _instagram = TextEditingController();
  final _address = TextEditingController();
  final _holder = TextEditingController();
  final _accountNumber = TextEditingController();
  final _iban = TextEditingController();
  final _swift = TextEditingController();
  final _branch = TextEditingController();
  final _branchCode = TextEditingController();
  final _walletPhone = TextEditingController();
  final _paypalEmail = TextEditingController();
  final _paypalName = TextEditingController();
  final _transferNotes = TextEditingController();
  final Map<String, TextEditingController> _texts = {};
  final Map<String, List<String>> _lists = {};
  final List<_ProjectDraft> _projects = [];
  late final RegisterCatalog _catalog;
  final _api = ApiClient();
  int _step = 0;
  bool _busy = false;
  bool _loading = false;
  bool _hidePassword = true;
  String? _error;
  String? _vendorType;
  String? _cityId;
  DateTime? _dob;
  String? _payoutMethod;
  String? _accountType;
  String? _bankName;
  String? _walletNetwork;
  String? _walletTelecom;
  String? _walletBank;
  String? _pricingModelId;
  XFile? _profilePhoto;
  XFile? _nationalId;
  XFile? _coverImage;
  String? _profilePhotoUrl;
  String? _nationalIdUrl;
  String? _coverImageUrl;
  double? _latitude;
  double? _longitude;

  VendorTypeSpec get _spec => _catalog.specFor(_vendorType);

  List<PricingModelSpec> get _pricingModels => _spec.resolvedModels;

  PricingModelSpec? get _selectedPricing {
    for (final model in _pricingModels) {
      if (model.id == _pricingModelId) {
        return model;
      }
    }
    return _pricingModels.length == 1 ? _pricingModels.first : null;
  }

  bool get _editing => widget.editing;

  @override
  void initState() {
    super.initState();
    _catalog = RegisterCatalog.fromBootstrap(widget.bootstrap);
    if (_editing) {
      _hydrateFromSession();
      _loadProfile();
    }
  }

  @override
  void dispose() {
    for (final controller in [
      _name, _email, _password, _phone, _displayName, _profession, _bio, _contactEmail, _whatsapp, _instagram,
      _address, _holder, _accountNumber, _iban, _swift, _branch, _branchCode, _walletPhone,
      _paypalEmail, _paypalName, _transferNotes,
    ]) {
      controller.dispose();
    }
    for (final controller in _texts.values) {
      controller.dispose();
    }
    for (final project in _projects) {
      project.dispose();
    }
    super.dispose();
  }

  TextEditingController _text(String key) => _texts.putIfAbsent(key, TextEditingController.new);

  List<String> _list(String key) => _lists.putIfAbsent(key, () => <String>[]);

  bool get _live => LensConfig.useNetwork && SessionStore.instance.account != null;

  void _hydrateFromSession() {
    final account = SessionStore.instance.account;
    if (account == null) {
      return;
    }
    _name.text = account.name;
    _email.text = account.email;
    _phone.text = account.phone ?? '';
    _displayName.text = account.vendorName ?? account.name;
    _cityId = account.cityId?.toString();
    _vendorType ??= 'photographer';
  }

  Future<void> _loadProfile() async {
    if (!_live) {
      _hydrateDemoVendor();
      return;
    }
    setState(() => _loading = true);
    try {
      final payload = await _api.getJson('/app/account/profile');
      if (!mounted) {
        return;
      }
      _applyProfile(payload);
      setState(() => _loading = false);
    } catch (_) {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  void _hydrateDemoVendor() {
    _vendorType = 'photographer';
    _displayName.text = SessionStore.instance.account?.vendorName ?? 'Fahad Studio Light';
    _profession.text = 'Wedding & product photographer';
    _bio.text = 'Product and wedding photography with portrait lenses and strobe lighting.';
    _whatsapp.text = _phone.text;
    _contactEmail.text = _email.text;
    _instagram.text = '@fahadstudiolight';
    _address.text = 'Zamalek, Cairo';
    _latitude = 30.0626;
    _longitude = 31.2197;
    _coverImageUrl = 'https://images.unsplash.com/photo-1539650116574-75c0c6d73f6e?auto=format&fit=crop&w=1400&h=900&q=80';
    _nationalIdUrl = 'https://images.unsplash.com/photo-1589330694653-beb37556d8e1?auto=format&fit=crop&w=800&h=500&q=80';
    _profilePhotoUrl = SessionStore.instance.account?.avatarUrl;
    _dob = DateTime(1992, 4, 18);
    _payoutMethod = 'bank';
    _bankName = 'Banque Misr';
    _accountType = 'current';
    _holder.text = 'Fahad Photography';
    _accountNumber.text = '2039485761';
    _iban.text = 'EG380003000203948576100000000';
    _swift.text = 'BMISEGCXXXX';
    _branch.text = 'Zamalek';
    _branchCode.text = '003';
    _transferNotes.text = 'Use InstaPay for same-day payouts.';
    _text('half_day_price').text = '1800';
    _text('full_day_price').text = '3200';
    _text('turnaround_hours').text = '48';
    _lists['specialties'] = ['wedding', 'product', 'editorial'];
    final models = _spec.resolvedModels;
    _pricingModelId = models.length == 1 ? models.first.id : _pricingModelId;
  }

  void _applyProfile(Map<String, dynamic> payload) {
    _name.text = payload['name']?.toString() ?? _name.text;
    _email.text = payload['email']?.toString() ?? _email.text;
    _phone.text = payload['phone']?.toString() ?? _phone.text;
    _cityId = payload['city_id']?.toString() ?? _cityId;
    final vendor = Map<String, dynamic>.from((payload['vendor'] as Map?) ?? const {});
    if (vendor.isEmpty) {
      return;
    }
    _vendorType = vendor['vendor_type']?.toString() ?? _vendorType;
    _displayName.text = vendor['display_name']?.toString() ?? _displayName.text;
    _profession.text = vendor['profession']?.toString() ?? '';
    _bio.text = vendor['bio']?.toString() ?? '';
    _contactEmail.text = vendor['contact_email']?.toString() ?? _email.text;
    _whatsapp.text = vendor['whatsapp']?.toString() ?? '';
    _instagram.text = vendor['instagram']?.toString() ?? '';
    _address.text = vendor['address']?.toString() ?? '';
    _latitude = _asDouble(vendor['latitude']);
    _longitude = _asDouble(vendor['longitude']);
    _profilePhotoUrl = vendor['profile_photo']?.toString();
    _nationalIdUrl = vendor['national_id_image']?.toString();
    _coverImageUrl = vendor['cover_image']?.toString();
    _cityId = vendor['city_id']?.toString() ?? _cityId;
    _dob = DateTime.tryParse(vendor['date_of_birth']?.toString() ?? '');
    _payoutMethod = vendor['payout_method']?.toString();
    _bankName = vendor['bank_name']?.toString();
    _accountType = vendor['bank_account_type']?.toString();
    _holder.text = vendor['bank_account_holder']?.toString() ?? '';
    _accountNumber.text = vendor['bank_account_number']?.toString() ?? '';
    _iban.text = vendor['bank_iban']?.toString() ?? '';
    _swift.text = vendor['bank_swift']?.toString() ?? '';
    _branch.text = vendor['bank_branch']?.toString() ?? '';
    _branchCode.text = vendor['bank_branch_code']?.toString() ?? '';
    _walletNetwork = vendor['wallet_network_type']?.toString();
    _walletTelecom = vendor['wallet_telecom']?.toString();
    _walletBank = vendor['wallet_bank_name']?.toString();
    _walletPhone.text = vendor['wallet_phone']?.toString() ?? '';
    _paypalEmail.text = vendor['paypal_email']?.toString() ?? '';
    _paypalName.text = vendor['paypal_name']?.toString() ?? '';
    _transferNotes.text = vendor['transfer_notes']?.toString() ?? '';
    _pricingModelId = vendor['pricing_model_id']?.toString();
    _lists['specialties'] = _stringList(vendor['specialties']);
    _lists['delivery_formats'] = _stringList(vendor['delivery_formats']);
    final extras = Map<String, dynamic>.from((vendor['extras'] as Map?) ?? const {});
    extras.forEach((key, value) {
      if (key == 'prices' && value is Map) {
        value.forEach((priceKey, priceValue) {
          _text('extras.prices.$priceKey').text = '${priceValue ?? ''}';
        });
        return;
      }
      if (value is List) {
        _lists['extras.$key'] = _stringList(value);
        return;
      }
      _text('extras.$key').text = value?.toString() ?? '';
    });
    for (final field in [..._spec.fields, ...?_selectedPricing?.fields]) {
      if (field.key.startsWith('filter.') || field.isList) {
        continue;
      }
      final raw = vendor[field.key];
      if (raw != null) {
        _text(field.key).text = '$raw';
      }
    }
    _applyFilterTags(_stringList(vendor['filter_tags']));
    for (final project in _projects) {
      project.dispose();
    }
    _projects
      ..clear()
      ..addAll(
        (vendor['projects'] as List<dynamic>? ?? const [])
            .whereType<Map>()
            .map((item) => _ProjectDraft.fromJson(Map<String, dynamic>.from(item))),
      );
    final models = _spec.resolvedModels;
    if (_pricingModelId == null && models.length == 1) {
      _pricingModelId = models.first.id;
    }
  }

  void _applyFilterTags(List<String> tags) {
    for (final field in _spec.fields) {
      if (!field.key.startsWith('filter.')) {
        continue;
      }
      final allowed = field.options.map((item) => item.id).toSet();
      _lists[field.key] = tags.where(allowed.contains).toList();
    }
  }

  List<String> _stringList(Object? raw) {
    return (raw as List<dynamic>? ?? const []).map((item) => item.toString()).where((item) => item.isNotEmpty).toList();
  }

  double? _asDouble(Object? raw) {
    if (raw is num) {
      return raw.toDouble();
    }
    return double.tryParse(raw?.toString() ?? '');
  }

  Future<void> _continue() async {
    setState(() => _error = null);
    if (_editing) {
      await _saveEdit();
      return;
    }
    if (_step == 0) {
      if (_name.text.trim().isEmpty || _email.text.trim().isEmpty || _password.text.length < 6) {
        setState(() => _error = 'Enter your name, email, and a password of at least 6 characters.');
        return;
      }
      if (!EgyptPhone.isValid(_phone.text, required: true) || !EgyptPhone.isValid(_whatsapp.text)) {
        setState(() => _error = EgyptPhone.message);
        return;
      }
      if ((_vendorType ?? '').isEmpty) {
        setState(() => _error = 'Choose a vendor type to load the matching Type profile.');
        return;
      }
      if (_displayName.text.trim().isEmpty) {
        _displayName.text = _name.text.trim();
      }
      setState(() => _step = 1);
      return;
    }
    if (_step == 3) {
      final model = _selectedPricing;
      if (model == null) {
        setState(() => _error = 'Choose the pricing model assigned to this vendor type.');
        return;
      }
      for (final field in model.fields) {
        if (_text(field.key).text.trim().isEmpty) {
          setState(() => _error = 'Fill every price field from ${model.name}.');
          return;
        }
      }
      setState(() => _step = 4);
      return;
    }
    if (_step < 4) {
      setState(() => _step += 1);
      return;
    }
    if ((_payoutMethod ?? '').isEmpty) {
      setState(() => _error = 'Choose a payout method.');
      return;
    }
    if (_payoutMethod == 'wallet' && !EgyptPhone.isValid(_walletPhone.text, required: true)) {
      setState(() => _error = EgyptPhone.message);
      return;
    }
    setState(() => _busy = true);
    try {
      await SessionStore.instance.register(
        name: _name.text,
        email: _email.text,
        password: _password.text,
        role: 'vendor',
        extra: _payload(),
        files: [...await _identityFiles(), ...await _projectFiles()],
      );
      if (widget.onSuccess != null) {
        widget.onSuccess!();
      } else if (mounted) {
        Navigator.of(context).pop();
      }
    } catch (error) {
      setState(() => _error = error.toString().replaceFirst('Exception: ', '').replaceFirst('ApiException: ', ''));
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Future<void> _saveEdit() async {
    if (_name.text.trim().isEmpty || _email.text.trim().isEmpty) {
      setState(() => _error = 'Name and email are required.');
      return;
    }
    if (!EgyptPhone.isValid(_phone.text, required: true) || !EgyptPhone.isValid(_whatsapp.text)) {
      setState(() => _error = EgyptPhone.message);
      return;
    }
    if ((_vendorType ?? '').isEmpty) {
      setState(() => _error = 'Choose a vendor type to load the matching Type profile.');
      return;
    }
    if (_displayName.text.trim().isEmpty) {
      _displayName.text = _name.text.trim();
    }
    final model = _selectedPricing;
    if (model != null) {
      for (final field in model.fields) {
        if (_text(field.key).text.trim().isEmpty) {
          setState(() => _error = 'Fill every price field from ${model.name}.');
          return;
        }
      }
    }
    if ((_payoutMethod ?? '').isEmpty) {
      setState(() => _error = 'Choose a payout method.');
      return;
    }
    if (_payoutMethod == 'wallet' && !EgyptPhone.isValid(_walletPhone.text, required: true)) {
      setState(() => _error = EgyptPhone.message);
      return;
    }

    setState(() => _busy = true);
    final next = (SessionStore.instance.account ??
            SessionAccount(name: _name.text.trim(), email: _email.text.trim(), role: 'vendor'))
        .copyWith(
      name: _name.text.trim(),
      email: _email.text.trim(),
      phone: EgyptPhone.digits(_phone.text),
      vendorName: _displayName.text.trim(),
      cityId: int.tryParse(_cityId ?? ''),
    );
    try {
      if (_live) {
        final body = {
          'name': next.name,
          'email': next.email,
          'locale': next.locale,
          ..._payload(),
        };
        final files = [...await _identityFiles(), ...await _projectFiles()];
        final payload = files.isEmpty
            ? await _api.postJson('/app/account/profile', body)
            : await _api.postForm('/app/account/profile', body, files: files);
        SessionStore.instance.applyAccount(SessionAccount.fromJson(payload).copyWith(
          vendorId: next.vendorId,
          vendorName: payload['vendor_name']?.toString() ?? next.vendorName,
        ));
      } else {
        SessionStore.instance.applyAccount(next);
      }
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profile updated'), backgroundColor: Color(0xFF24140E)),
      );
      if (Navigator.of(context).canPop()) {
        Navigator.of(context).pop();
      }
    } catch (error) {
      setState(() => _error = error.toString().replaceFirst('Exception: ', '').replaceFirst('ApiException: ', ''));
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  Map<String, dynamic> _payload() {
    final extras = <String, dynamic>{};
    final prices = <String, dynamic>{};
    final body = <String, dynamic>{
      if (_selectedPricing != null && int.tryParse(_selectedPricing!.id) != null) 'pricing_model_id': int.parse(_selectedPricing!.id),
      'phone': EgyptPhone.digits(_phone.text),
      'vendor_type': _vendorType,
      'display_name': _displayName.text.trim(),
      'date_of_birth': _dob?.toIso8601String().split('T').first,
      'profession': _profession.text.trim(),
      'bio': _bio.text.trim(),
      'contact_phone': EgyptPhone.digits(_phone.text),
      'contact_email': (_contactEmail.text.trim().isEmpty ? _email.text : _contactEmail.text).trim(),
      'whatsapp': EgyptPhone.digits(_whatsapp.text),
      'instagram': _instagram.text.trim(),
      'address': _address.text.trim(),
      if (_latitude != null) 'latitude': _latitude,
      if (_longitude != null) 'longitude': _longitude,
      if (_cityId != null && int.tryParse(_cityId!) != null) 'city_id': int.parse(_cityId!),
      'payout_method': _payoutMethod,
      'bank_name': _bankName,
      'bank_account_type': _accountType,
      'bank_account_holder': _holder.text.trim(),
      'bank_account_number': _accountNumber.text.trim(),
      'bank_iban': _iban.text.trim(),
      'bank_swift': _swift.text.trim(),
      'bank_branch': _branch.text.trim(),
      'bank_branch_code': _branchCode.text.trim(),
      'wallet_network_type': _walletNetwork,
      'wallet_telecom': _walletTelecom,
      'wallet_bank_name': _walletBank,
      'wallet_phone': EgyptPhone.digits(_walletPhone.text),
      'paypal_email': _paypalEmail.text.trim(),
      'paypal_name': _paypalName.text.trim(),
      'transfer_notes': _transferNotes.text.trim(),
      'projects': [
        for (final project in _projects)
          if (project.title.text.trim().isNotEmpty)
            {
              'ids': project.ids,
              'type': project.type,
              'title': project.title.text.trim(),
              'completed_on': project.completedOn.text.trim(),
              'external_url': project.url.text.trim(),
              'description': project.description.text.trim(),
              'is_featured': project.featured,
            },
      ],
    };

    final filterTags = <String>[];
    for (final field in [..._spec.fields, ...?_selectedPricing?.fields]) {
      if (field.isList) {
        final values = _list(field.key);
        if (field.key.startsWith('filter.')) {
          filterTags.addAll(values);
        } else if (field.key == 'specialties') {
          body['specialties'] = values;
        } else if (field.key == 'delivery_formats') {
          body['delivery_formats'] = values;
        } else if (field.key.startsWith('extras.')) {
          extras[field.key.substring(7)] = values;
        }
        continue;
      }
      final raw = _text(field.key).text.trim();
      if (raw.isEmpty) {
        continue;
      }
      final value = field.type == 'number' ? num.tryParse(raw) ?? raw : raw;
      if (field.key.startsWith('extras.prices.')) {
        prices[field.key.substring('extras.prices.'.length)] = value;
      } else if (field.key.startsWith('extras.')) {
        extras[field.key.substring(7)] = value;
      } else {
        body[field.key] = value;
      }
    }
    if (prices.isNotEmpty) {
      extras['prices'] = prices;
    }
    if (extras.isNotEmpty) {
      body['extras'] = extras;
    }
    if (filterTags.isNotEmpty) {
      body['filter_tags'] = filterTags;
    }
    return body;
  }

  Future<List<http.MultipartFile>> _identityFiles() async {
    final files = <http.MultipartFile>[];
    Future<void> add(String field, XFile? file) async {
      if (file == null) {
        return;
      }
      files.add(await http.MultipartFile.fromPath(field, file.path, filename: file.name));
    }

    await add('profile_photo', _profilePhoto);
    await add('national_id_image', _nationalId);
    await add('cover_image', _coverImage);
    return files;
  }

  Future<List<http.MultipartFile>> _projectFiles() async {
    final files = <http.MultipartFile>[];
    var payloadIndex = 0;
    for (final project in _projects) {
      if (project.title.text.trim().isEmpty) {
        continue;
      }
      for (var order = 0; order < project.files.length; order++) {
        final file = project.files[order];
        files.add(http.MultipartFile.fromBytes(
          'projects[$payloadIndex][files][$order]',
          await file.readAsBytes(),
          filename: file.name,
        ));
      }
      payloadIndex += 1;
    }
    return files;
  }

  @override
  Widget build(BuildContext context) {
    if (_editing) {
      return DefaultTabController(
        length: _titles.length,
        child: ProfileScaffold(
          title: 'Edit Profile',
          action: TextButton(
            onPressed: _busy ? null : _continue,
            child: Text(_busy ? 'Saving' : 'Save', style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w800)),
          ),
          child: Column(
            children: [
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 0, 20, 8),
                child: Text(
                  'Same Identity, Type profile, Previous projects, Pricing, and Bank & payouts tabs as the admin vendor page.',
                  style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5, height: 1.35),
                ),
              ),
              TabBar(
                isScrollable: true,
                tabAlignment: TabAlignment.start,
                labelColor: LensColors.primary,
                unselectedLabelColor: const Color(0xFF8E8B84),
                indicatorColor: LensColors.primary,
                dividerColor: const Color(0xFF2A2A2E),
                tabs: [for (final title in _titles) Tab(text: title)],
              ),
              if (_loading) const LinearProgressIndicator(color: LensColors.primary, minHeight: 2),
              Expanded(
                child: TabBarView(
                  children: [
                    _scroll(_identity()),
                    _scroll(_typeProfile()),
                    _scroll(_projectsStep()),
                    _scroll(_pricing()),
                    _scroll(_payout()),
                  ],
                ),
              ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
                  child: Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
                ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 16),
                child: SizedBox(
                  height: 54,
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: _busy ? null : _continue,
                    style: FilledButton.styleFrom(
                      backgroundColor: LensColors.primary,
                      foregroundColor: Colors.white,
                      disabledBackgroundColor: LensColors.primary.withValues(alpha: 0.5),
                      elevation: 0,
                      shape: const StadiumBorder(),
                    ),
                    child: _busy
                        ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : const Text('Save changes', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }
    return RegisterScaffold(
      title: _step == 1 ? _spec.profileTitle : _titles[_step],
      subtitle: _step == 1 ? _spec.profileDescription : _subtitles[_step],
      step: _step,
      total: 5,
      busy: _busy,
      error: _error,
      onBack: () {
        if (_step == 0) {
          Navigator.of(context).maybePop();
          return;
        }
        setState(() => _step -= 1);
      },
      onContinue: _continue,
      child: switch (_step) {
        0 => _identity(),
        1 => _typeProfile(),
        2 => _projectsStep(),
        3 => _pricing(),
        _ => _payout(),
      },
    );
  }

  Widget _scroll(Widget child) {
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
      child: child,
    );
  }

  Widget _identity() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        RegisterField(controller: _name, hint: 'Full name', icon: Icons.person_outline_rounded),
        const SizedBox(height: 12),
        RegisterField(controller: _email, hint: 'Email', icon: Icons.mail_outline_rounded, keyboard: TextInputType.emailAddress),
        const SizedBox(height: 12),
        RegisterField(
          controller: _phone,
          hint: 'Phone 010 / 011 / 012 / 015',
          icon: Icons.phone_outlined,
          keyboard: TextInputType.phone,
          digitsOnly: true,
          maxLength: 11,
        ),
        if (!_editing) ...[
          const SizedBox(height: 12),
          RegisterField(
            controller: _password,
            hint: 'Password',
            icon: Icons.lock_outline_rounded,
            secret: _hidePassword,
            suffix: IconButton(
              onPressed: () => setState(() => _hidePassword = !_hidePassword),
              icon: Icon(_hidePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, color: const Color(0xFF8E8B84)),
            ),
          ),
        ],
        const SizedBox(height: 18),
        const Text('Vendor type *', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
        const SizedBox(height: 6),
        const Text(
          'Choosing a type loads the matching Type profile — the same filter-screen fields clients use in the app.',
          style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, height: 1.35),
        ),
        const SizedBox(height: 8),
        RegisterChoiceChips(
          options: _catalog.typeChoices,
          selected: _vendorType,
          onSelect: (value) => setState(() {
            _vendorType = value;
            final models = _catalog.specFor(value).resolvedModels;
            _pricingModelId = models.length == 1 ? models.first.id : null;
          }),
        ),
        const SizedBox(height: 14),
        RegisterField(controller: _displayName, hint: 'Display name', icon: Icons.badge_outlined),
        const SizedBox(height: 14),
        _imageField(
          label: 'Personal photo',
          hint: 'Same avatar used on the admin Identity tab.',
          icon: Icons.person_outline_rounded,
          file: _profilePhoto,
          url: _profilePhotoUrl,
          height: 140,
          onPick: (file) => setState(() => _profilePhoto = file),
        ),
        const SizedBox(height: 12),
        _imageField(
          label: 'National ID card',
          hint: 'Front of the Egyptian national ID for verification.',
          icon: Icons.badge_outlined,
          file: _nationalId,
          url: _nationalIdUrl,
          height: 160,
          onPick: (file) => setState(() => _nationalId = file),
        ),
        const SizedBox(height: 12),
        _dateField(),
        if (_dob != null) ...[
          const SizedBox(height: 8),
          Text('Age ${_ageYears()} years', style: const TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w600)),
        ],
        const SizedBox(height: 12),
        RegisterField(controller: _profession, hint: 'Profession / job title', icon: Icons.work_outline_rounded),
        const SizedBox(height: 12),
        RegisterField(controller: _bio, hint: 'About', maxLines: 4),
        const SizedBox(height: 12),
        RegisterField(controller: _contactEmail, hint: 'Contact email', icon: Icons.alternate_email_rounded, keyboard: TextInputType.emailAddress),
        const SizedBox(height: 12),
        RegisterField(
          controller: _whatsapp,
          hint: 'WhatsApp 010 / 011 / 012 / 015',
          icon: Icons.chat_outlined,
          keyboard: TextInputType.phone,
          digitsOnly: true,
          maxLength: 11,
        ),
        const SizedBox(height: 12),
        RegisterField(controller: _instagram, hint: 'Instagram', icon: Icons.camera_alt_outlined),
        const SizedBox(height: 12),
        RegisterDropdown(
          value: _cityId,
          options: _catalog.cityChoices,
          hint: 'Home governorate',
          onChanged: (value) => setState(() => _cityId = value),
        ),
        const SizedBox(height: 12),
        _mapAddressTile(),
        const SizedBox(height: 12),
        _imageField(
          label: 'Cover image',
          hint: 'Marketplace cover used on the admin Identity tab.',
          icon: Icons.photo_outlined,
          file: _coverImage,
          url: _coverImageUrl,
          height: 170,
          onPick: (file) => setState(() => _coverImage = file),
        ),
      ],
    );
  }

  Widget _typeProfile() {
    if ((_vendorType ?? '').isEmpty) {
      return const Text(
        'Choose a vendor type on Identity. Matching portfolio, gear, and specialty fields will appear here.',
        style: TextStyle(color: Color(0xFFB0ABA3), height: 1.4),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (final field in _spec.fields) ...[
          Text(field.label, style: const TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          _dynamicField(field),
          const SizedBox(height: 14),
        ],
        Text(
          'Add a portfolio under Previous projects next — the same tab used in admin.',
          style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13, height: 1.35),
        ),
      ],
    );
  }

  Widget _projectsStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Upload a photo, a photo gallery, one video, or several videos — the same Previous projects tab in admin.',
          style: TextStyle(color: Color(0xFFB0ABA3), height: 1.35),
        ),
        const SizedBox(height: 14),
        for (var index = 0; index < _projects.length; index++) ...[
          _projectCard(_projects[index], index),
          const SizedBox(height: 12),
        ],
        OutlinedButton.icon(
          onPressed: () => setState(() => _projects.add(_ProjectDraft())),
          icon: const Icon(Icons.add_rounded),
          label: const Text('Add previous project'),
          style: OutlinedButton.styleFrom(
            foregroundColor: Colors.white,
            side: const BorderSide(color: Color(0xFF2E2A26)),
            backgroundColor: const Color(0xFF141210),
            shape: const StadiumBorder(),
            minimumSize: const Size.fromHeight(48),
          ),
        ),
      ],
    );
  }

  Widget _projectCard(_ProjectDraft project, int index) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xE6121214),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFF2A2A2E)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text('Project ${index + 1}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
              const Spacer(),
              IconButton(
                onPressed: () => setState(() {
                  project.dispose();
                  _projects.removeAt(index);
                }),
                icon: const Icon(Icons.delete_outline_rounded, color: Color(0xFF8E8B84)),
              ),
            ],
          ),
          const Text('Item type', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          RegisterChoiceChips(
            options: const [
              CatalogOption(id: 'image', label: 'Photo'),
              CatalogOption(id: 'video', label: 'Video'),
              CatalogOption(id: 'link', label: 'Social / external link'),
            ],
            selected: project.type,
            onSelect: (value) => setState(() {
              project.type = value;
              if (value == 'link') {
                project.files.clear();
              }
            }),
          ),
          const SizedBox(height: 12),
          RegisterField(controller: project.title, hint: 'Project title'),
          const SizedBox(height: 12),
          RegisterField(controller: project.completedOn, hint: 'Completed on'),
          if (project.type == 'image') ...[
            const SizedBox(height: 12),
            _mediaButton(
              label: project.files.isEmpty ? 'Add photos / gallery' : 'Add more photos',
              icon: Icons.photo_library_outlined,
              onTap: () => _pickImages(project),
            ),
          ] else if (project.type == 'video') ...[
            const SizedBox(height: 12),
            _mediaButton(
              label: project.files.isEmpty ? 'Add videos' : 'Add more videos',
              icon: Icons.video_library_outlined,
              onTap: () => _pickVideo(project),
            ),
            const SizedBox(height: 12),
            RegisterField(controller: project.url, hint: 'Sample URL (optional)', keyboard: TextInputType.url),
          ] else ...[
            const SizedBox(height: 12),
            RegisterField(controller: project.url, hint: 'Sample URL', keyboard: TextInputType.url),
          ],
          if (project.existing.isNotEmpty || project.files.isNotEmpty) ...[
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (var fileIndex = 0; fileIndex < project.existing.length; fileIndex++)
                  _existingChip(project, fileIndex),
                for (var fileIndex = 0; fileIndex < project.files.length; fileIndex++)
                  _fileChip(project, fileIndex),
              ],
            ),
          ],
          const SizedBox(height: 12),
          RegisterField(controller: project.description, hint: 'What was delivered', maxLines: 3),
          const SizedBox(height: 8),
          SwitchListTile.adaptive(
            contentPadding: EdgeInsets.zero,
            value: project.featured,
            onChanged: (value) => setState(() => project.featured = value),
            activeThumbColor: LensColors.primary,
            title: const Text('Feature this project', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }

  Widget _pricing() {
    if (_pricingModels.isEmpty) {
      return const Text(
        'This vendor type has no pricing model yet. Admin must add a Pricing model and select this vendor type.',
        style: TextStyle(color: Color(0xFFB0ABA3), height: 1.4),
      );
    }
    final selected = _selectedPricing;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Pricing model', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
        const SizedBox(height: 8),
        RegisterChoiceChips(
          options: [for (final model in _pricingModels) CatalogOption(id: model.id, label: model.name)],
          selected: selected?.id,
          onSelect: (value) => setState(() => _pricingModelId = value),
        ),
        const SizedBox(height: 16),
        if (selected == null)
          const Text('Choose the pricing model for this vendor type, then fill the amounts.', style: TextStyle(color: Color(0xFFB0ABA3), height: 1.4))
        else ...[
          for (final field in selected.fields) ...[
            Text(field.label, style: const TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
            if ((field.helper ?? '').isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(field.helper!, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
            ],
            const SizedBox(height: 8),
            _dynamicField(field),
            const SizedBox(height: 14),
          ],
        ],
      ],
    );
  }

  Widget _mediaButton({required String label, required IconData icon, required VoidCallback onTap}) {
    return OutlinedButton.icon(
      onPressed: onTap,
      icon: Icon(icon),
      label: Text(label),
      style: OutlinedButton.styleFrom(
        foregroundColor: Colors.white,
        side: const BorderSide(color: Color(0xFF2E2A26)),
        backgroundColor: const Color(0xFF141210),
        shape: const StadiumBorder(),
        minimumSize: const Size.fromHeight(48),
      ),
    );
  }

  Widget _fileChip(_ProjectDraft project, int index) {
    return GestureDetector(
      onTap: () => setState(() => project.files.removeAt(index)),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFF1A120C),
          borderRadius: BorderRadius.circular(99),
          border: Border.all(color: LensColors.primary),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(project.type == 'video' ? Icons.videocam_outlined : Icons.image_outlined, color: LensColors.primary, size: 16),
            const SizedBox(width: 6),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 160),
              child: Text(project.files[index].name, overflow: TextOverflow.ellipsis, style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 12)),
            ),
            const SizedBox(width: 6),
            const Icon(Icons.close_rounded, color: LensColors.primary, size: 14),
          ],
        ),
      ),
    );
  }

  Widget _existingChip(_ProjectDraft project, int index) {
    final file = project.existing[index];
    return GestureDetector(
      onTap: () => setState(() {
        project.existing.removeAt(index);
        project.ids.remove(file.id);
      }),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: const Color(0xFF1A120C),
          borderRadius: BorderRadius.circular(99),
          border: Border.all(color: LensColors.primary),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(project.type == 'video' ? Icons.videocam_outlined : Icons.image_outlined, color: LensColors.primary, size: 16),
            const SizedBox(width: 6),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 160),
              child: Text(file.name, overflow: TextOverflow.ellipsis, style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 12)),
            ),
            const SizedBox(width: 6),
            const Icon(Icons.close_rounded, color: LensColors.primary, size: 14),
          ],
        ),
      ),
    );
  }

  Future<void> _pickImages(_ProjectDraft project) async {
    final picked = await ImagePicker().pickMultiImage(imageQuality: 85);
    if (picked.isEmpty) {
      return;
    }
    setState(() => project.files.addAll(picked));
  }

  Future<void> _pickVideo(_ProjectDraft project) async {
    final picked = await ImagePicker().pickVideo(source: ImageSource.gallery);
    if (picked == null) {
      return;
    }
    setState(() => project.files.add(picked));
  }

  Widget _dynamicField(RegisterFieldSpec field) {
    return switch (field.type) {
      'multi' => RegisterMultiChips(
          options: field.options,
          selected: _list(field.key),
          onChanged: (value) => setState(() => _lists[field.key] = value),
        ),
      'tags' => RegisterTagField(
          values: _list(field.key),
          hint: field.placeholder ?? field.label,
          onChanged: (value) => setState(() => _lists[field.key] = value),
        ),
      'textarea' => RegisterField(controller: _text(field.key), hint: field.placeholder ?? field.label, maxLines: 4),
      'number' => RegisterField(
          controller: _text(field.key),
          hint: field.prefix == null ? (field.placeholder ?? field.label) : '${field.prefix} ${field.label}',
          keyboard: const TextInputType.numberWithOptions(decimal: true),
        ),
      'url' => RegisterField(controller: _text(field.key), hint: field.placeholder ?? field.label, keyboard: TextInputType.url),
      _ => RegisterField(controller: _text(field.key), hint: field.placeholder ?? field.label),
    };
  }

  Widget _payout() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Payout method', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
        const SizedBox(height: 8),
        RegisterChoiceChips(
          options: const [
            CatalogOption(id: 'bank', label: 'Bank account'),
            CatalogOption(id: 'wallet', label: 'Mobile wallet'),
            CatalogOption(id: 'paypal', label: 'PayPal'),
          ],
          selected: _payoutMethod,
          onSelect: (value) => setState(() => _payoutMethod = value),
        ),
        const SizedBox(height: 16),
        if (_payoutMethod == 'bank') ...[
          RegisterDropdown(value: _bankName, options: _catalog.bankChoices, hint: 'Bank', onChanged: (value) => setState(() => _bankName = value)),
          const SizedBox(height: 12),
          RegisterDropdown(
            value: _accountType,
            options: const [
              CatalogOption(id: 'current', label: 'Current'),
              CatalogOption(id: 'savings', label: 'Savings'),
            ],
            hint: 'Account type',
            onChanged: (value) => setState(() => _accountType = value),
          ),
          const SizedBox(height: 12),
          RegisterField(controller: _holder, hint: 'Account holder name'),
          const SizedBox(height: 12),
          RegisterField(controller: _accountNumber, hint: 'Account number', keyboard: TextInputType.number),
          const SizedBox(height: 12),
          RegisterField(controller: _iban, hint: 'IBAN'),
          const SizedBox(height: 12),
          RegisterField(controller: _swift, hint: 'SWIFT / BIC'),
          const SizedBox(height: 12),
          RegisterField(controller: _branch, hint: 'Branch name'),
          const SizedBox(height: 12),
          RegisterField(controller: _branchCode, hint: 'Branch code'),
        ] else if (_payoutMethod == 'wallet') ...[
          RegisterField(
            controller: _walletPhone,
            hint: 'Wallet phone 010 / 011 / 012 / 015',
            keyboard: TextInputType.phone,
            digitsOnly: true,
            maxLength: 11,
          ),
          const SizedBox(height: 12),
          const Text('Wallet issued by', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          RegisterChoiceChips(
            options: const [
              CatalogOption(id: 'telecom', label: 'Egyptian telecom company'),
              CatalogOption(id: 'bank', label: 'Bank'),
            ],
            selected: _walletNetwork,
            onSelect: (value) => setState(() => _walletNetwork = value),
          ),
          const SizedBox(height: 12),
          if (_walletNetwork == 'telecom')
            RegisterDropdown(value: _walletTelecom, options: _catalog.telecomChoices, hint: 'Telecom company', onChanged: (value) => setState(() => _walletTelecom = value)),
          if (_walletNetwork == 'bank')
            RegisterDropdown(value: _walletBank, options: _catalog.bankChoices, hint: 'Bank', onChanged: (value) => setState(() => _walletBank = value)),
        ] else if (_payoutMethod == 'paypal') ...[
          RegisterField(controller: _paypalEmail, hint: 'PayPal email', keyboard: TextInputType.emailAddress),
          const SizedBox(height: 12),
          RegisterField(controller: _paypalName, hint: 'PayPal account name'),
        ],
        if (_payoutMethod != null) ...[
          const SizedBox(height: 12),
          RegisterField(controller: _transferNotes, hint: 'Other transfer details', maxLines: 3),
        ],
      ],
    );
  }

  int _ageYears() {
    final dob = _dob;
    if (dob == null) {
      return 0;
    }
    final now = DateTime.now();
    var years = now.year - dob.year;
    if (DateTime(now.year, dob.month, dob.day).isAfter(now)) {
      years -= 1;
    }
    return years;
  }

  Future<void> _pickMapAddress() async {
    final pin = await Navigator.of(context).push<PickedMapLocation>(
      MaterialPageRoute(
        builder: (_) => BookMapPickerPage(
          initial: _latitude != null && _longitude != null
              ? PickedMapLocation(
                  label: _address.text.trim().isEmpty ? 'Pinned location' : _address.text.trim(),
                  latitude: _latitude!,
                  longitude: _longitude!,
                )
              : null,
        ),
      ),
    );
    if (pin == null || !mounted) {
      return;
    }
    setState(() {
      _address.text = pin.label;
      _latitude = pin.latitude;
      _longitude = pin.longitude;
    });
  }

  Future<void> _pickIdentityImage(ValueChanged<XFile> onPicked) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Add photo', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
              const SizedBox(height: 14),
              ListTile(
                leading: const Icon(Icons.photo_camera_outlined, color: LensColors.primary),
                title: const Text('Take a photo', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                onTap: () => Navigator.pop(context, ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_outlined, color: LensColors.primary),
                title: const Text('Choose from gallery', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                onTap: () => Navigator.pop(context, ImageSource.gallery),
              ),
            ],
          ),
        );
      },
    );
    if (source == null) {
      return;
    }
    try {
      final picked = await ImagePicker().pickImage(source: source, imageQuality: 85, maxWidth: 1600);
      if (picked == null || !mounted) {
        return;
      }
      onPicked(picked);
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Could not open the photo picker on this device.');
      }
    }
  }

  Widget _mapAddressTile() {
    final hasPin = _address.text.trim().isNotEmpty || _latitude != null;
    return Material(
      color: const Color(0xE6121214),
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        onTap: _pickMapAddress,
        borderRadius: BorderRadius.circular(22),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
          child: Row(
            children: [
              const Icon(Icons.map_outlined, color: LensColors.primary),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      hasPin ? _address.text.trim() : 'Pin address on Google Maps',
                      style: TextStyle(
                        color: hasPin ? Colors.white : const Color(0xFF8E8B84),
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _latitude == null
                          ? 'Same map pin stored as Address, Latitude, and Longitude in admin.'
                          : '${_latitude!.toStringAsFixed(5)}, ${_longitude!.toStringAsFixed(5)}',
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _imageField({
    required String label,
    required String hint,
    required IconData icon,
    required XFile? file,
    required String? url,
    required ValueChanged<XFile> onPick,
    required double height,
  }) {
    ImageProvider? image;
    if (file != null) {
      image = FileImage(File(file.path));
    } else if (url != null && url.isNotEmpty && LensConfig.useNetwork) {
      image = NetworkImage(url);
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
        const SizedBox(height: 6),
        Text(hint, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5, height: 1.35)),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: () => _pickIdentityImage(onPick),
          child: Container(
            height: height,
            width: double.infinity,
            decoration: BoxDecoration(
              color: const Color(0xE6121214),
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: const Color(0xFF2A2A2E)),
              image: image == null ? null : DecorationImage(image: image, fit: BoxFit.cover),
            ),
            child: image == null
                ? Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(icon, color: LensColors.primary),
                      const SizedBox(height: 8),
                      Text('Add $label', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                    ],
                  )
                : Align(
                    alignment: Alignment.bottomRight,
                    child: Padding(
                      padding: const EdgeInsets.all(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(color: const Color(0xCC121214), borderRadius: BorderRadius.circular(99)),
                        child: const Text('Change', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12)),
                      ),
                    ),
                  ),
          ),
        ),
      ],
    );
  }

  Widget _dateField() {
    final label = _dob == null ? 'Date of birth' : '${_dob!.day}/${_dob!.month}/${_dob!.year}';
    return GestureDetector(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          firstDate: DateTime(1950),
          lastDate: DateTime.now().subtract(const Duration(days: 365 * 16)),
          initialDate: _dob ?? DateTime(1995, 1, 1),
        );
        if (picked != null) {
          setState(() => _dob = picked);
        }
      },
      child: Container(
        height: 56,
        padding: const EdgeInsets.symmetric(horizontal: 18),
        decoration: BoxDecoration(
          color: const Color(0xE6121214),
          borderRadius: BorderRadius.circular(99),
          border: Border.all(color: const Color(0xFF2A2A2E)),
        ),
        child: Row(
          children: [
            const Icon(Icons.cake_outlined, color: Color(0xFF8E8B84)),
            const SizedBox(width: 12),
            Text(label, style: TextStyle(color: _dob == null ? const Color(0xFF8E8B84) : Colors.white, fontSize: 15)),
          ],
        ),
      ),
    );
  }
}

class _ExistingFile {
  const _ExistingFile({required this.id, required this.name});

  final int id;
  final String name;
}

class _ProjectDraft {
  _ProjectDraft();

  factory _ProjectDraft.fromJson(Map<String, dynamic> json) {
    final draft = _ProjectDraft()
      ..type = json['type']?.toString() ?? 'image'
      ..featured = json['is_featured'] == true;
    draft.title.text = json['title']?.toString() ?? '';
    draft.completedOn.text = json['completed_on']?.toString() ?? '';
    draft.url.text = json['external_url']?.toString() ?? '';
    draft.description.text = json['description']?.toString() ?? '';
    draft.ids = (json['ids'] as List<dynamic>? ?? const [])
        .map((item) => item is int ? item : int.tryParse('$item') ?? 0)
        .where((id) => id > 0)
        .toList();
    draft.existing = (json['files'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) {
          final map = Map<String, dynamic>.from(item);
          final id = map['id'] is int ? map['id'] as int : int.tryParse('${map['id']}') ?? 0;
          return _ExistingFile(id: id, name: map['name']?.toString() ?? 'File');
        })
        .where((item) => item.id > 0)
        .toList();
    return draft;
  }

  final title = TextEditingController();
  final completedOn = TextEditingController();
  final url = TextEditingController();
  final description = TextEditingController();
  final files = <XFile>[];
  List<int> ids = [];
  List<_ExistingFile> existing = [];
  String type = 'image';
  bool featured = false;

  void dispose() {
    title.dispose();
    completedOn.dispose();
    url.dispose();
    description.dispose();
  }
}
