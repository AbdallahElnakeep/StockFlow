import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';

class CustomersView extends StatelessWidget {
  const CustomersView({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<BootstrapBloc, BootstrapState>(
      bloc: sl<BootstrapBloc>(),
      builder: (context, state) {
        if (state is! BootstrapLoaded) {
          return Scaffold(
            appBar: AppBar(title: Text('customers'.tr())),
            body: const Center(child: CircularProgressIndicator()),
          );
        }

        final customers = state.data.customers;

        return Scaffold(
          appBar: AppBar(title: Text('customers'.tr())),
          body: customers.isEmpty
              ? Center(child: Text('no_customers'.tr(), style: const TextStyle(color: AppColors.text3)))
              : ListView.separated(
                  itemCount: customers.length,
                  separatorBuilder: (_, __) => const Divider(),
                  itemBuilder: (context, index) {
                    final customer = customers[index];
                    return ListTile(
                      title: Text(customer.name),
                      subtitle: customer.area != null ? Text(customer.area!) : null,
                      trailing: customer.balance > 0
                          ? Text(customer.balance.toStringAsFixed(2), style: const TextStyle(color: AppColors.error, fontWeight: FontWeight.w600))
                          : const Icon(Icons.chevron_left, color: AppColors.text3),
                      onTap: () => context.go('/home/customers/${customer.id}'),
                    );
                  },
                ),
        );
      },
    );
  }
}
