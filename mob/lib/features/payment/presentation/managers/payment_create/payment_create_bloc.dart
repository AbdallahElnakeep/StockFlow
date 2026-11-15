import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';
import '../../../../sync/domain/repo/sync_repo.dart';
import 'payment_create_event.dart';
import 'payment_create_state.dart';

class PaymentCreateBloc extends Bloc<PaymentCreateEvent, PaymentCreateState> {
  final SyncRepo _syncRepo;
  final BootstrapBloc _bootstrapBloc;

  PaymentCreateBloc(this._syncRepo, this._bootstrapBloc) : super(PaymentCreateInitial()) {
    on<SubmitPaymentEvent>(_onSubmit);
  }

  Future<void> _onSubmit(SubmitPaymentEvent event, Emitter<PaymentCreateState> emit) async {
    if (event.amount <= 0) {
      emit(PaymentInvalid('أدخل مبلغًا صحيحًا'));
      return;
    }

    emit(PaymentSubmitting());

    await _syncRepo.enqueue('payment', {
      'customer_id': event.customerId,
      'amount': event.amount,
      'note': event.note,
      'issued_at': DateTime.now().toIso8601String(),
    });

    final flush = await _syncRepo.flush();

    flush.fold(
      (failure) => emit(PaymentQueued(false)),
      (results) => emit(PaymentQueued(true)),
    );

    _bootstrapBloc.add(const LoadBootstrap());
  }
}
