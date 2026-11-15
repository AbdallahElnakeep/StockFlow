class BootstrapData {
  final String syncedAt;
  final int custodyWarehouseId;
  final List<CustodyItem> custodyBalances;
  final List<ProductItem> products;
  final List<CustomerItem> customers;

  const BootstrapData({
    required this.syncedAt,
    required this.custodyWarehouseId,
    required this.custodyBalances,
    required this.products,
    required this.customers,
  });
}

class CustodyItem {
  final int productId;
  final String name;
  final double qty;
  final double salePrice;

  const CustodyItem({required this.productId, required this.name, required this.qty, required this.salePrice});
}

class ProductItem {
  final int id;
  final String name;
  final String? barcode;
  final double salePrice;

  const ProductItem({required this.id, required this.name, this.barcode, required this.salePrice});
}

class CustomerItem {
  final int id;
  final String name;
  final String? phone;
  final String? address;
  final String? area;
  final double balance;
  final double creditLimit;

  const CustomerItem({required this.id, required this.name, this.phone, this.address, this.area, required this.balance, required this.creditLimit});
}
