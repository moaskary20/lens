import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/register_catalog.dart';

class RegisterScaffold extends StatelessWidget {
  const RegisterScaffold({
    super.key,
    required this.title,
    required this.subtitle,
    required this.step,
    required this.total,
    required this.child,
    required this.onContinue,
    this.busy = false,
    this.error,
    this.onBack,
  });

  final String title;
  final String subtitle;
  final int step;
  final int total;
  final Widget child;
  final VoidCallback? onContinue;
  final bool busy;
  final String? error;
  final VoidCallback? onBack;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF070707),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 6, 20, 0),
              child: Row(
                children: [
                  IconButton(
                    onPressed: onBack ?? () => Navigator.of(context).maybePop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 28),
                  ),
                  const Expanded(
                    child: Text('Lens', style: TextStyle(color: LensColors.primary, fontSize: 28, fontWeight: FontWeight.w800, height: 1)),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 10),
              child: RegisterStepper(current: step, total: total),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.15)),
                    const SizedBox(height: 6),
                    Text(subtitle, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 14.5, height: 1.35)),
                    const SizedBox(height: 22),
                    child,
                    if (error != null) ...[
                      const SizedBox(height: 14),
                      Text(error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
                    ],
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
              child: SizedBox(
                height: 54,
                width: double.infinity,
                child: FilledButton(
                  onPressed: busy ? null : onContinue,
                  style: FilledButton.styleFrom(
                    backgroundColor: LensColors.primary,
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: LensColors.primary.withValues(alpha: 0.5),
                    elevation: 0,
                    shape: const StadiumBorder(),
                  ),
                  child: busy
                      ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Stack(
                          alignment: Alignment.center,
                          children: [
                            Text('Continue', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                            Align(alignment: Alignment.centerRight, child: Icon(Icons.chevron_right_rounded, size: 26)),
                          ],
                        ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class RegisterStepper extends StatelessWidget {
  const RegisterStepper({super.key, required this.current, required this.total});

  final int current;
  final int total;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        for (var index = 0; index < total; index++) ...[
          if (index > 0)
            Expanded(
              child: Container(
                height: 2,
                margin: const EdgeInsets.symmetric(horizontal: 6),
                color: index <= current ? LensColors.primary : const Color(0xFF2A2A2E),
              ),
            ),
          _StepDot(number: index + 1, state: index < current ? _StepState.done : (index == current ? _StepState.active : _StepState.todo)),
        ],
      ],
    );
  }
}

enum _StepState { todo, active, done }

class _StepDot extends StatelessWidget {
  const _StepDot({required this.number, required this.state});

  final int number;
  final _StepState state;

  @override
  Widget build(BuildContext context) {
    final active = state != _StepState.todo;
    return Container(
      width: 28,
      height: 28,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: active ? LensColors.primary : const Color(0xFF141210),
        shape: BoxShape.circle,
        border: Border.all(color: active ? LensColors.primary : const Color(0xFF2E2A26)),
      ),
      child: state == _StepState.done
          ? const Icon(Icons.check_rounded, color: Colors.white, size: 16)
          : Text(
              '$number',
              style: TextStyle(color: active ? Colors.white : const Color(0xFF8E8B84), fontWeight: FontWeight.w800, fontSize: 13),
            ),
    );
  }
}

class RegisterField extends StatelessWidget {
  const RegisterField({
    super.key,
    required this.controller,
    required this.hint,
    this.icon,
    this.secret = false,
    this.keyboard,
    this.suffix,
    this.maxLines = 1,
    this.maxLength,
    this.digitsOnly = false,
  });

  final TextEditingController controller;
  final String hint;
  final IconData? icon;
  final bool secret;
  final TextInputType? keyboard;
  final Widget? suffix;
  final int maxLines;
  final int? maxLength;
  final bool digitsOnly;

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      obscureText: secret,
      keyboardType: keyboard,
      maxLines: maxLines,
      minLines: maxLines,
      maxLength: maxLength,
      inputFormatters: [
        if (digitsOnly) FilteringTextInputFormatter.digitsOnly,
      ],
      style: const TextStyle(color: Colors.white, fontSize: 15),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w500),
        prefixIcon: icon == null ? null : Icon(icon, color: const Color(0xFF8E8B84)),
        suffixIcon: suffix,
        counterText: '',
        filled: true,
        fillColor: const Color(0xE6121214),
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(maxLines > 1 ? 22 : 99),
          borderSide: const BorderSide(color: Color(0xFF2A2A2E)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(maxLines > 1 ? 22 : 99),
          borderSide: const BorderSide(color: LensColors.primary),
        ),
      ),
    );
  }
}

class RegisterChoiceChips extends StatelessWidget {
  const RegisterChoiceChips({
    super.key,
    required this.options,
    required this.selected,
    required this.onSelect,
  });

  final List<CatalogOption> options;
  final String? selected;
  final ValueChanged<String> onSelect;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final option in options)
          GestureDetector(
            onTap: () => onSelect(option.id),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: selected == option.id ? const Color(0xFF1A120C) : const Color(0xE6121214),
                borderRadius: BorderRadius.circular(99),
                border: Border.all(color: selected == option.id ? LensColors.primary : const Color(0xFF2A2A2E)),
              ),
              child: Text(
                option.label,
                style: TextStyle(
                  color: selected == option.id ? LensColors.primary : Colors.white,
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class RegisterMultiChips extends StatelessWidget {
  const RegisterMultiChips({
    super.key,
    required this.options,
    required this.selected,
    required this.onChanged,
  });

  final List<CatalogOption> options;
  final List<String> selected;
  final ValueChanged<List<String>> onChanged;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final option in options)
          GestureDetector(
            onTap: () {
              final next = [...selected];
              if (next.contains(option.id)) {
                next.remove(option.id);
              } else {
                next.add(option.id);
              }
              onChanged(next);
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: selected.contains(option.id) ? const Color(0xFF1A120C) : const Color(0xE6121214),
                borderRadius: BorderRadius.circular(99),
                border: Border.all(color: selected.contains(option.id) ? LensColors.primary : const Color(0xFF2A2A2E)),
              ),
              child: Text(
                option.label,
                style: TextStyle(
                  color: selected.contains(option.id) ? LensColors.primary : Colors.white,
                  fontWeight: FontWeight.w700,
                  fontSize: 13,
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class RegisterTagField extends StatefulWidget {
  const RegisterTagField({
    super.key,
    required this.values,
    required this.onChanged,
    this.hint = 'Add and press enter',
  });

  final List<String> values;
  final ValueChanged<List<String>> onChanged;
  final String hint;

  @override
  State<RegisterTagField> createState() => _RegisterTagFieldState();
}

class _RegisterTagFieldState extends State<RegisterTagField> {
  final _input = TextEditingController();

  @override
  void dispose() {
    _input.dispose();
    super.dispose();
  }

  void _add() {
    final value = _input.text.trim();
    if (value.isEmpty || widget.values.contains(value)) {
      return;
    }
    widget.onChanged([...widget.values, value]);
    _input.clear();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextField(
          controller: _input,
          style: const TextStyle(color: Colors.white, fontSize: 15),
          textInputAction: TextInputAction.done,
          onSubmitted: (_) => _add(),
          decoration: InputDecoration(
            hintText: widget.hint,
            hintStyle: const TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w500),
            suffixIcon: IconButton(
              onPressed: _add,
              icon: const Icon(Icons.add_rounded, color: LensColors.primary),
            ),
            filled: true,
            fillColor: const Color(0xE6121214),
            contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(99),
              borderSide: const BorderSide(color: Color(0xFF2A2A2E)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(99),
              borderSide: const BorderSide(color: LensColors.primary),
            ),
          ),
        ),
        if (widget.values.isNotEmpty) ...[
          const SizedBox(height: 10),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final value in widget.values)
                GestureDetector(
                  onTap: () => widget.onChanged(widget.values.where((item) => item != value).toList()),
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
                        Text(value, style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 13)),
                        const SizedBox(width: 6),
                        const Icon(Icons.close_rounded, color: LensColors.primary, size: 14),
                      ],
                    ),
                  ),
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class RegisterDropdown extends StatelessWidget {
  const RegisterDropdown({
    super.key,
    required this.value,
    required this.options,
    required this.hint,
    required this.onChanged,
  });

  final String? value;
  final List<CatalogOption> options;
  final String hint;
  final ValueChanged<String?> onChanged;

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<String>(
      // ignore: deprecated_member_use
      value: options.any((item) => item.id == value) ? value : null,
      items: [
        for (final option in options)
          DropdownMenuItem(value: option.id, child: Text(option.label)),
      ],
      onChanged: onChanged,
      dropdownColor: const Color(0xFF141210),
      style: const TextStyle(color: Colors.white, fontSize: 15),
      icon: const Icon(Icons.expand_more_rounded, color: Color(0xFF8E8B84)),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w500),
        filled: true,
        fillColor: const Color(0xE6121214),
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(99),
          borderSide: const BorderSide(color: Color(0xFF2A2A2E)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(99),
          borderSide: const BorderSide(color: LensColors.primary),
        ),
      ),
    );
  }
}
