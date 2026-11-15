import 'package:dartz/dartz.dart';

typedef ApiResult<T> = Either<AppFailure, T>;

sealed class AppFailure {
  final String message;
  const AppFailure(this.message);
}

final class ServerFailure extends AppFailure {
  const ServerFailure(super.message);
}

final class NetworkFailure extends AppFailure {
  const NetworkFailure() : super('لا يوجد اتصال بالإنترنت');
}

final class UnauthorizedFailure extends AppFailure {
  const UnauthorizedFailure() : super('انتهت الجلسة — سجّل الدخول مرة أخرى');
}

final class ValidationFailure extends AppFailure {
  const ValidationFailure(super.message);
}

final class CacheFailure extends AppFailure {
  const CacheFailure(super.message);
}
