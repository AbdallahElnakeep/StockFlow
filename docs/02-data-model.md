# StockFlow — Data Model & Core Workflows

## 1. جداول قاعدة البيانات (Migration مستقل لكل جدول)

### Tenancy & Users
- `companies` — name, is_active, settings JSON, plan
- `users` — company_id, name, phone (login), password, is_active, last_login_at
- `roles` — company_id (null = نظامية), name, is_system
- `permissions` — key, module, name_ar — ثابتة seed
- `role_permission` — pivot
- `role_user` — pivot

### Inventory
- `warehouses` — company_id, name, type: main|rep|van, rep_user_id (فريد إذا type=rep)
- `categories` — company_id, name, parent_id
- `units` — company_id, name, short_name
- `products` — company_id, category_id, unit_id, sku, barcode, name, cost_price, sale_price, min_stock, is_active
- `stock_balances` — company_id, warehouse_id, product_id, qty (فريد مركّب)
- `stock_movements` — company_id, ref_type, ref_id, warehouse_id, product_id, qty (+in/−out), unit_cost, balance_after, uuid, created_by
- `stock_transfers` + `stock_transfer_items` — from_warehouse, to_warehouse, status: draft|confirmed
- `stock_counts` + `stock_count_items` — warehouse_id, status: draft|adjusted, counted_qty, system_qty

### Purchasing
- `suppliers` — company_id, name, phone, balance
- `purchases` + `purchase_items` — supplier_id, warehouse_id, uuid, total, payment_type: cash|credit, status

### Sales
- `customers` — company_id, name, phone, address, area, credit_limit, balance
- `sales_invoices` + `sales_invoice_items` — customer_id, warehouse_id (مخزن أو عهدة مندوب), rep_user_id, uuid, type: cash|credit, subtotal, discount, total, status
- `sales_returns` + `sales_return_items` — invoice_id?, customer_id, warehouse_id, rep_user_id, uuid, restock: bool
- `payments` — company_id, payable_type: customer|supplier, payable_id, direction: in|out, method: cash|cheque|transfer, uuid, rep_user_id, amount

### Rep cycle
- `rep_loadings` + `rep_loading_items` — rep_user_id, from_warehouse, to_warehouse(عهدة), uuid, status
- `rep_settlements` — rep_user_id, uuid, sales_total, returns_total, collected_total, expenses_total, returned_to_stock_total, cash_handover, difference, status: draft|settled
- `expenses` — company_id, expense_category, rep_user_id, uuid, amount, note
- `customer_visits` — company_id, rep_user_id, customer_id, uuid, status: visited|no_answer|postponed, note

### Accounting & System
- `ledger_accounts` — company_id, code, name, type: asset|liability|equity|revenue|expense
- `ledger_entries` — company_id, account_id, ref_type, ref_id, uuid, debit, credit, entry_date
- `settings` — company_id, key, value
- `audit_logs` — company_id, user_id, action, auditable_type, auditable_id, old_values, new_values, ip
- `sync_batches` — company_id, user_id, uuid, device_id, status — لمعالجة مزامنة المندوب ذرّيًا

**قاعدة ثابتة:** كل جدول مستندات مالي/مخزني يحمل `uuid` فريد (idempotency) + `company_id` + indexes مناسبة + FKs.

## 2. القواعد المحاسبية/المخزنية الأساسية

1. **كل مستند = Transaction واحدة** تُنشئ: حركات مخزون (±) + تحديث `stock_balances` + قيود دفتر + تحديث رصيد العميل/المورد.
2. **البيع من عهدة المندوب** يخصم من مخزن العهدة؛ **البيع من المخزن** يخصم من المخزن. نفس المستند `sales_invoice` والفرق `warehouse_id`.
3. **التحصيل النقدي من المندوب** لا يدخل خزينة الشركة مباشرة — يزيد عهدة المندوب المالية (حساب "نقدية لدى المندوبين") حتى التسوية.
4. **التسوية** تقفل الفارق: نقد مُسلَّم + مرتجع للمخزن + تالف + مبيعات آجلة مقيّدة على العملاء = كل ما حُمّل. الفرق ≠ 0 يتطلب قرارًا (عجز يخصم على المندوب / زيادة تُقيّد).
5. **الجرد** يولّد تسوية (فروقات) بمستند `stock_movements` نوع `adjustment` مع سبب.
6. **المرتجع** يعيد للمخزن فقط إذا `restock=true`، وإلا يُقيّد تالفًا (حساب هالك).

## 3. صلاحيات (Permissions seed)

Modules: users, roles, warehouses, products, purchases, inventory, transfers, counts, customers, sales, returns, payments, reps, expenses, reports, settings.
Actions لكل module: view|create|update|delete (+approve للمستندات). أدوار نظامية: owner (الكل)، warehouse_manager، sales_manager، accountant، rep (يقيَّد ببياناته فقط عبر Policies لا أزرار مخفية).

## 4. API للمندوب (Mobile)

- `POST /api/auth/login` (phone+password → token sanctum)
- `GET /api/bootstrap` — عملاؤه، أصناف+أسعار، أرصدة عهدته، أرصدة عملاء مديونيات، إعدادات (دور واحد يجمع كل شيء لتقليل الطلبات offline)
- `POST /api/sync` — payload { device_id, batch_uuid, operations: [ {op_uuid, type: invoice|payment|return|expense|visit, data} ] }
  استجابة لكل op: accepted | duplicate (الرابط الأصلي) | rejected+سبب (تعارض سعر/رصيد)
- `GET /api/my/summary` — أداء المندوب اليوم/الشهر
كل الـ ops بـ idempotency: `op_uuid` فريد ⇒ إعادة الإرسال آمنة.

## 5. دورة عمل المندوب في التطبيق (≤3 نقرات للعملية الأساسية)

1. دخول بجواله → تحميل Bootstrap (يعمل offline بعدها)
2. تحميل عهدة (يظهر له ما عليه)
3. عميل → فاتورة (اختيار أصناف بباركود/بحث، خصم، نقدي/آجل) → إرسال (أو طابور offline)
4. تحصيل من عميل / مرتجع / مصروف — أزرار من شاشة العميل
5. آخر اليوم: ملخص العهدة + طلب تسوية
