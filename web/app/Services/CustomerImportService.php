<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;

class CustomerImportService
{
    public function import(string $filePath, ?User $actor = null): array
    {
        $created = 0;
        $skipped = 0;
        $handle = fopen($filePath, 'r');

        $header = fgetcsv($handle);
        $map = $this->mapHeader($header ?: []);

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim($row[$map['name']] ?? '');
            if ($name === '' || mb_strlen($name) > 150) {
                $skipped++;
                continue;
            }

            $phone = trim($row[$map['phone']] ?? '') ?: null;

            if ($phone && Customer::where('phone', $phone)->exists()) {
                $skipped++;
                continue;
            }

            Customer::create([
                'name' => $name,
                'phone' => $phone,
                'area' => trim($row[$map['area']] ?? '') ?: null,
                'address' => trim($row[$map['address']] ?? '') ?: null,
                'credit_limit' => (float) (trim($row[$map['credit_limit']] ?? '') ?: 0),
                'created_by' => $actor?->id,
            ]);
            $created++;
        }

        fclose($handle);

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function mapHeader(array $header): array
    {
        $normalized = array_map(fn ($h) => str_replace([' ', '_'], '', trim(mb_strtolower($h ?? ''))), $header);

        $find = function (array $aliases) use ($normalized) {
            foreach ($aliases as $alias) {
                $pos = array_search($alias, $normalized, true);
                if ($pos !== false) {
                    return (int) $pos;
                }
            }

            return null;
        };

        return [
            'name' => $find(['الاسم', 'name', 'اسم']) ?? 0,
            'phone' => $find(['الهاتف', 'الجوال', 'الموبايل', 'phone', 'mobile']) ?? 1,
            'area' => $find(['المنطقة', 'area']) ?? 2,
            'address' => $find(['العنوان', 'address']) ?? 3,
            'credit_limit' => $find(['حدالآجل', 'الحد', 'creditlimit']) ?? 4,
        ];
    }
}
