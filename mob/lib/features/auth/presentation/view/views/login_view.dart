import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/router/app_routes.dart';
import '../../../../../../core/widgets/server_url_field.dart';
import '../../managers/login/login_bloc.dart';
import '../../managers/login/login_event.dart';
import '../../managers/login/login_state.dart';

class LoginView extends StatelessWidget {
  LoginView({super.key});

  final _phone = TextEditingController();
  final _password = TextEditingController();

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => LoginBloc(),
      child: Scaffold(
        backgroundColor: const Color(0xFF0B1E3D),
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text('app_name'.tr(), textAlign: TextAlign.center, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 4),
                    Text('login_sub'.tr(), textAlign: TextAlign.center, style: const TextStyle(fontSize: 13, color: Color(0xFF667085))),
                    const SizedBox(height: 24),
                    BlocConsumer<LoginBloc, LoginState>(
                      listener: (context, state) {
                        if (state is LoginSuccessState) {
                          context.go(sl<AppRoutes>().home);
                        }
                      },
                      builder: (context, state) {
                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            TextField(
                              controller: _phone,
                              keyboardType: TextInputType.phone,
                              decoration: InputDecoration(labelText: 'phone'.tr()),
                            ),
                            const SizedBox(height: 14),
                            TextField(
                              controller: _password,
                              obscureText: true,
                              decoration: InputDecoration(labelText: 'password'.tr()),
                            ),
                            if (state is LoginErrorState) ...[
                              const SizedBox(height: 12),
                              Text(state.message, style: const TextStyle(color: Color(0xFFB42318), fontSize: 13)),
                            ],
                            const SizedBox(height: 20),
                            ElevatedButton(
                              onPressed: state is LoginLoadingState
                                  ? null
                                  : () => context.read<LoginBloc>().add(SubmitLoginEvent(_phone.text.trim(), _password.text)),
                              child: state is LoginLoadingState
                                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : Text('login'.tr()),
                            ),
                            const SizedBox(height: 14),
                            const ServerUrlField(),
                          ],
                        );
                      },
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
