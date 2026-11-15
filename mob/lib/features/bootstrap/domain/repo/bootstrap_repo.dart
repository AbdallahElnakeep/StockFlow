import '../../../../core/error/failures.dart';
import '../entity/bootstrap_data.dart';

abstract class BootstrapRepo {
  Future<ApiResult<BootstrapData>> fetchRemote();
  Future<BootstrapData?> loadCached();
}
