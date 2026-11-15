import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../bootstrap/presentation/managers/bootstrap_bloc.dart';
import '../../../../sync/domain/repo/sync_repo.dart';
import 'return_create_event.dart';
import 'return_create_state.dart';

class ReturnCreateBloc extends Bloc<ReturnCreateEvent, ReturnCreateState> {
  final SyncRepo _syncRepo;
  final BootstrapBloc _bootstrapBloc;

  ReturnCreateBloc(this._syncRepo, this._bootstrapBloc) : super(ReturnCreateInitial()) {
    on<SubmitReturnEvent>(_onSubmit);
  }

  Future<void> _onSubmit(SubmitReturnEvent event, Emitter<ReturnCreateState> emit) async {
    if (event.items.isEmpty) {
      emit(ReturnInvalid('اختر صنفًا واحدًا على الأقل'));
      return;
    }

    emit(ReturnSubmitting());

    await _syncRepo.enqueue('return', {
      'customer_id': event.customerId,
      'restock': event.restock,
      'issued_at': DateTime.now().toIso8601String(),
      'items': event.items,
    });

    final flush = await _syncRepo.flush();

    flush.fold(
      (failure) => emit(ReturnQueued(false)),
      (results) => emit(ReturnQueued(true)),
    );

    _bootstrapBloc.add(const LoadBootstrap());
  }
}
