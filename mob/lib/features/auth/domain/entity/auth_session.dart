class AuthSession {
  final String token;
  final int userId;
  final String name;
  final bool isRep;
  final int? custodyWarehouseId;

  const AuthSession({
    required this.token,
    required this.userId,
    required this.name,
    required this.isRep,
    this.custodyWarehouseId,
  });
}
