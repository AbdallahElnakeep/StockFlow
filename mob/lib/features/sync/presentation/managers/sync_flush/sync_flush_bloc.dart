import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../domain/repo/sync_repo.dart';
import 'sync_flush_event.dart';
import 'sync_flush_state.dart';

class SyncFlushBloc extends Bloc<SyncFlushEvent, SyncFlushState> {
  final SyncRepo _repo;

  SyncFlushBloc(this._repo) : super(const SyncFlushIdle(0)) {
    on<LoadPendingCount>(_onLoad);
    on<FlushNow>(_onFlush);
  }

  Future<void> _onLoad(LoadPendingCount event, Emitter<SyncFlushState> emit) async {
    emit(SyncFlushIdle(await _repo.pendingCount()));
  }

  Future<void> _onFlush(FlushNow event, Emitter<SyncFlushState> emit) async {
    emit(SyncFlushing(await _repo.pendingCount()));

    final result = await _repo.flush();

    await result.fold(
      (failure) async => emit(SyncOffline(await _repo.pendingCount())),
      (results) async {
        final rejected = results.where((r) => r.state == 'rejected').length;
        emit(SyncFlushDone(await _repo.pendingCount(), rejected));
      },
    );
  }
}
