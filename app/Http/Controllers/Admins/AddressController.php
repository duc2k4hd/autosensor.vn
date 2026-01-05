<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddressFilterRequest;
use App\Http\Requests\Admin\AddressUpdateRequest;
use App\Models\Account;
use App\Models\Address;
use App\Models\AddressAudit;
use App\Services\AddressService;

class AddressController extends Controller
{
    public function __construct(protected AddressService $addressService) {}

    public function index(AddressFilterRequest $request)
    {
        $query = Address::with('account')->filter($request->validated());

        $addresses = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('admins.addresses.index', [
            'addresses' => $addresses,
            'filters' => $request->validated(),
            'accounts' => Account::orderBy('name')->select('id', 'name', 'email')->get(),
        ]);
    }

    public function show(Address $address)
    {
        $address->load('account');

        return view('admins.addresses.show', [
            'address' => $address,
            'audits' => AddressAudit::where('address_id', $address->id)->latest()->limit(20)->get(),
        ]);
    }

    public function edit(Address $address)
    {
        $address->load('account');

        return view('admins.addresses.edit', [
            'address' => $address,
            'accounts' => Account::orderBy('name')->select('id', 'name')->get(),
        ]);
    }

    public function update(AddressUpdateRequest $request, Address $address)
    {
        $data = $request->validated();

        /** @var Account|null $admin */
        $admin = auth('admin')->user();

        $this->addressService->update($address, $data, $admin);

        return redirect()->route('admin.addresses.show', $address)->with('success', 'Đã cập nhật địa chỉ.');
    }

    public function destroy(Address $address)
    {
        /** @var Account|null $admin */
        $admin = auth('admin')->user();

        $this->addressService->delete($address, $admin);

        return redirect()->route('admin.addresses.index')->with('success', 'Đã xóa địa chỉ.');
    }

    public function setDefault(Address $address)
    {
        /** @var Account|null $admin */
        $admin = auth('admin')->user();

        $this->addressService->setDefault($address, $admin);

        return back()->with('success', 'Đã đặt làm địa chỉ mặc định.');
    }

    public function searchAccounts()
    {
        $keyword = request('keyword', '');
        $limit = min((int) request('limit', 20), 100);

        $query = Account::select('id', 'name', 'email')
            ->orderBy('name');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $accounts = $query->limit($limit)->get();

        return response()->json(
            $accounts->map(fn ($account) => [
                'value' => $account->id,
                'text' => "{$account->name} ({$account->email})",
            ])
        );
    }

    public function searchProvinces()
    {
        $keyword = request('keyword', '');
        $limit = min((int) request('limit', 100), 100);

        $query = Address::select('province')
            ->distinct()
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->orderBy('province');

        if ($keyword) {
            $query->where('province', 'like', "%{$keyword}%");
        }

        $provinces = $query->limit($limit)->pluck('province')->unique()->values();

        return response()->json(
            $provinces->map(fn ($province) => [
                'value' => $province,
                'text' => $province,
            ])
        );
    }

    public function searchDistricts()
    {
        $keyword = request('keyword', '');
        $province = request('province', '');
        $limit = min((int) request('limit', 100), 100);

        $query = Address::select('district')
            ->distinct()
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->orderBy('district');

        if ($province) {
            $query->where('province', $province);
        }

        if ($keyword) {
            $query->where('district', 'like', "%{$keyword}%");
        }

        $districts = $query->limit($limit)->pluck('district')->unique()->values();

        return response()->json(
            $districts->map(fn ($district) => [
                'value' => $district,
                'text' => $district,
            ])
        );
    }
}
