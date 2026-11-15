sealed class PaymentCreateEvent {}

final class SubmitPaymentEvent extends PaymentCreateEvent {
  final int customerId;
  final double amount;
  final String note;

  SubmitPaymentEvent({required this.customerId, required this.amount, required this.note});
}
