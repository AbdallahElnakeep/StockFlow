import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

sealed class ConnectivityEvent {
  const ConnectivityEvent();
}

final class StartListening extends ConnectivityEvent {
  const StartListening();
}

final class ConnectionChanged extends ConnectivityEvent {
  final bool online;
  const ConnectionChanged(this.online);
}

sealed class ConnectivityState {
  const ConnectivityState();
}

final class ConnectivityInitial extends ConnectivityState {
  const ConnectivityInitial();
}

final class ConnectivityOnline extends ConnectivityState {
  const ConnectivityOnline();
}

final class ConnectivityOffline extends ConnectivityState {
  const ConnectivityOffline();
}

class ConnectivityBloc extends Bloc<ConnectivityEvent, ConnectivityState> {
  StreamSubscription<List<ConnectivityResult>>? _subscription;

  ConnectivityBloc() : super(const ConnectivityInitial()) {
    on<StartListening>(_onStart);
    on<ConnectionChanged>(_onChange);
  }

  Future<void> _onStart(StartListening event, Emitter<ConnectivityState> emit) async {
    final results = await Connectivity().checkConnectivity();
    emit(results.any((r) => r != ConnectivityResult.none)
        ? const ConnectivityOnline()
        : const ConnectivityOffline());

    _subscription = Connectivity().onConnectivityChanged.listen(
      (results) => add(ConnectionChanged(
        results.any((r) => r != ConnectivityResult.none),
      )),
    );
  }

  void _onChange(ConnectionChanged event, Emitter<ConnectivityState> emit) {
    emit(event.online ? const ConnectivityOnline() : const ConnectivityOffline());
  }

  @override
  Future<void> close() {
    _subscription?.cancel();
    return super.close();
  }
}
