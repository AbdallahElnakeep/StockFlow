import '../../domain/entity/bootstrap_data.dart';

class BootstrapDto {
  final Map<String, dynamic> json;

  const BootstrapDto(this.json);

  BootstrapData toEntity() => BootstrapData(
        syncedAt: json['synced_at'] as String,
        custodyWarehouseId: (json['custody'] as Map<String, dynamic>)['warehouse_id'] as int,
        custodyBalances: ((json['custody'] as Map<String, dynamic>)['balances'] as List)
            .map((e) => _custody(e as Map<String, dynamic>))
            .toList(),
        products: (json['products'] as List).map((e) => _product(e as Map<String, dynamic>)).toList(),
        customers: (json['customers'] as List).map((e) => _customer(e as Map<String, dynamic>)).toList(),
      );

  CustodyItem _custody(Map<String, dynamic> e) => CustodyItem(
        productId: e['product_id'] as int,
        name: e['name'] as String,
        qty: (e['qty'] as num).toDouble(),
        salePrice: (e['sale_price'] as num).toDouble(),
      );

  ProductItem _product(Map<String, dynamic> e) => ProductItem(
        id: e['id'] as int,
        name: e['name'] as String,
        barcode: e['barcode'] as String?,
        salePrice: (e['sale_price'] as num).toDouble(),
      );

  CustomerItem _customer(Map<String, dynamic> e) => CustomerItem(
        id: e['id'] as int,
        name: e['name'] as String,
        phone: e['phone'] as String?,
        address: e['address'] as String?,
        area: e['area'] as String?,
        balance: (e['balance'] as num).toDouble(),
        creditLimit: (e['credit_limit'] as num).toDouble(),
      );
}
