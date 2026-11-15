import 'package:equatable/equatable.dart';

sealed class LoginEvent extends Equatable {
  const LoginEvent();
}

final class SubmitLoginEvent extends LoginEvent {
  final String phone;
  final String password;

  const SubmitLoginEvent(this.phone, this.password);

  @override
  List<Object?> get props => [phone, password];
}

final class CheckSessionEvent extends LoginEvent {
  const CheckSessionEvent();
  @override
  List<Object?> get props => [];
}
