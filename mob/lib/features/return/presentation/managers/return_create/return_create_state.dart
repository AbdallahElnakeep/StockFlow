sealed class ReturnCreateState {}

final class ReturnCreateInitial extends ReturnCreateState {}

final class ReturnSubmitting extends ReturnCreateState {}

final class ReturnQueued extends ReturnCreateState {
  final bool sent;
  ReturnQueued(this.sent);
}

final class ReturnInvalid extends ReturnCreateState {
  final String message;
  ReturnInvalid(this.message);
}
