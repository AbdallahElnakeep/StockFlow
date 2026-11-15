import 'dart:convert';
import 'package:dartz/dartz.dart';
import '../../../../core/error/failures.dart';
import '../../../../core/local/app_database.dart';
import '../../../../core/network/api_service.dart';
import '../../domain/entity/bootstrap_data.dart';
import '../../domain/repo/bootstrap_repo.dart';
import '../dtos/bootstrap_dto.dart';

class BootstrapRepoImpl implements BootstrapRepo {
  final ApiService _api;
  final AppDatabase _db;

  BootstrapRepoImpl(this._api, this._db);

  @override
  Future<ApiResult<BootstrapData>> fetchRemote() async {
    try {
      final response = await _api.bootstrap();
      final data = BootstrapDto(response.data as Map<String, dynamic>).toEntity();
      await _db.putCache('bootstrap', jsonEncode(response.data));
      return Right(data);
    } on Exception {
      return const Left(NetworkFailure());
    }
  }

  @override
  Future<BootstrapData?> loadCached() async {
    final raw = await _db.getCache('bootstrap');
    if (raw == null) return null;
    return BootstrapDto(jsonDecode(raw) as Map<String, dynamic>).toEntity();
  }
}
