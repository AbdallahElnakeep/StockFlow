sealed class InvoiceCreateEvent {}

final class SubmitInvoiceEvent extends InvoiceCreateEvent {
  final int customerId;
  final String type;
  final List<Map<String, dynamic>> items;

  SubmitInvoiceEvent({required this.customerId, required this.type, required this.items});
}
