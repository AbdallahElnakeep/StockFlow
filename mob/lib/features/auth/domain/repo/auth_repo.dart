import '../../../../core/error/failures.dart';
import '../entity/auth_session.dart';

abstract class AuthRepo {
  Future<ApiResult<AuthSession>> login(String phone, String password, String deviceName);
  Future<void> logout();
  Future<AuthSession?> savedSession();
}
