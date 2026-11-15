import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';
import 'package:stockflow_rep/core/local/app_database.dart';

void main() {
  setUpAll(() {
    sqfliteFfiInit();
    databaseFactory = databaseFactoryFfi;
  });

  late AppDatabase db;

  setUp(() async {
    db = AppDatabase(inMemory: true);
    await db.open();
  });

  tearDown(() async {
    final database = await db.database;
    await database.close();
  });

  test('enqueue ثم pendingOps يعيد العمليات بترتيب الإدراج', () async {
    await db.enqueueOp('uuid-1', 'invoice', {'customer_id': 5, 'total': 300});
    await db.enqueueOp('uuid-2', 'payment', {'customer_id': 5, 'amount': 50});

    final ops = await db.pendingOps();

    expect(ops.length, 2);
    expect(ops.first['op_uuid'], 'uuid-1');
    expect(ops.last['type'], 'payment');
    expect(await db.pendingCount(), 2);
  });

  test('نفس op_uuid لا يُكرر أبداً (idempotency محلي)', () async {
    await db.enqueueOp('dup-uuid', 'invoice', {'x': 1});
    await db.enqueueOp('dup-uuid', 'invoice', {'x': 1});
    await db.enqueueOp('dup-uuid', 'invoice', {'x': 1});

    expect(await db.pendingCount(), 1);
  });

  test('removeOp يحذف العملية المُزامنة فقط', () async {
    await db.enqueueOp('op-a', 'invoice', {});
    await db.enqueueOp('op-b', 'expense', {});

    await db.removeOp('op-a');

    final ops = await db.pendingOps();
    expect(ops.length, 1);
    expect(ops.first['op_uuid'], 'op-b');
  });

  test('الـpayload يُحفظ ويُسترجع كـJSON صحيح', () async {
    await db.enqueueOp('op-json', 'invoice', {
      'customer_id': 9,
      'items': [
        {'product_id': 3, 'qty': 12.5, 'unit_price': 100.0},
      ],
    });

    final ops = await db.pendingOps();
    final payload = ops.first['payload'] as String;
    expect(payload, contains('customer_id'));
    expect(payload, contains('12.5'));
  });

  test('cache يُخزن ويُقرأ ويُحدّث', () async {
    await db.putCache('bootstrap', '{"v": 1}');
    await db.putCache('device_id', 'abc');

    expect(await db.getCache('bootstrap'), '{"v": 1}');
    expect(await db.getCache('device_id'), 'abc');

    await db.putCache('bootstrap', '{"v": 2}');
    expect(await db.getCache('bootstrap'), '{"v": 2}');
  });
}
