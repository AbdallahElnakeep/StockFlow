import 'package:flutter_bloc/flutter_bloc.dart';
import '../../domain/entity/bootstrap_data.dart';
import '../../domain/repo/bootstrap_repo.dart';

sealed class BootstrapEvent {
  const BootstrapEvent();
}

final class LoadBootstrap extends BootstrapEvent {
  const LoadBootstrap();
}

sealed class BootstrapState {
  const BootstrapState();
}

final class BootstrapLoading extends BootstrapState {
  const BootstrapLoading();
}

final class BootstrapLoaded extends BootstrapState {
  final BootstrapData data;
  final bool fromCache;
  const BootstrapLoaded(this.data, {this.fromCache = false});
}

final class BootstrapEmpty extends BootstrapState {
  const BootstrapEmpty();
}

class BootstrapBloc extends Bloc<BootstrapEvent, BootstrapState> {
  final BootstrapRepo _repo;

  BootstrapBloc(this._repo) : super(const BootstrapLoading()) {
    on<LoadBootstrap>(_onLoad);
  }

  Future<void> _onLoad(LoadBootstrap event, Emitter<BootstrapState> emit) async {
    final remote = await _repo.fetchRemote();

    await remote.fold(
      (failure) async {
        final cached = await _repo.loadCached();
        emit(cached != null ? BootstrapLoaded(cached, fromCache: true) : const BootstrapEmpty());
      },
      (data) async => emit(BootstrapLoaded(data)),
    );
  }
}
