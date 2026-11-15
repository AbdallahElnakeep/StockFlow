sealed class ReturnCreateEvent {}

final class SubmitReturnEvent extends ReturnCreateEvent {
  final int customerId;
  final bool restock;
  final List<Map<String, dynamic>> items;

  SubmitReturnEvent({required this.customerId, required this.restock, required this.items});
}
