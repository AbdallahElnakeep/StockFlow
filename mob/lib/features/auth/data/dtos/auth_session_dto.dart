import '../../domain/entity/auth_session.dart';

class AuthSessionDto {
  final String token;
  final Map<String, dynamic> user;

  const AuthSessionDto({required this.token, required this.user});

  factory AuthSessionDto.fromJson(Map<String, dynamic> json) => AuthSessionDto(
        token: json['token'] as String,
        user: json['user'] as Map<String, dynamic>,
      );

  AuthSession toEntity() => AuthSession(
        token: token,
        userId: user['id'] as int,
        name: user['name'] as String,
        isRep: user['is_rep'] as bool? ?? false,
        custodyWarehouseId: user['custody_warehouse_id'] as int?,
      );
}
