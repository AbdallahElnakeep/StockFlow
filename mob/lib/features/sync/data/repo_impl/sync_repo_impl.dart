import 'dart:convert';
import 'package:dartz/dartz.dart';
import 'package:uuid/uuid.dart';
import '../../../../core/error/failures.dart';
import '../../../../core/local/app_database.dart';
import '../../../../core/network/api_service.dart';
import '../../domain/repo/sync_repo.dart';

class SyncRepoImpl implements SyncRepo {
  final ApiService _api;
  final AppDatabase _db;
  final Uuid _uuid = const Uuid();

  SyncRepoImpl(this._api, this._db);

  @override
  Future<String> enqueue(String type, Map<String, dynamic> payload) async {
    final opUuid = _uuid.v4();
    await _db.enqueueOp(opUuid, type, payload);
    return opUuid;
  }

  @override
  Future<int> pendingCount() async {
    final ops = await _db.pendingOps();
    return ops.length;
  }

  @override
  Future<ApiResult<List<SyncOpResult>>> flush() async {
    final ops = await _db.pendingOps();
    if (ops.isEmpty) {
      return const Right([]);
    }

    final batchUuid = _uuid.v4();

    try {
      final response = await _api.sync({
        'batch_uuid': batchUuid,
        'device_id': await _deviceId(),
        'operations': ops.map((op) => {
              'op_uuid': op['op_uuid'],
              'type': op['type'],
              'data': jsonDecode(op['payload'] as String),
            }).toList(),
      });

      final results = (response.data['results'] as List)
          .map((r) => SyncOpResult(
                opUuid: r['op_uuid'] as String,
                state: (r['status'] as Map<String, dynamic>)['state'] as String,
                reason: (r['status'] as Map<String, dynamic>)['reason'],
              ))
          .toList();

      for (final result in results) {
        await _db.removeOp(result.opUuid);
      }

      return Right(results);
    } on Exception {
      return const Left(NetworkFailure());
    }
  }

  Future<String> _deviceId() async {
    var id = await _db.getCache('device_id');
    if (id == null) {
      id = _uuid.v4();
      await _db.putCache('device_id', id);
    }
    return id;
  }
}
