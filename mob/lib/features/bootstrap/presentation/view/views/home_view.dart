import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/router/app_routes.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../../domain/repo/bootstrap_repo.dart';
import '../../managers/bootstrap_bloc.dart';

class HomeView extends StatelessWidget {
  const HomeView({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => BootstrapBloc(sl<BootstrapRepo>())..add(const LoadBootstrap()),
      child: const _HomeScaffold(),
    );
  }
}

class _HomeScaffold extends StatelessWidget {
  const _HomeScaffold();

  @override
  Widget build(BuildContext context) {
    final routes = sl<AppRoutes>();

    return Scaffold(
      appBar: AppBar(title: Text('home'.tr())),
      body: BlocBuilder<BootstrapBloc, BootstrapState>(
        builder: (context, state) {
          if (state is BootstrapLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state is BootstrapEmpty) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('need_sync'.tr(), style: const TextStyle(color: AppColors.text3)),
                  const SizedBox(height: 12),
                  ElevatedButton(onPressed: () => context.read<BootstrapBloc>().add(const LoadBootstrap()), child: Text('retry'.tr())),
                ],
              ),
            );
          }
          if (state is BootstrapLoaded) {
            return RefreshIndicator(
              onRefresh: () async => context.read<BootstrapBloc>().add(const LoadBootstrap()),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (state.fromCache)
                    Container(
                      padding: const EdgeInsets.all(10),
                      margin: const EdgeInsets.only(bottom: 12),
                      decoration: BoxDecoration(color: AppColors.warningBg, borderRadius: BorderRadius.circular(6)),
                      child: Text('offline_cached'.tr(), style: const TextStyle(color: AppColors.warning, fontSize: 12.5)),
                    ),
                  _ActionCard(icon: Icons.groups, label: 'customers'.tr(), onTap: () => context.go(routes.customers)),
                  const SizedBox(height: 10),
                  _ActionCard(icon: Icons.inventory_2, label: 'custody'.tr(), onTap: () => context.go(routes.custody)),
                  const SizedBox(height: 10),
                  _ActionCard(icon: Icons.sync, label: 'sync'.tr(), onTap: () => context.go(routes.sync)),
                ],
              ),
            );
          }
          return const SizedBox.shrink();
        },
      ),
    );
  }
}

class _ActionCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _ActionCard({required this.icon, required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: Icon(icon, color: AppColors.brand),
        title: Text(label),
        trailing: const Icon(Icons.chevron_left, color: AppColors.text3),
        onTap: onTap,
      ),
    );
  }
}
