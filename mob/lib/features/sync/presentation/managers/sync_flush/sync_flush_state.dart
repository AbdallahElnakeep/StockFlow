sealed class SyncFlushState {
  final int pendingCount;
  const SyncFlushState(this.pendingCount);
}

final class SyncFlushIdle extends SyncFlushState {
  const SyncFlushIdle(super.pendingCount);
}

final class SyncFlushing extends SyncFlushState {
  const SyncFlushing(super.pendingCount);
}

final class SyncFlushDone extends SyncFlushState {
  final int rejectedCount;
  const SyncFlushDone(super.pendingCount, this.rejectedCount);
}

final class SyncOffline extends SyncFlushState {
  const SyncOffline(super.pendingCount);
}
