import 'package:dio/dio.dart';
import '../di/injection.dart';
import 'dio_client.dart';

class ApiService {
  final Dio _dio = sl<DioClient>().create();

  Future<Response> login(Map<String, dynamic> body) => _dio.post('/api/auth/login', data: body);

  Future<Response> logout() => _dio.post('/api/auth/logout');

  Future<Response> bootstrap() => _dio.get('/api/bootstrap');

  Future<Response> sync(Map<String, dynamic> body) => _dio.post('/api/sync', data: body);

  Future<Response> summary() => _dio.get('/api/my/summary');
}
