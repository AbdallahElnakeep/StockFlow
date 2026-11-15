import 'package:dartz/dartz.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../core/error/failures.dart';
import '../../../../core/network/api_service.dart';
import '../../domain/entity/auth_session.dart';
import '../../domain/repo/auth_repo.dart';
import '../dtos/auth_session_dto.dart';

class AuthRepoImpl implements AuthRepo {
  final ApiService _api;
  AuthRepoImpl(this._api);

  @override
  Future<ApiResult<AuthSession>> login(String phone, String password, String deviceName) async {
    try {
      final response = await _api.login({
        'phone': phone,
        'password': password,
        'device_name': deviceName,
      });
      final session = AuthSessionDto.fromJson(response.data as Map<String, dynamic>).toEntity();

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(DioTokenKeys.token, session.token);

      return Right(session);
    } on Exception catch (e) {
      return Left(_mapFailure(e));
    }
  }

  @override
  Future<void> logout() async {
    try {
      await _api.logout();
    } on Exception {
      rethrow;
    } finally {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(DioTokenKeys.token);
    }
  }

  @override
  Future<AuthSession?> savedSession() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(DioTokenKeys.token);
    if (token == null) return null;
    return AuthSession(
      token: token,
      userId: prefs.getInt('user_id') ?? 0,
      name: prefs.getString('user_name') ?? '',
      isRep: prefs.getBool('user_is_rep') ?? false,
    );
  }

  AppFailure _mapFailure(Exception e) {
    final message = e.toString();
    if (message.contains('SocketException') || message.contains('Connection refused') || message.contains('Failed host lookup')) {
      return const NetworkFailure();
    }

    if (message.contains('401')) {
      return const UnauthorizedFailure();
    }

    return const ServerFailure('فشل تسجيل الدخول — تحقق من البيانات والاتصال');
  }
}

class DioTokenKeys {
  static const token = 'auth_token';
}
