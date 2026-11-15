import '../../../../core/error/failures.dart';

abstract class SyncRepo {
  Future<void> enqueue(String type, Map<String, dynamic> payload);
  Future<int> pendingCount();
  Future<ApiResult<List<SyncOpResult>>> flush();
}

class SyncOpResult {
  final String opUuid;
  final String state;
  final dynamic reason;

  const SyncOpResult({required this.opUuid, required this.state, this.reason});
}
