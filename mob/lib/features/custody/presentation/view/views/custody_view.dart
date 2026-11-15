import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';

class CustodyView extends StatelessWidget {
  const CustodyView({super.key});

  @override
  Widget build(BuildContext context) {
    final state = sl<BootstrapBloc>().state;

    if (state is! BootstrapLoaded) {
      return Scaffold(appBar: AppBar(title: Text('custody'.tr())), body: const Center(child: CircularProgressIndicator()));
    }

    final balances = state.data.custodyBalances.where((b) => b.qty > 0).toList();

    return Scaffold(
      appBar: AppBar(title: Text('custody'.tr())),
      body: balances.isEmpty
          ? Center(child: Text('custody_empty'.tr(), style: const TextStyle(color: AppColors.text3)))
          : ListView.separated(
              padding: const EdgeInsets.all(8),
              itemCount: balances.length,
              separatorBuilder: (_, __) => const Divider(),
              itemBuilder: (context, index) {
                final item = balances[index];
                return ListTile(
                  title: Text(item.name),
                  subtitle: Text('${'price'.tr()}: ${item.salePrice.toStringAsFixed(2)}'),
                  trailing: Text(item.qty.toStringAsFixed(0), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                );
              },
            ),
    );
  }
}
