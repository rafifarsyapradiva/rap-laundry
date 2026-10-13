<?php

namespace App\Http\Controllers;

use App\Enums\BillingUnit;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use App\Services\LaundryServiceManagement;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        protected LaundryServiceManagement $serviceManagement
    ) {}

    /**
     * Tampilkan katalog daftar master layanan dan tarif.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $unit = (string) $request->query('unit', 'all');
        $status = (string) $request->query('status', 'active');

        if (! in_array($status, ['active', 'inactive', 'archived', 'all'], true)) {
            $status = 'active';
        }

        $services = Service::query()
            ->search($search)
            ->billingUnit($unit)
            ->statusFilter($status)
            ->withCount('orderItems')
            ->orderBy('name', 'asc')
            ->paginate(12)
            ->withQueryString();

        $counts = [
            'active' => Service::where('is_active', true)->count(),
            'inactive' => Service::where('is_active', false)->count(),
            'archived' => Service::onlyTrashed()->count(),
            'all' => Service::withTrashed()->count(),
        ];

        $unitCounts = [
            'all' => Service::count(),
            'kg' => Service::where('billing_unit', BillingUnit::Kg->value)->count(),
            'item' => Service::where('billing_unit', BillingUnit::Item->value)->count(),
            'package' => Service::where('billing_unit', BillingUnit::Paket->value)->count(),
        ];

        return view('services.index', compact('services', 'search', 'unit', 'status', 'counts', 'unitCounts'));
    }

    /**
     * Formulir pendaftaran jenis layanan baru (Khusus Admin).
     */
    public function create(Request $request): View
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Administrator/Pemilik yang berwenang menambah layanan baru.');
        }

        return view('services.create');
    }

    /**
     * Simpan master layanan baru ke dalam sistem.
     */
    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $service = $this->serviceManagement->createService(
            data: $request->validated(),
            actor: $request->user()
        );

        return redirect()
            ->route('services.show', $service)
            ->with('success', "Layanan '{$service->name}' berhasil ditambahkan ke master layanan.");
    }

    /**
     * Tampilkan detail spesifikasi layanan dan riwayat pemakaian transaksi.
     */
    public function show(int $id): View
    {
        /** @var Service $service */
        $service = Service::withTrashed()->withCount('orderItems')->findOrFail($id);

        $metrics = $this->serviceManagement->getServiceMetrics($service);

        $recentOrderItems = $service->orderItems()
            ->with(['order.customer'])
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('services.show', compact('service', 'metrics', 'recentOrderItems'));
    }

    /**
     * Formulir ubah data master layanan (Khusus Admin).
     */
    public function edit(int $id, Request $request): View
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Administrator/Pemilik yang berwenang mengubah data layanan.');
        }

        /** @var Service $service */
        $service = Service::findOrFail($id);

        return view('services.edit', compact('service'));
    }

    /**
     * Perbarui master layanan dan catat audit jika harga diubah.
     */
    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->serviceManagement->updateService(
            service: $service,
            data: $request->validated(),
            actor: $request->user()
        );

        return redirect()
            ->route('services.show', $service)
            ->with('success', "Data layanan '{$service->name}' berhasil diperbarui.");
    }

    /**
     * Arsipkan layanan (soft delete dan nonaktifkan) (Khusus Admin).
     */
    public function destroy(Service $service, Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Administrator/Pemilik yang berwenang mengarsipkan layanan.');
        }

        $this->serviceManagement->archiveService(
            service: $service,
            actor: $request->user()
        );

        return redirect()
            ->route('services.index')
            ->with('success', "Layanan '{$service->name}' berhasil diarsipkan dan dinonaktifkan.");
    }

    /**
     * Pulihkan layanan yang telah diarsipkan (Khusus Admin).
     */
    public function restore(int $id, Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Administrator/Pemilik yang berwenang memulihkan layanan.');
        }

        /** @var Service $service */
        $service = Service::onlyTrashed()->findOrFail($id);

        $this->serviceManagement->restoreService(
            service: $service,
            actor: $request->user()
        );

        return redirect()
            ->route('services.show', $service)
            ->with('success', "Layanan '{$service->name}' berhasil dipulihkan dari arsip.");
    }

    /**
     * Ubah status aktif/nonaktif layanan secara cepat (Khusus Admin).
     */
    public function toggleStatus(Service $service, Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Administrator/Pemilik yang berwenang mengubah status layanan.');
        }

        $newStatus = $this->serviceManagement->toggleStatus(
            service: $service,
            actor: $request->user()
        );

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Layanan '{$service->name}' berhasil {$statusText}.");
    }

    /**
     * Hapus permanen layanan (Khusus Admin). Wajib ditolak jika sudah pernah digunakan dalam transaksi!
     */
    public function forceDelete(int $id, Request $request): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Hanya Administrator/Pemilik yang berwenang melakukan penghapusan permanen.');
        }

        /** @var Service $service */
        $service = Service::withTrashed()->findOrFail($id);

        try {
            $this->serviceManagement->forceDeleteService(
                service: $service,
                actor: $request->user()
            );

            return redirect()
                ->route('services.index')
                ->with('success', "Layanan '{$service->name}' berhasil dihapus secara permanen.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
