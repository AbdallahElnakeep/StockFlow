sealed class SyncFlushEvent {
  const SyncFlushEvent();
}

final class LoadPendingCount extends SyncFlushEvent {
  const LoadPendingCount();
}

final class FlushNow extends SyncFlushEvent {
  const FlushNow();
}
