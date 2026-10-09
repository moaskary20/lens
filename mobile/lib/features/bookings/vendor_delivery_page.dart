import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/theme/lens_colors.dart';

class VendorDeliveryPage extends StatefulWidget {
  const VendorDeliveryPage({
    super.key,
    required this.bookingId,
    required this.projectName,
  });

  final int bookingId;
  final String projectName;

  @override
  State<VendorDeliveryPage> createState() => _VendorDeliveryPageState();
}

class _VendorDeliveryPageState extends State<VendorDeliveryPage> {
  final List<PlatformFile> _files = [];
  bool _busy = false;

  Future<void> _pickFiles() async {
    try {
      final result = await FilePicker.platform.pickFiles(
        allowMultiple: true,
        withData: kIsWeb,
      );
      if (result == null || result.files.isEmpty || !mounted) {
        return;
      }
      final tooLarge = result.files
          .where((file) => file.size > 50 * 1024 * 1024)
          .toList();
      if (tooLarge.isNotEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Each file must be 50 MB or smaller.')),
        );
        return;
      }
      setState(() {
        for (final file in result.files) {
          if (!_files.any(
            (selected) =>
                selected.name == file.name && selected.size == file.size,
          )) {
            _files.add(file);
          }
        }
      });
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not open the file picker.')),
        );
      }
    }
  }

  Future<void> _upload() async {
    if (_files.isEmpty || _busy) {
      return;
    }
    setState(() => _busy = true);
    try {
      final parts = <http.MultipartFile>[];
      for (var index = 0; index < _files.length; index++) {
        final file = _files[index];
        if (file.bytes != null) {
          parts.add(
            http.MultipartFile.fromBytes(
              'files[$index]',
              file.bytes!,
              filename: file.name,
            ),
          );
        } else if (file.path != null) {
          parts.add(
            await http.MultipartFile.fromPath(
              'files[$index]',
              file.path!,
              filename: file.name,
            ),
          );
        } else {
          throw StateError('Could not read ${file.name}.');
        }
      }
      final result = await ApiClient().postForm(
        '/app/bookings/${widget.bookingId}/deliverables',
        const {},
        files: parts,
      );
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            result['message']?.toString() ?? 'Files delivered to your client.',
          ),
        ),
      );
      Navigator.of(context).pop(true);
    } catch (error) {
      if (mounted) {
        setState(() => _busy = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(error.toString().replaceFirst('ApiException: ', '')),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      appBar: AppBar(
        backgroundColor: LensColors.charcoal,
        foregroundColor: LensColors.cream,
        title: const Text('Project delivery'),
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.projectName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 21,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 7),
                  const Text(
                    'Upload the finished project files. Your client will be notified when the delivery is ready.',
                    style: TextStyle(color: LensColors.slate, height: 1.45),
                  ),
                ],
              ),
            ),
            Expanded(
              child: _files.isEmpty
                  ? Center(
                      child: OutlinedButton.icon(
                        onPressed: _busy ? null : _pickFiles,
                        icon: const Icon(Icons.add_rounded),
                        label: const Text('Choose project files'),
                      ),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      itemCount: _files.length,
                      separatorBuilder: (context, index) =>
                          const SizedBox(height: 9),
                      itemBuilder: (context, index) {
                        final file = _files[index];
                        return Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 12,
                          ),
                          decoration: BoxDecoration(
                            color: const Color(0xFF191A1F),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFF2A2D34)),
                          ),
                          child: Row(
                            children: [
                              const Icon(
                                Icons.insert_drive_file_outlined,
                                color: LensColors.primary,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      file.name,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 3),
                                    Text(
                                      '${(file.size / (1024 * 1024)).toStringAsFixed(1)} MB',
                                      style: const TextStyle(
                                        color: LensColors.slate,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              IconButton(
                                tooltip: 'Remove file',
                                onPressed: _busy
                                    ? null
                                    : () => setState(
                                        () => _files.removeAt(index),
                                      ),
                                icon: const Icon(
                                  Icons.close_rounded,
                                  color: LensColors.slate,
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 18),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _busy ? null : _pickFiles,
                      icon: const Icon(Icons.attach_file_rounded),
                      label: const Text('Add files'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _busy || _files.isEmpty ? null : _upload,
                      icon: _busy
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.cloud_upload_outlined),
                      label: Text(_busy ? 'Uploading' : 'Deliver'),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
