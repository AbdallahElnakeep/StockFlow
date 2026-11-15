import 'package:flutter_test/flutter_test.dart';
import 'package:stockflow_rep/features/auth/presentation/managers/login/login_event.dart';
import 'package:stockflow_rep/features/auth/presentation/managers/login/login_state.dart';

void main() {
  test('حالات الدخول الأولية معرّفة بشكل صحيح', () {
    expect(const LoginInitialState(), isA<LoginState>());
    expect(const LoginLoadingState(), isA<LoginState>());
    expect(const LoginSuccessState(), isA<LoginState>());
  });

  test('حدث الدخول يحمل القيم', () {
    const event = SubmitLoginEvent('01000000002', 'rep123');

    expect(event.phone, '01000000002');
    expect(event.password, 'rep123');
    expect(event.props, ['01000000002', 'rep123']);
  });
}
