import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';
import '../../managers/payment_create/payment_create_bloc.dart';
import '../../managers/payment_create/payment_create_event.dart';
import '../../managers/payment_create/payment_create_state.dart';

class PaymentCreateView extends StatelessWidget {
  final int customerId;

  const PaymentCreateView({super.key, required this.customerId});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => PaymentCreateBloc(sl(), sl<BootstrapBloc>()),
      child: PaymentCreateScreen(customerId: customerId),
    );
  }
}

class PaymentCreateScreen extends StatefulWidget {
  final int customerId;

  const PaymentCreateScreen({super.key, required this.customerId});

  @override
  State<PaymentCreateScreen> createState() => _PaymentCreateScreenState();
}

class _PaymentCreateScreenState extends State<PaymentCreateScreen> {
  final _amount = TextEditingController();
  final _note = TextEditingController();

  @override
  Widget build(BuildContext context) {
    final bloc = sl<BootstrapBloc>();
    final state = bloc.state;

    if (state is! BootstrapLoaded) {
      return Scaffold(appBar: AppBar(title: Text('new_payment'.tr())), body: const Center(child: CircularProgressIndicator()));
    }

    final customer = state.data.customers.firstWhere((c) => c.id == widget.customerId);

    return Scaffold(
      appBar: AppBar(title: Text('${'new_payment'.tr()} — ${customer.name}')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: AppColors.infoBg, borderRadius: BorderRadius.circular(10)),
              child: Text('${'debt'.tr()}: ${customer.balance.toStringAsFixed(2)}',
                  style: const TextStyle(color: AppColors.info, fontWeight: FontWeight.w700, fontSize: 15)),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(labelText: 'amount'.tr()),
            ),
            const SizedBox(height: 12),
            TextField(controller: _note, decoration: InputDecoration(labelText: 'note'.tr())),
            const Spacer(),
            BlocConsumer<PaymentCreateBloc, PaymentCreateState>(
              listener: (context, state) {
                if (state is PaymentQueued) {
                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(state.sent ? 'saved_synced'.tr() : 'saved_offline'.tr())));
                  context.pop(true);
                }
                if (state is PaymentInvalid) {
                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                    content: Text(state.message),
                    backgroundColor: AppColors.error,
                  ));
                }
              },
              builder: (context, state) {
                return ElevatedButton(
                  onPressed: state is PaymentSubmitting
                      ? null
                      : () => context.read<PaymentCreateBloc>().add(SubmitPaymentEvent(
                            customerId: widget.customerId,
                            amount: double.tryParse(_amount.text) ?? 0,
                            note: _note.text,
                          )),
                  child: state is PaymentSubmitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text('save'.tr()),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
