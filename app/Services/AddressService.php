<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Address;
use App\Models\AddressAudit;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function update(Address $address, array $data, ?Account $admin = null): void
    {
        $data['is_default'] = ! empty($data['is_default']);

        // Không cho phép ghi đè address_type thành null/empty (cột này không cho phép null trong DB)
        if (array_key_exists('address_type', $data) && ($data['address_type'] === null || $data['address_type'] === '')) {
            unset($data['address_type']);
        }

        DB::transaction(function () use ($address, $data, $admin): void {
            $original = $address->getOriginal();

            if ($data['is_default']) {
                Address::where('account_id', $address->account_id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->fill($data);
            $address->save();

            $changes = [];
            foreach ($address->getChanges() as $field => $newValue) {
                if ($field === 'updated_at') {
                    continue;
                }

                $changes[$field] = [
                    'old' => $original[$field] ?? null,
                    'new' => $newValue,
                ];
            }

            if (! empty($changes)) {
                AddressAudit::create([
                    'address_id' => $address->id,
                    'performed_by' => $admin?->id,
                    'action' => 'update',
                    'description' => 'Cập nhật địa chỉ',
                    'changes' => $changes,
                ]);
            }
        });
    }

    public function delete(Address $address, ?Account $admin = null): void
    {
        DB::transaction(function () use ($address, $admin): void {
            AddressAudit::create([
                'address_id' => $address->id,
                'performed_by' => $admin?->id,
                'action' => 'delete',
                'description' => 'Xóa địa chỉ',
                'changes' => [],
            ]);

            $address->delete();
        });
    }

    public function setDefault(Address $address, ?Account $admin = null): void
    {
        DB::transaction(function () use ($address, $admin): void {
            Address::where('account_id', $address->account_id)
                ->update(['is_default' => false]);

            $address->is_default = true;
            $address->save();

            AddressAudit::create([
                'address_id' => $address->id,
                'performed_by' => $admin?->id,
                'action' => 'set_default',
                'description' => 'Đặt làm địa chỉ mặc định',
                'changes' => ['is_default' => ['old' => false, 'new' => true]],
            ]);
        });
    }
}
