import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/router/app_routes.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../../bootstrap/domain/entity/bootstrap_data.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';
import '../../../../../../core/di/injection.dart';

class CustomerDetailView extends StatelessWidget {
  final int id;

  const CustomerDetailView({super.key, required this.id});

  CustomerItem? _find() {
    final bloc = sl<BootstrapBloc>();
    final state = bloc.state;
    if (state is BootstrapLoaded) {
      final matches = state.data.customers.where((c) => c.id == id);
      if (matches.isNotEmpty) return matches.first;
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final customer = _find();
    final routes = sl<AppRoutes>();

    if (customer == null) {
      return Scaffold(appBar: AppBar(), body: Center(child: Text('not_found'.tr())));
    }

    return Scaffold(
      appBar: AppBar(title: Text(customer.name)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (customer.balance > 0)
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: AppColors.errorBg, borderRadius: BorderRadius.circular(10)),
              child: Row(
                children: [
                  const Icon(Icons.account_balance_wallet, color: AppColors.error),
                  const SizedBox(width: 10),
                  Text('${'debt'.tr()}: ${customer.balance.toStringAsFixed(2)}',
                      style: const TextStyle(color: AppColors.error, fontWeight: FontWeight.w700, fontSize: 15)),
                ],
              ),
            ),
          const SizedBox(height: 16),
          _Action(icon: Icons.receipt_long, label: 'new_invoice'.tr(), onTap: () => context.go('${routes.home}/invoice/$id')),
          const SizedBox(height: 10),
          _Action(icon: Icons.payments, label: 'new_payment'.tr(), onTap: () => context.go('${routes.home}/payment/$id')),
          const SizedBox(height: 10),
          _Action(icon: Icons.undo, label: 'new_return'.tr(), onTap: () => context.go('${routes.home}/return/$id')),
          const SizedBox(height: 10),
          _Action(icon: Icons.pin_drop, label: 'visit'.tr(), onTap: () => context.go('${routes.home}/visit/$id')),
          const SizedBox(height: 24),
          if (customer.phone != null) ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.phone), title: Text(customer.phone!)),
          if (customer.address != null) ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.location_on), title: Text(customer.address!)),
        ],
      ),
    );
  }
}

class _Action extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _Action({required this.icon, required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: Icon(icon, color: AppColors.brand),
        title: Text(label),
        trailing: const Icon(Icons.chevron_left, color: AppColors.text3),
        onTap: onTap,
      ),
    );
  }
}
