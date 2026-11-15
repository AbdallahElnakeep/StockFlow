import 'package:flutter_test/flutter_test.dart';
import 'package:stockflow_rep/core/error/failures.dart';
import 'package:stockflow_rep/features/bootstrap/data/dtos/bootstrap_dto.dart';

void main() {
  test('bootstrap dto maps full payload to entity', () {
    final json = {
      'synced_at': '2026-11-15T10:00:00Z',
      'custody': {
        'warehouse_id': 3,
        'name': 'عهدة',
        'balances': [
          {'product_id': 1, 'name': 'زيت', 'qty': 30.0, 'sale_price': 100.0},
        ],
      },
      'products': [
        {'id': 1, 'name': 'زيت', 'barcode': '123', 'sku': null, 'sale_price': 100.0},
      ],
      'customers': [
        {'id': 5, 'name': 'بقالة', 'phone': '010', 'address': null, 'area': 'وسط', 'balance': 200.0, 'credit_limit': 500.0},
      ],
    };

    final data = BootstrapDto(json).toEntity();

    expect(data.custodyWarehouseId, 3);
    expect(data.custodyBalances.single.qty, 30.0);
    expect(data.products.single.name, 'زيت');
    expect(data.customers.single.balance, 200.0);
  });

  test('failure messages are user facing arabic', () {
    expect(const NetworkFailure().message, contains('اتصال'));
    expect(const UnauthorizedFailure().message, contains('الجلسة'));
  });
}
