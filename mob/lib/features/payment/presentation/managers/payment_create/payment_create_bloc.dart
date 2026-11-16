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

    final opUuid = await _syncRepo.enqueue('payment', {
      'customer_id': event.customerId,
      'amount': event.amount,
      'note': event.note,
      'issued_at': DateTime.now().toIso8601String(),
    });

    final flush = await _syncRepo.flush();

    flush.fold(
      (failure) => emit(PaymentQueued(false)),
      (results) {
        final own = results.where((r) => r.opUuid == opUuid).toList();
        if (own.isNotEmpty && own.first.state == 'rejected') {
          final reason = own.first.reason;
          emit(PaymentInvalid(reason is String && reason.isNotEmpty
              ? 'رُفضت من السيرفر: $reason'
              : 'رُفضت من السيرفر — راجع البيانات وأعد المحاولة'));
          return;
        }
        emit(PaymentQueued(true));
      },
    );

    _bootstrapBloc.add(const LoadBootstrap());
  }
}
