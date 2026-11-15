sealed class LoginState {
  const LoginState();
}

final class LoginInitialState extends LoginState {
  const LoginInitialState();
}

final class LoginLoadingState extends LoginState {
  const LoginLoadingState();
}

final class LoginSuccessState extends LoginState {
  const LoginSuccessState();
}

final class LoginErrorState extends LoginState {
  final String message;
  const LoginErrorState(this.message);
}

final class SessionRestoredState extends LoginState {
  const SessionRestoredState();
}

final class NoSessionState extends LoginState {
  const NoSessionState();
}
