import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:lens/core/egypt_phone.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/register_catalog.dart';
import 'package:lens/features/auth/register_scaffold.dart';

class VendorRegisterPage extends StatefulWidget {
  const VendorRegisterPage({super.key, required this.bootstrap, this.onSuccess});

  final Map<String, dynamic> bootstrap;
  final VoidCallback? onSuccess;

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
  int _step = 0;
  bool _busy = false;
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

  @override
  void initState() {
    super.initState();
    _catalog = RegisterCatalog.fromBootstrap(widget.bootstrap);
  }

  @override
  void dispose() {
    for (final controller in [
      _name, _email, _password, _phone, _displayName, _profession, _bio, _whatsapp, _instagram,
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

  Future<void> _continue() async {
    setState(() => _error = null);
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
        files: await _projectFiles(),
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
      'contact_email': _email.text.trim(),
      'whatsapp': EgyptPhone.digits(_whatsapp.text),
      'instagram': _instagram.text.trim(),
      'address': _address.text.trim(),
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

  Future<List<http.MultipartFile>> _projectFiles() async {
    final files = <http.MultipartFile>[];
    for (var index = 0; index < _projects.length; index++) {
      for (var order = 0; order < _projects[index].files.length; order++) {
        final file = _projects[index].files[order];
        files.add(http.MultipartFile.fromBytes(
          'projects[$index][files][$order]',
          await file.readAsBytes(),
          filename: file.name,
        ));
      }
    }
    return files;
  }

  @override
  Widget build(BuildContext context) {
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
        const SizedBox(height: 12),
        _dateField(),
        const SizedBox(height: 12),
        RegisterField(controller: _profession, hint: 'Profession / job title', icon: Icons.work_outline_rounded),
        const SizedBox(height: 12),
        RegisterField(controller: _bio, hint: 'About', maxLines: 4),
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
        RegisterField(controller: _address, hint: 'Address', icon: Icons.place_outlined, maxLines: 3),
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
          if (project.files.isNotEmpty) ...[
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
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

class _ProjectDraft {
  _ProjectDraft();

  final title = TextEditingController();
  final completedOn = TextEditingController();
  final url = TextEditingController();
  final description = TextEditingController();
  final files = <XFile>[];
  String type = 'image';
  bool featured = false;

  void dispose() {
    title.dispose();
    completedOn.dispose();
    url.dispose();
    description.dispose();
  }
}
