# StockFlow — نظام إدارة المخازن والتوزيع

SaaS عربي RTL لإدارة المخازن والمبيعات وتوزيع مندوبي المبيعات مع تطبيق موبايل offline-first.

## البنية
```
StockFlow/
├── web/   Laravel 12 + Blade + Sanctum (الإدارة + API المندوبين)
├── mob/   Flutter (تطبيق المندوب — Android، Clean Architecture + Bloc)
└── docs/  الاستراتيجية، الفجوات التجارية، خطة التجربة الميدانية
```

## التشغيل السريع (الويب)
```bash
cd web
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # أو اضبط MySQL في .env
php artisan migrate --seed       # شركة نموذجية + 13 صنفًا بـ5 براندات + دورة حقيقية
php artisan serve
# دخول: 01000000001 / owner123 (مالك) — 01000000002 / rep123 (مندوب)
```

## تطبيق المندوب
```bash
cd mob
flutter pub get
flutter run                    # أو: flutter build apk --release
# من شاشة الدخول: إعدادات الخادم ← http://<عنوان-جهازك>:8000
```

## الاختبارات
```bash
cd web
vendor/bin/phpunit                                  # 17 اختبارًا / 187 assertion (منطق + صلاحيات مصفوفية)
php tests/e2e/deep_web_e2e.php                      # 67 فحص E2E عبر HTTP حقيقي
php tests/e2e/deep_probe.php                        # سيناريوهات هجومية (توازن الدفتر، إلخ)
cd ../mob && flutter test                           # 10 اختبارات وحدة + offline queue بـSQLite حقيقي
```

## المزايا الأساسية
- دورة مندوب مغلقة: تحميل عهدة → بيع/تحصيل/مرتجع من التطبيق (offline + مزامنة idempotent) → تسوية عهدة بمطابقة نقد
- محاسبة تلقائية متوازنة: كل مستند يولّد قيودًا (دفتر مُختبر التوازن في كل السيناريوهات)
- صلاحيات دقيقة لكل وحدة (view/create/update/delete) + عزل شركات صارم (مُختبر IDOR ومصفوفة أدوار)
- قواعد عمل محمية: حد آجل، سعر معتمد للسند، منع مرتجع زائد، إلغاء عكسي كامل، شيكات تحت التحصيل
- واجهة عربية RTL كاملة بثيم فاتح/داكن ولوحة متابعة بمخازن وبراندات وإجراءات سريعة

راجع `docs/` للاستراتيجية وسجل الفجوات التجارية (المفتق والمغلق) وخطة التجربة الميدانية.
