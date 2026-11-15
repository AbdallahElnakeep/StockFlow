import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import '../../../../core/theme/app_theme.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ServerUrlField extends StatefulWidget {
  const ServerUrlField({super.key});

  @override
  State<ServerUrlField> createState() => _ServerUrlFieldState();
}

class _ServerUrlFieldState extends State<ServerUrlField> {
  final _controller = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    final current = prefs.getString('base_url');
    _controller.text = current ?? 'http://10.0.2.2:8000';
  }

  Future<void> _save() async {
    final prefs = await SharedPreferences.getInstance();
    var url = _controller.text.trim().replaceAll(RegExp(r'/+$'), '');
    if (!url.startsWith('http')) {
      url = 'http://$url';
    }
    await prefs.setString('base_url', url);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('server_saved'.tr()), backgroundColor: AppColors.success),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        GestureDetector(
          onTap: () => _showSheet(context),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.dns_outlined, size: 14, color: AppColors.text3),
              const SizedBox(width: 5),
              Text(
                'server_settings'.tr(),
                style: const TextStyle(fontSize: 12, color: AppColors.text3),
              ),
            ],
          ),
        ),
      ],
    );
  }

  void _showSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (sheetContext) => Padding(
        padding: EdgeInsets.only(
          left: 20, right: 20, top: 20,
          bottom: MediaQuery.of(sheetContext).viewInsets.bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('server_url'.tr(), style: const TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            Text('server_url_hint'.tr(), style: const TextStyle(fontSize: 12.5, color: AppColors.text3)),
            const SizedBox(height: 12),
            TextField(
              controller: _controller,
              keyboardType: TextInputType.url,
              decoration: const InputDecoration(hintText: 'http://192.168.1.x:8000'),
            ),
            const SizedBox(height: 12),
            ElevatedButton(onPressed: () { _save(); Navigator.pop(sheetContext); }, child: Text('save'.tr())),
          ],
        ),
      ),
    );
  }
}
