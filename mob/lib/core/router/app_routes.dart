class AppRoutes {
  final splash = '/';
  final login = '/login';
  final home = '/home';
  final customers = '/customers';
  final customerDetail = '/customers/:id';
  final invoiceCreate = '/invoice/:customerId';
  final paymentCreate = '/payment/:customerId';
  final returnCreate = '/return/:customerId';
  final custody = '/custody';
  final expenses = '/expenses';
  final sync = '/sync';
}
