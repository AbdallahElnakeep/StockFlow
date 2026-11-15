import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../../../core/di/injection.dart';
import '../../../domain/repo/auth_repo.dart';
import 'login_event.dart';
import 'login_state.dart';

class LoginBloc extends Bloc<LoginEvent, LoginState> {
  final AuthRepo _repo = sl();

  LoginBloc() : super(const LoginInitialState()) {
    on<SubmitLoginEvent>(_onSubmit);
    on<CheckSessionEvent>(_onCheckSession);
  }

  Future<void> _onSubmit(SubmitLoginEvent event, Emitter<LoginState> emit) async {
    emit(const LoginLoadingState());

    final result = await _repo.login(event.phone, event.password, 'rep-device');

    result.fold(
      (failure) => emit(LoginErrorState(failure.message)),
      (session) async {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setInt('user_id', session.userId);
        await prefs.setString('user_name', session.name);
        await prefs.setBool('user_is_rep', session.isRep);
        emit(const LoginSuccessState());
      },
    );
  }

  Future<void> _onCheckSession(CheckSessionEvent event, Emitter<LoginState> emit) async {
    final session = await _repo.savedSession();
    session != null ? emit(const SessionRestoredState()) : emit(const NoSessionState());
  }
}
