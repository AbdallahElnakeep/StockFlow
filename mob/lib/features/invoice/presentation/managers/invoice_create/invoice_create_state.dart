sealed class InvoiceCreateState {}

final class InvoiceCreateInitial extends InvoiceCreateState {}

final class InvoiceSubmitting extends InvoiceCreateState {}

final class InvoiceQueued extends InvoiceCreateState {
  final bool sent;
  InvoiceQueued(this.sent);
}

final class InvoiceCreateError extends InvoiceCreateState {
  final String message;
  InvoiceCreateError(this.message);
}
