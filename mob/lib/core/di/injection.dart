import 'package:get_it/get_it.dart';
import '../local/app_database.dart';
import '../network/api_service.dart';
import '../network/dio_client.dart';
import '../router/app_router.dart';
import '../router/app_routes.dart';
import '../../features/auth/data/repo_impl/auth_repo_impl.dart';
import '../../features/auth/domain/repo/auth_repo.dart';
import '../../features/bootstrap/data/repo_impl/bootstrap_repo_impl.dart';
import '../../features/bootstrap/domain/repo/bootstrap_repo.dart';
import '../../features/sync/data/repo_impl/sync_repo_impl.dart';
import '../../features/sync/domain/repo/sync_repo.dart';
import '../../features/bootstrap/presentation/managers/bootstrap_bloc.dart';

final sl = GetIt.instance;

Future<void> configureDependencies() async {
  sl.registerLazySingleton<DioClient>(() => DioClient());
  sl.registerLazySingleton<ApiService>(() => ApiService());
  sl.registerLazySingleton<AppDatabase>(() => AppDatabase());
  sl.registerLazySingleton<AppRoutes>(() => AppRoutes());
  sl.registerLazySingleton<AppRouter>(() => AppRouter(sl()));

  sl.registerLazySingleton<AuthRepo>(() => AuthRepoImpl(sl()));
  sl.registerLazySingleton<BootstrapRepo>(() => BootstrapRepoImpl(sl(), sl()));
  sl.registerLazySingleton<SyncRepo>(() => SyncRepoImpl(sl(), sl()));
  sl.registerLazySingleton<BootstrapBloc>(() => BootstrapBloc(sl<BootstrapRepo>())..add(const LoadBootstrap()));
}
