import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../../../../core/di/injection.dart';
import '../../../../../../core/theme/app_theme.dart';
import '../../managers/sync_flush/sync_flush_bloc.dart';
import '../../managers/sync_flush/sync_flush_event.dart';
import '../../managers/sync_flush/sync_flush_state.dart';

class SyncView extends StatelessWidget {
  const SyncView({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => SyncFlushBloc(sl())..add(const LoadPendingCount()),
      child: Scaffold(
        appBar: AppBar(title: Text('sync'.tr())),
        body: BlocConsumer<SyncFlushBloc, SyncFlushState>(
          listener: (context, state) {
            if (state is SyncFlushDone && state.rejectedCount > 0) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('${'rejected_ops'.tr()}: ${state.rejectedCount}')),
              );
            }
          },
          builder: (context, state) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    '${'pending_ops'.tr()}: ${state.pendingCount}',
                    style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 20),
                  if (state is SyncFlushing)
                    const CircularProgressIndicator()
                  else
                    ElevatedButton(
                      onPressed: () => context.read<SyncFlushBloc>().add(const FlushNow()),
                      child: Text('sync_now'.tr()),
                    ),
                  if (state is SyncFlushDone) ...[
                    const SizedBox(height: 16),
                    Text(
                      state.rejectedCount > 0 ? 'some_rejected'.tr() : 'all_synced'.tr(),
                      style: TextStyle(
                        color: state.rejectedCount > 0 ? AppColors.error : AppColors.success,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}
