<?php

$fixes = [
    'lib/features/auth/presentation/managers/login/login_bloc.dart' => [
        "../../../../domain/repo/auth_repo.dart" => "../../domain/repo/auth_repo.dart",
    ],
    'lib/features/custody/presentation/view/views/custody_view.dart' => [
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/customers/presentation/view/views/customer_detail_view.dart' => [
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/customers/presentation/view/views/customers_view.dart' => [
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/invoice/presentation/managers/invoice_create/invoice_create_bloc.dart' => [
        "../../../sync/" => "../../../../sync/",
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/invoice/presentation/view/views/invoice_create_view.dart' => [
        "../../../bootstrap/" => "../../../../bootstrap/",
        "../../../sync/" => "../../../../sync/",
    ],
    'lib/features/payment/presentation/managers/payment_create/payment_create_bloc.dart' => [
        "../../../sync/" => "../../../../sync/",
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/payment/presentation/view/views/payment_create_view.dart' => [
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/return/presentation/managers/return_create/return_create_bloc.dart' => [
        "../../../sync/" => "../../../../sync/",
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/return/presentation/view/views/return_create_view.dart' => [
        "../../../bootstrap/" => "../../../../bootstrap/",
    ],
    'lib/features/sync/presentation/managers/sync_flush/sync_flush_bloc.dart' => [
        "../../domain/repo/sync_repo.dart" => "../../../../domain/repo/sync_repo.dart",
    ],
];

foreach ($fixes as $file => $replacements) {
    $src = file_get_contents($file);
    $src = str_replace(array_keys($replacements), array_values($replacements), $src);
    file_put_contents($file, $src);
    echo "fixed: $file\n";
}
