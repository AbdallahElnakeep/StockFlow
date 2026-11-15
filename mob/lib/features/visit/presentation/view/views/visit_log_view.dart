import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';
import '../../../../sync/domain/repo/sync_repo.dart';

class VisitLogView extends StatelessWidget {
  final int customerId;

  const VisitLogView({super.key, required this.customerId});

  @override
  Widget build(BuildContext context) {
    final state = sl<BootstrapBloc>().state;
    if (state is! BootstrapLoaded) {
      return Scaffold(appBar: AppBar(title: Text('visit'.tr())), body: const Center(child: CircularProgressIndicator()));
    }

    final customer = state.data.customers.firstWhere((c) => c.id == customerId);

    return Scaffold(
      appBar: AppBar(title: Text('${'visit'.tr()} — ${customer.name}')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _Option(
            label: 'visited'.tr(),
            sub: 'visited_sub'.tr(),
            color: AppColors.success,
            onTap: () => _log(context, 'visited'),
          ),
          const SizedBox(height: 10),
          _Option(
            label: 'no_answer'.tr(),
            sub: 'no_answer_sub'.tr(),
            color: AppColors.warning,
            onTap: () => _log(context, 'no_answer'),
          ),
          const SizedBox(height: 10),
          _Option(
            label: 'postponed'.tr(),
            sub: 'postponed_sub'.tr(),
            color: AppColors.info,
            onTap: () => _log(context, 'postponed'),
          ),
        ],
      ),
    );
  }

  Future<void> _log(BuildContext context, String status) async {
    await sl<SyncRepo>().enqueue('visit', {
      'customer_id': customerId,
      'status': status,
      'visited_at': DateTime.now().toIso8601String(),
    });
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('visit_saved'.tr())));
      context.pop(true);
    }
  }
}

class _Option extends StatelessWidget {
  final String label;
  final String sub;
  final Color color;
  final VoidCallback onTap;

  const _Option({required this.label, required this.sub, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: Icon(Icons.circle, color: color, size: 14),
        title: Text(label),
        subtitle: Text(sub),
        onTap: onTap,
      ),
    );
  }
}
