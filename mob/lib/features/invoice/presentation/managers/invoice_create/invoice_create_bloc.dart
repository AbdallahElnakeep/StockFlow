import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../sync/domain/repo/sync_repo.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart' as b;
import 'invoice_create_event.dart';
import 'invoice_create_state.dart';

class InvoiceCreateBloc extends Bloc<InvoiceCreateEvent, InvoiceCreateState> {
  final SyncRepo _syncRepo;
  final b.BootstrapBloc _bootstrapBloc;

  InvoiceCreateBloc(this._syncRepo, this._bootstrapBloc) : super(InvoiceCreateInitial()) {
    on<SubmitInvoiceEvent>(_onSubmit);
  }

  Future<void> _onSubmit(SubmitInvoiceEvent event, Emitter<InvoiceCreateState> emit) async {
    if (event.items.isEmpty) {
      emit(InvoiceCreateError('اختر صنفًا واحدًا على الأقل'));
      return;
    }
    if (event.items.any((i) => (i['qty'] as num? ?? 0) <= 0)) {
      emit(InvoiceCreateError('كل الكميات يجب أن تكون أكبر من صفر'));
      return;
    }

    emit(InvoiceSubmitting());

    final opUuid = await _syncRepo.enqueue('invoice', {
      'customer_id': event.customerId,
      'type': event.type,
      'issued_at': DateTime.now().toIso8601String(),
      'items': event.items,
    });

    final flush = await _syncRepo.flush();

    flush.fold(
      (failure) => emit(InvoiceQueued(false)),
      (results) {
        final own = results.where((r) => r.opUuid == opUuid).toList();
        if (own.isNotEmpty && own.first.state == 'rejected') {
          final reason = own.first.reason;
          emit(InvoiceCreateError(reason is String && reason.isNotEmpty
              ? 'رُفضت من السيرفر: $reason'
              : 'رُفضت من السيرفر — راجع البيانات وأعد المحاولة'));
          return;
        }
        emit(InvoiceQueued(true));
      },
    );

    _bootstrapBloc.add(const b.LoadBootstrap());
  }
}
