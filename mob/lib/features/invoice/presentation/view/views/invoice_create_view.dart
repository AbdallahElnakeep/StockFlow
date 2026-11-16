import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';
import '../../../../sync/domain/repo/sync_repo.dart';
import '../../managers/invoice_create/invoice_create_bloc.dart';
import '../../managers/invoice_create/invoice_create_event.dart';
import '../../managers/invoice_create/invoice_create_state.dart';

class InvoiceCreateView extends StatelessWidget {
  final int customerId;

  const InvoiceCreateView({super.key, required this.customerId});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => InvoiceCreateBloc(sl<SyncRepo>(), sl<BootstrapBloc>()),
      child: InvoiceCreateScreen(customerId: customerId),
    );
  }
}

class InvoiceCreateScreen extends StatefulWidget {
  final int customerId;

  const InvoiceCreateScreen({super.key, required this.customerId});

  @override
  State<InvoiceCreateScreen> createState() => _InvoiceCreateScreenState();
}

class _InvoiceCreateScreenState extends State<InvoiceCreateScreen> {
  final Map<int, double> _qtys = {};
  final Map<int, double> _prices = {};
  String _type = 'cash';

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<BootstrapBloc, BootstrapState>(
      bloc: sl<BootstrapBloc>(),
      builder: (context, state) {
        if (state is! BootstrapLoaded) {
          return Scaffold(appBar: AppBar(title: Text('new_invoice'.tr())), body: const Center(child: CircularProgressIndicator()));
        }

        final items = state.data.custodyBalances;
        final customer = state.data.customers.firstWhere((c) => c.id == widget.customerId);

        return Scaffold(
          appBar: AppBar(title: Text('${'new_invoice'.tr()} — ${customer.name}')),
          body: Column(
            children: [
              Padding(
                padding: const EdgeInsets.all(12),
                child: SegmentedButton<String>(
                  segments: [
                    ButtonSegment(value: 'cash', label: Text('cash'.tr())),
                    ButtonSegment(value: 'credit', label: Text('credit'.tr())),
                  ],
                  selected: {_type},
                  onSelectionChanged: (s) => setState(() => _type = s.first),
                ),
              ),
              Expanded(
                child: items.isEmpty
                    ? Center(child: Text('no_custody_stock'.tr(), style: const TextStyle(color: AppColors.text3)))
                    : ListView.separated(
                        itemCount: items.length,
                        separatorBuilder: (_, __) => const Divider(),
                        itemBuilder: (context, index) {
                          final item = items[index];
                          return ListTile(
                            title: Text(item.name),
                            subtitle: Text('${'available'.tr()}: ${item.qty.toStringAsFixed(0)}'),
                            trailing: SizedBox(
                              width: 110,
                              child: TextField(
                                keyboardType: TextInputType.number,
                                decoration: const InputDecoration(hintText: '0'),
                                textAlign: TextAlign.center,
                                onChanged: (v) => setState(() {
                                  final parsed = double.tryParse(v) ?? 0;
                                  _qtys[item.productId] = parsed;
                                  _prices[item.productId] = item.salePrice;
                                }),
                              ),
                            ),
                          );
                        },
                      ),
              ),
              SafeArea(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Row(
                    children: [
                      Expanded(child: Text('${'total'.tr()}: ${_total(_prices, _qtys).toStringAsFixed(2)}', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700))),
                      BlocConsumer<InvoiceCreateBloc, InvoiceCreateState>(
                        listener: (context, state) {
                          if (state is InvoiceQueued) {
                            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(state.sent ? 'saved_synced'.tr() : 'saved_offline'.tr())));
                            context.pop(true);
                          }
                          if (state is InvoiceCreateError) {
                            ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                              content: Text(state.message),
                              backgroundColor: AppColors.error,
                            ));
                          }
                        },
                        builder: (context, state) {
                          return ElevatedButton(
                            onPressed: state is InvoiceSubmitting || _total(_prices, _qtys) <= 0
                                ? null
                                : () => context.read<InvoiceCreateBloc>().add(SubmitInvoiceEvent(
                                      customerId: widget.customerId,
                                      type: _type,
                                      items: _qtys.entries
                                          .where((e) => e.value > 0)
                                          .map((e) => {'product_id': e.key, 'qty': e.value, 'unit_price': _prices[e.key] ?? 0})
                                          .toList(),
                                    )),
                            child: state is InvoiceSubmitting ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Text('save'.tr()),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  double _total(Map<int, double> prices, Map<int, double> qtys) {
    double total = 0;
    qtys.forEach((product, qty) => total += qty * (prices[product] ?? 0));
    return total;
  }
}
