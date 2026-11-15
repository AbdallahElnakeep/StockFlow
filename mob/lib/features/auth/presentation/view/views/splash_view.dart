import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/router/app_routes.dart';
import '../../../../../../core/di/injection.dart';
import '../../managers/login/login_bloc.dart';
import '../../managers/login/login_event.dart';
import '../../managers/login/login_state.dart';

class SplashView extends StatelessWidget {
  const SplashView({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => LoginBloc()..add(const CheckSessionEvent()),
      child: BlocListener<LoginBloc, LoginState>(
        listener: (context, state) {
          if (state is SessionRestoredState) {
            context.go(sl<AppRoutes>().home);
          } else if (state is NoSessionState) {
            context.go(sl<AppRoutes>().login);
          }
        },
        child: const Scaffold(
          backgroundColor: Color(0xFF0B1E3D),
          body: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('StockFlow', style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w700)),
                SizedBox(height: 12),
                CircularProgressIndicator(color: Color(0xFF0066FF)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
