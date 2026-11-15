import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/presentation/view/views/login_view.dart';
import '../../features/auth/presentation/view/views/splash_view.dart';
import '../../features/bootstrap/presentation/view/views/home_view.dart';
import '../../features/customers/presentation/view/views/customer_detail_view.dart';
import '../../features/customers/presentation/view/views/customers_view.dart';
import '../../features/custody/presentation/view/views/custody_view.dart';
import '../../features/invoice/presentation/view/views/invoice_create_view.dart';
import '../../features/payment/presentation/view/views/payment_create_view.dart';
import '../../features/return/presentation/view/views/return_create_view.dart';
import '../../features/sync/presentation/view/views/sync_view.dart';
import '../../features/visit/presentation/view/views/visit_log_view.dart';
import 'app_routes.dart';

class AppRouter {
  final AppRoutes routes;
  AppRouter(this.routes);

  GoRouter build() => GoRouter(
        initialLocation: routes.splash,
        routes: [
          GoRoute(
            path: routes.splash,
            pageBuilder: (s, r) => const NoTransitionPage(child: SplashView()),
          ),
          GoRoute(
            path: routes.login,
            pageBuilder: (s, r) => NoTransitionPage(child: LoginView()),
          ),
          GoRoute(
            path: routes.home,
            pageBuilder: (s, r) => const NoTransitionPage(child: HomeView()),
            routes: [
              GoRoute(
                path: 'customers',
                pageBuilder: (s, r) => _slide(const CustomersView()),
                routes: [
                  GoRoute(
                    path: ':id',
                    pageBuilder: (s, r) => _slide(CustomerDetailView(id: int.parse(r.pathParameters['id']!))),
                  ),
                ],
              ),
              GoRoute(
                path: 'custody',
                pageBuilder: (s, r) => _slide(const CustodyView()),
              ),
              GoRoute(
                path: 'sync',
                pageBuilder: (s, r) => _slide(const SyncView()),
              ),
              GoRoute(
                path: 'invoice/:customerId',
                pageBuilder: (s, r) => _slide(InvoiceCreateView(customerId: int.parse(r.pathParameters['customerId']!))),
              ),
              GoRoute(
                path: 'payment/:customerId',
                pageBuilder: (s, r) => _slide(PaymentCreateView(customerId: int.parse(r.pathParameters['customerId']!))),
              ),
              GoRoute(
                path: 'return/:customerId',
                pageBuilder: (s, r) => _slide(ReturnCreateView(customerId: int.parse(r.pathParameters['customerId']!))),
              ),
              GoRoute(
                path: 'visit/:customerId',
                pageBuilder: (s, r) => _slide(VisitLogView(customerId: int.parse(r.pathParameters['customerId']!))),
              ),
            ],
          ),
        ],
      );

  CustomTransitionPage _slide(Widget child) => CustomTransitionPage(
        child: child,
        transitionDuration: const Duration(milliseconds: 180),
        transitionsBuilder: (c, a, s, child) => SlideTransition(
          position: Tween(begin: const Offset(0.06, 0), end: Offset.zero).animate(a),
          child: child,
        ),
      );
}

GoRouter buildAppRouter(AppRouter router) => router.build();
