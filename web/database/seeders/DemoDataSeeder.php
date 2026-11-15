<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RepLoading;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ExpenseService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\RepLoadingService;
use App\Services\SalesReturnService;
use App\Services\SalesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = \App\Models\Company::orderBy('id')->value('id') ?? 1;
        app()->instance('current.company.id', $companyId);

        if (Product::count() > 0) {
            return;
        }

        $owner = User::where('company_id', $companyId)->first();
        $main = Warehouse::where('company_id', $companyId)->where('type', 'main')->orderBy('id')->first();
        $branch = Warehouse::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'مخزن الجيزة'],
            ['type' => 'main']
        );

        $unit = Unit::firstOrCreate(
            ['company_id' => $companyId, 'short_name' => 'كرتونة'],
            ['name' => 'كرتونة']
        );

        $catalog = [
            ['زيوت وسمن', 'النخبة', [['زيت نباتي 1 لتر ×12', 320, 400, 80], ['زيت ذرة 800مل ×12', 350, 430, 60], ['سمن صناعي 1كج ×12', 380, 460, 40]]],
            ['سكريات وأرز', 'الفجر', [['سكر أبيض 1كج ×10', 120, 145, 100], ['أرز مصري 1كج ×10', 180, 220, 70]]],
            ['مشروبات', 'سنترال', [['عصير برتقال 1ل ×12', 140, 180, 90], ['مياه معدنية 1.5ل ×6', 45, 60, 200], ['مشروب غازي 330مل ×24', 200, 250, 60]]],
            ['منظفات', 'بيور', [['سائل أطباق 1ل ×12', 130, 165, 50], ['مسحوق غسيل 3كج ×6', 240, 300, 35], ['مطهر أرضيات 1ل ×12', 110, 140, 45]]],
            ['حلويات وسناكس', 'لمسة', [['بسكويت شاي ×24', 90, 115, 120], ['شيبسي كبير ×24', 95, 120, 100]]],
        ];

        $suppliers = collect([
            ['شركة النخبة للتوزيع', '01011122233'],
            ['مؤسسة الفجر للأغذية', '01044455566'],
            ['سنترال للمشروبات', '01077788899'],
            ['بيور للمنظفات', '01222233344'],
        ])->map(fn ($s) => Supplier::create(['name' => $s[0], 'phone' => $s[1]]));

        $products = collect();
        foreach ($catalog as [$categoryName, $brand, $items]) {
            $category = \App\Models\Category::firstOrCreate(
                ['company_id' => $companyId, 'name' => $categoryName],
                []
            );
            foreach ($items as [$name, $cost, $price, $minStock]) {
                $products->push(Product::create([
                    'company_id' => $companyId,
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'name' => $name,
                    'brand' => $brand,
                    'cost_price' => $cost,
                    'sale_price' => $price,
                    'min_stock' => $minStock,
                ]));
            }
        }

        $purchasesBySupplier = $products->chunk(4);
        foreach ($purchasesBySupplier as $index => $chunk) {
            app(PurchaseService::class)->createPurchase([
                'uuid' => (string) Str::uuid(),
                'supplier_id' => $suppliers[$index % $suppliers->count()]->id,
                'warehouse_id' => $main->id,
                'payment_type' => $index % 2 ? 'credit' : 'cash',
                'purchase_date' => now()->subDays(5 - $index)->toDateString(),
                'items' => $chunk->map(fn ($p) => ['product_id' => $p->id, 'qty' => rand(80, 200), 'unit_cost' => $p->cost_price])->all(),
            ], $owner);
        }

        app(\App\Services\TransferService::class)->create([
            'uuid' => (string) Str::uuid(),
            'from_warehouse_id' => $main->id,
            'to_warehouse_id' => $branch->id,
            'items' => $products->random(4)->map(fn ($p) => ['product_id' => $p->id, 'qty' => rand(15, 40)])->all(),
        ], $owner);

        $customersData = [
            ['بقالة النور — مدينة نصر', '0120001111', 'مدينة نصر', 2000],
            ['سوبر ماركت الأمانة — المعادي', '0120002222', 'المعادي', 5000],
            ['بقالة الشيخ — حلوان', '0120003333', 'حلوان', 1500],
            ['ماركت الركن — الهرم', '0120004444', 'الجيزة', 3000],
            ['بقالة الصفا — شبرا', '0120005555', 'شبرا', 800],
            ['سوبر ماركت المدينة — التجمع', '0120006666', 'التجمع', 4000],
            ['بقالة البركة — الزاوية', '0120007777', 'حلوان', 600],
            ['ماركت النسيم — العاشر', '0120008888', 'العاشر', 2500],
        ];
        $customers = collect($customersData)->map(fn ($c) => Customer::create([
            'name' => $c[0], 'phone' => $c[1], 'area' => $c[2], 'credit_limit' => $c[3],
        ]));

        $reps = User::where('company_id', $companyId)->whereHas('custodyWarehouse')->get();
        $mainReps = $reps->take(2);
        $extraRep = $reps->first();

        if ($reps->count() < 2) {
            $repRole = \App\Models\Role::firstOrCreate(
                ['company_id' => $companyId, 'name' => 'مندوب توزيع'],
                ['is_system' => true]
            );
            $repRole = \App\Models\Role::firstOrCreate(["company_id" => $companyId, "name" => "مندوب توزيع"], ["is_system" => true]);
            $secondRep = User::create([
                'company_id' => $companyId,
                'name' => 'مندوب الجيزة',
                'phone' => '01000000003',
                'password' => 'rep123',
            ]);
            $secondRep->roles()->attach($repRole);
            app(\App\Services\RepCustodyService::class)->ensureCustody($secondRep);
            $mainReps = $mainReps->push($secondRep);
        }

        $mainReps = User::where("company_id", $companyId)->whereHas("custodyWarehouse")->get();
        foreach ($mainReps as $i => $rep) {
            app(RepLoadingService::class)->create([
                'uuid' => (string) Str::uuid(),
                'rep_user_id' => $rep->id,
                'from_warehouse_id' => $main->id,
                'items' => $products->random(6)->map(fn ($p) => ['product_id' => $p->id, 'qty' => rand(10, 30)])->all(),
            ], $owner);

            $custody = $rep->custodyWarehouse;
            $custodyProducts = StockBalance::where('warehouse_id', $custody->id)->where('qty', '>', 0)->get();

            foreach (range(1, 4) as $day) {
                $customer = $customers->random();
                $items = $custodyProducts->random(min(3, $custodyProducts->count()))
                    ->map(fn ($b) => ['product_id' => $b->product_id, 'qty' => rand(1, min(6, (int) $b->qty)), 'unit_price' => Product::find($b->product_id)->sale_price])->all();
                if (empty($items)) {
                    continue;
                }
                $type = $day % 3 === 0 ? 'credit' : 'cash';
                try {
                app(SalesService::class)->createInvoice([
                    'uuid' => (string) Str::uuid(),
                    'customer_id' => $customer->id,
                    'warehouse_id' => $custody->id,
                    'rep_user_id' => $rep->id,
                    'type' => $type,
                    'channel' => 'mobile',
                    'issued_at' => now()->subDays($day),
                    'items' => $items,
                ], $rep);
                } catch (\DomainException) {
                }
            }

            app(ExpenseService::class)->createExpense([
                'rep_user_id' => $rep->id,
                'uuid' => (string) Str::uuid(),
                'category' => 'بنزين',
                'amount' => 120,
                'issued_at' => now()->subDay(),
            ], $rep);
        }

        $debtors = $customers->filter(fn ($c) => $c->refresh()->balance > 0);
        foreach ($debtors->take(3) as $customer) {
            if ($customer->balance > 0) {
                try {
                app(PaymentService::class)->createPayment([
                    'uuid' => (string) Str::uuid(),
                    'payable_type' => 'customer',
                    'payable_id' => $customer->id,
                    'direction' => 'in',
                    'method' => 'cash',
                    'amount' => round((float) $customer->balance * 0.5, 2),
                ], $owner);
                } catch (\DomainException) {
                }
            }
        }

        $webInvoiceCustomer = $customers->first();
        $webInvoiceCustomer->update(['credit_limit' => 999999]);
        try {
        app(SalesService::class)->createInvoice([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $webInvoiceCustomer->id,
            'warehouse_id' => $main->id,
            'type' => 'credit',
            'collected_amount' => 200,
            'items' => $products->random(3)->map(fn ($p) => ['product_id' => $p->id, 'qty' => rand(2, 8), 'unit_price' => $p->sale_price])->all(),
        ], $owner);
        } catch (\DomainException) {
        }

        $this->command->info('DemoData: '.$products->count().' صنف ('.$products->pluck('brand')->unique()->implode('، ').')، '.Customer::count().' عميل، '.Purchase::count().' شراء، '.SalesInvoice::count().' فاتورة');
    }
}
