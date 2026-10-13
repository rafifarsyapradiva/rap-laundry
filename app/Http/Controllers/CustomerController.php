<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Tampilkan daftar pelanggan dengan pagination, pencarian, dan filter status.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'active');

        // Batasi nilai filter status yang valid
        if (! in_array($status, ['active', 'inactive', 'archived', 'all'], true)) {
            $status = 'active';
        }

        $customers = Customer::query()
            ->search($search)
            ->statusFilter($status)
            ->withCount('orders')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $counts = [
            'active' => Customer::where('is_active', true)->count(),
            'inactive' => Customer::where('is_active', false)->count(),
            'archived' => Customer::onlyTrashed()->count(),
            'all' => Customer::withTrashed()->count(),
        ];

        return view('customers.index', compact('customers', 'search', 'status', 'counts'));
    }

    /**
     * Tampilkan formulir pembuatan pelanggan baru.
     */
    public function create(): View
    {
        return view('customers.create');
    }

    /**
     * Simpan data pelanggan baru ke database.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse|JsonResponse
    {
        $customer = $this->customerService->createCustomer(
            data: $request->validated(),
            actor: $request->user()
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'formatted_phone' => $customer->formatted_phone,
                    'address' => $customer->address,
                ],
                'message' => "Pelanggan {$customer->name} ({$customer->customer_code}) berhasil didaftarkan.",
            ], 201);
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Pelanggan {$customer->name} ({$customer->customer_code}) berhasil didaftarkan.");
    }

    /**
     * Tampilkan detail profil pelanggan dan riwayat transaksi cuciannya.
     */
    public function show(int $id): View
    {
        /** @var Customer $customer */
        $customer = Customer::withTrashed()->findOrFail($id);

        $metrics = $this->customerService->getCustomerMetrics($customer);

        $orders = $customer->orders()
            ->with(['items', 'payments'])
            ->latest('received_at')
            ->paginate(10)
            ->withQueryString();

        return view('customers.show', compact('customer', 'metrics', 'orders'));
    }

    /**
     * Tampilkan formulir ubah data pelanggan.
     */
    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Perbarui data pelanggan di database.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customerService->updateCustomer(
            customer: $customer,
            data: $request->validated(),
            actor: $request->user()
        );

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Data pelanggan {$customer->name} berhasil diperbarui.");
    }

    /**
     * Arsipkan pelanggan (soft delete dan set status nonaktif).
     */
    public function destroy(Customer $customer, Request $request): RedirectResponse
    {
        $this->customerService->archiveCustomer(
            customer: $customer,
            actor: $request->user()
        );

        return redirect()
            ->route('customers.index')
            ->with('success', "Pelanggan {$customer->name} ({$customer->customer_code}) berhasil diarsipkan.");
    }

    /**
     * Pulihkan pelanggan yang telah diarsipkan.
     */
    public function restore(int $id, Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = Customer::onlyTrashed()->findOrFail($id);

        $this->customerService->restoreCustomer(
            customer: $customer,
            actor: $request->user()
        );

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Pelanggan {$customer->name} ({$customer->customer_code}) berhasil dipulihkan dari arsip.");
    }

    /**
     * Ubah status aktif/nonaktif pelanggan secara cepat.
     */
    public function toggleStatus(Customer $customer, Request $request): RedirectResponse
    {
        $newStatus = $this->customerService->toggleStatus(
            customer: $customer,
            actor: $request->user()
        );

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Status pelanggan {$customer->name} berhasil {$statusText}.");
    }

    /**
     * Hapus permanen pelanggan. Menolak jika memiliki riwayat transaksi!
     */
    public function forceDelete(int $id, Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Administrator/Pemilik yang berwenang melakukan penghapusan permanen.');
        }

        /** @var Customer $customer */
        $customer = Customer::withTrashed()->findOrFail($id);

        try {
            $this->customerService->forceDeleteCustomer(
                customer: $customer,
                actor: $request->user()
            );

            return redirect()
                ->route('customers.index')
                ->with('success', "Pelanggan {$customer->name} berhasil dihapus secara permanen.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
