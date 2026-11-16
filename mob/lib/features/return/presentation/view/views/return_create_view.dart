import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';
import '../../managers/return_create/return_create_bloc.dart';
import '../../managers/return_create/return_create_event.dart';
import '../../managers/return_create/return_create_state.dart';

class ReturnCreateView extends StatelessWidget {
  final int customerId;

  const ReturnCreateView({super.key, required this.customerId});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ReturnCreateBloc(sl(), sl<BootstrapBloc>()),
      child: ReturnCreateScreen(customerId: customerId),
    );
  }
}

class ReturnCreateScreen extends StatefulWidget {
  final int customerId;

  const ReturnCreateScreen({super.key, required this.customerId});

  @override
  State<ReturnCreateScreen> createState() => _ReturnCreateScreenState();
}

class _ReturnCreateScreenState extends State<ReturnCreateScreen> {
  final Map<int, double> _qtys = {};
  bool _restock = true;

  @override
  Widget build(BuildContext context) {
    final state = sl<BootstrapBloc>().state;

    if (state is! BootstrapLoaded) {
      return Scaffold(appBar: AppBar(title: Text('new_return'.tr())), body: const Center(child: CircularProgressIndicator()));
    }

    final products = state.data.products;

    return Scaffold(
      appBar: AppBar(title: Text('new_return'.tr())),
      body: Column(
        children: [
          SwitchListTile(
            title: Text('restock'.tr()),
            subtitle: Text(_restock ? 'restock_yes'.tr() : 'restock_no'.tr()),
            value: _restock,
            onChanged: (v) => setState(() => _restock = v),
          ),
          Expanded(
            child: ListView.separated(
              itemCount: products.length,
              separatorBuilder: (_, __) => const Divider(),
              itemBuilder: (context, index) {
                final product = products[index];
                return ListTile(
                  title: Text(product.name),
                  subtitle: Text('${'price'.tr()}: ${product.salePrice.toStringAsFixed(2)}'),
                  trailing: SizedBox(
                    width: 110,
                    child: TextField(
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(hintText: '0'),
                      textAlign: TextAlign.center,
                      onChanged: (v) => setState(() => _qtys[product.id] = double.tryParse(v) ?? 0),
                    ),
                  ),
                );
              },
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: BlocConsumer<ReturnCreateBloc, ReturnCreateState>(
                listener: (context, state) {
                  if (state is ReturnQueued) {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(state.sent ? 'saved_synced'.tr() : 'saved_offline'.tr())));
                    context.pop(true);
                  }
                  if (state is ReturnInvalid) {
                    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                      content: Text(state.message),
                      backgroundColor: AppColors.error,
                    ));
                  }
                },
                builder: (context, state) {
                  final hasQty = _qtys.values.any((q) => q > 0);
                  return ElevatedButton(
                    onPressed: state is ReturnSubmitting || !hasQty
                        ? null
                        : () => context.read<ReturnCreateBloc>().add(SubmitReturnEvent(
                              customerId: widget.customerId,
                              restock: _restock,
                              items: _qtys.entries
                                  .where((e) => e.value > 0)
                                  .map((e) {
                                final product = products.firstWhere((p) => p.id == e.key);
                                return {'product_id': e.key, 'qty': e.value, 'unit_price': product.salePrice};
                              }).toList(),
                            )),
                    child: state is ReturnSubmitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : Text('save'.tr()),
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}
