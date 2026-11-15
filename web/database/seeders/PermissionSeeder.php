<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private const MODULES = [
        'users', 'roles', 'warehouses', 'products', 'purchases', 'inventory', 'suppliers',
        'customers', 'sales', 'returns', 'payments', 'reps', 'expenses', 'reports', 'settings',
    ];

    private const ACTIONS = [
        'view' => 'عرض',
        'create' => 'إضافة',
        'update' => 'تعديل',
        'delete' => 'حذف',
    ];

    private const MODULE_NAMES = [
        'users' => 'المستخدمين',
        'roles' => 'الأدوار',
        'warehouses' => 'المخازن',
        'products' => 'الأصناف',
        'purchases' => 'المشتريات',
        'suppliers' => 'الموردين',
        'inventory' => 'المخزون',
        'customers' => 'العملاء',
        'sales' => 'المبيعات',
        'returns' => 'المرتجعات',
        'payments' => 'التحصيلات',
        'reps' => 'المندوبين',
        'expenses' => 'المصروفات',
        'reports' => 'التقارير',
        'settings' => 'الإعدادات',
    ];

    public static function moduleNames(): array
    {
        return self::MODULE_NAMES;
    }

    public function run(): void
    {
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action => $nameAr) {
                Permission::updateOrCreate(
                    ['key' => $module.'.'.$action],
                    ['module' => $module, 'action' => $action, 'name_ar' => $nameAr.' '.self::MODULE_NAMES[$module]]
                );
            }
        }
    }
}
