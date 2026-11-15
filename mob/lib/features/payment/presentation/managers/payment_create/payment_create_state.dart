sealed class PaymentCreateState {}

final class PaymentCreateInitial extends PaymentCreateState {}

final class PaymentSubmitting extends PaymentCreateState {}

final class PaymentQueued extends PaymentCreateState {
  final bool sent;
  PaymentQueued(this.sent);
}

final class PaymentInvalid extends PaymentCreateState {
  final String message;
  PaymentInvalid(this.message);
}
