import 'package:dartz/dartz.dart' show Left, Right;
import 'package:flutter_test/flutter_test.dart';
import 'package:stockflow_rep/core/error/failures.dart';
import 'package:stockflow_rep/features/bootstrap/domain/repo/bootstrap_repo.dart';
import 'package:stockflow_rep/features/bootstrap/domain/entity/bootstrap_data.dart';
import 'package:stockflow_rep/features/bootstrap/presentation/managers/bootstrap_bloc.dart';
import 'package:stockflow_rep/features/invoice/presentation/managers/invoice_create/invoice_create_bloc.dart';
import 'package:stockflow_rep/features/invoice/presentation/managers/invoice_create/invoice_create_event.dart';
import 'package:stockflow_rep/features/invoice/presentation/managers/invoice_create/invoice_create_state.dart';
import 'package:stockflow_rep/features/payment/presentation/managers/payment_create/payment_create_bloc.dart';
import 'package:stockflow_rep/features/payment/presentation/managers/payment_create/payment_create_event.dart';
import 'package:stockflow_rep/features/payment/presentation/managers/payment_create/payment_create_state.dart';
import 'package:stockflow_rep/features/return/presentation/managers/return_create/return_create_bloc.dart';
import 'package:stockflow_rep/features/return/presentation/managers/return_create/return_create_event.dart';
import 'package:stockflow_rep/features/return/presentation/managers/return_create/return_create_state.dart';
import 'package:stockflow_rep/features/sync/domain/repo/sync_repo.dart';

class _StubRepo implements SyncRepo {
  final List<String> enqueuedTypes = [];
  final Map<String, String> opStates = {};
  final Map<String, dynamic> opReasons = {};
  int removedCount = 0;
  int seq = 0;

  @override
  Future<String> enqueue(String type, Map<String, dynamic> payload) async {
    seq += 1;
    final uuid = 'op-$seq';
    enqueuedTypes.add(type);
    opStates[uuid] = 'pending';
    return uuid;
  }

  @override
  Future<int> pendingCount() async => opStates.length;

  @override
  Future<ApiResult<List<SyncOpResult>>> flush() async {
    final results = <SyncOpResult>[];
    opStates.forEach((uuid, state) {
      final resolved = state == 'pending' ? (opReasons[uuid] != null ? 'rejected' : 'accepted') : state;
      results.add(SyncOpResult(opUuid: uuid, state: resolved, reason: opReasons[uuid]));
    });
    opStates.clear();
    removedCount += results.length;
    return Right(results);
  }
}

class _StubBootstrapRepo implements BootstrapRepo {
  @override
  Future<ApiResult<BootstrapData>> fetchRemote() async => const Left(NetworkFailure());
  @override
  Future<BootstrapData?> loadCached() async => null;
}

void main() {
  test('فاتورة مرفوضة من السيرفر تُصدر خطأ بسبب الرفض وليس نجاحًا', () async {
    final repo = _StubRepo();
    final bloc = InvoiceCreateBloc(repo, BootstrapBloc(_StubBootstrapRepo()));
    repo.opReasons['op-1'] = 'الكمية المطلوبة غير متوفرة في المخزن';

    final states = <InvoiceCreateState>[];
    bloc.stream.listen(states.add);

    bloc.add(SubmitInvoiceEvent(
      customerId: 1,
      type: 'cash',
      items: [
        {'product_id': 1, 'qty': 5, 'unit_price': 10},
      ],
    ));

    await Future.delayed(const Duration(milliseconds: 200));

    final error = states.whereType<InvoiceCreateError>().toList();
    expect(error, isNotEmpty);
    expect(states.whereType<InvoiceQueued>(), isEmpty);
  });

  test('كمية غير موجبة تُرفض محليًا قبل الإرسال', () async {
    final repo = _StubRepo();
    final bloc = InvoiceCreateBloc(repo, BootstrapBloc(_StubBootstrapRepo()));

    final states = <InvoiceCreateState>[];
    bloc.stream.listen(states.add);

    bloc.add(SubmitInvoiceEvent(
      customerId: 1,
      type: 'cash',
      items: [
        {'product_id': 1, 'qty': 0, 'unit_price': 10},
      ],
    ));

    await Future.delayed(const Duration(milliseconds: 200));

    expect(states.whereType<InvoiceCreateError>(), isNotEmpty);
    expect(repo.enqueuedTypes, isEmpty);
  });

  test('تحصيل مرفوض يُصدر خطأ وليس نجاحًا', () async {
    final repo = _StubRepo();
    final bloc = PaymentCreateBloc(repo, BootstrapBloc(_StubBootstrapRepo()));
    repo.opReasons['op-1'] = 'بيانات غير موجودة في نطاق شركتك';

    final states = <PaymentCreateState>[];
    bloc.stream.listen(states.add);

    bloc.add(SubmitPaymentEvent(customerId: 99, amount: 100, note: ''));

    await Future.delayed(const Duration(milliseconds: 200));

    expect(states.whereType<PaymentInvalid>(), isNotEmpty);
    expect(states.whereType<PaymentQueued>(), isEmpty);
  });

  test('مرتجع بكمية سالبة يُرفض محليًا', () async {
    final repo = _StubRepo();
    final bloc = ReturnCreateBloc(repo, BootstrapBloc(_StubBootstrapRepo()));

    final states = <ReturnCreateState>[];
    bloc.stream.listen(states.add);

    bloc.add(SubmitReturnEvent(
      customerId: 1,
      restock: true,
      items: [
        {'product_id': 1, 'qty': -3, 'unit_price': 10},
      ],
    ));

    await Future.delayed(const Duration(milliseconds: 200));

    expect(states.whereType<ReturnInvalid>(), isNotEmpty);
    expect(repo.enqueuedTypes, isEmpty);
  });

  test('عملية مقبولة تُصدر نجاحًا وتُحذف من الطابور', () async {
    final repo = _StubRepo();
    final bloc = PaymentCreateBloc(repo, BootstrapBloc(_StubBootstrapRepo()));

    final states = <PaymentCreateState>[];
    bloc.stream.listen(states.add);

    bloc.add(SubmitPaymentEvent(customerId: 1, amount: 50, note: ''));

    await Future.delayed(const Duration(milliseconds: 200));

    expect(states.whereType<PaymentQueued>().first.sent, true);
    expect(repo.removedCount, 1);
  });
}
