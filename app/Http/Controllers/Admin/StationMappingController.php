<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\OrderStation;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StationMappingController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('permission', 'settings.view');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);
        $branch = $this->branchContext->getActiveBranch($user);

        $stations = OrderStation::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })
            ->withCount(['categories', 'menuItems', 'tickets'])
            ->orderBy('name')
            ->get();

        $categories = MenuCategory::where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->orWhereNull('branch_id');
        })
            ->where('is_active', true)
            ->get();

        return view('admin.stations.index', [
            'branch' => $branch,
            'stations' => $stations,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('permission', 'settings.manage');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'station_type' => ['required', 'string', 'in:kitchen,bar,grill,cafe,other'],
            'printer_model' => ['required', 'string', 'max:100'],
            'printer_connection' => ['required', 'string', 'in:thermal_80mm,network_ip,usb_spooler'],
            'printer_ip' => ['nullable', 'string', 'max:100'],
            'printer_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'paper_width_mm' => ['required', 'integer', 'in:80,58'],
            'auto_print' => ['nullable', 'boolean'],
            'print_copies' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:500'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:menu_categories,id'],
        ]);

        $validated['branch_id'] = $branchId;
        $validated['auto_print'] = $request->has('auto_print');
        $validated['is_active'] = true;

        $station = OrderStation::create($validated);

        // Map categories if provided
        if (! empty($validated['category_ids'])) {
            MenuCategory::whereIn('id', $validated['category_ids'])->update(['order_station_id' => $station->id]);
            MenuItem::whereIn('category_id', $validated['category_ids'])->update(['order_station_id' => $station->id]);
        }

        $this->auditLog->log(
            'create',
            'admin',
            "Created order station [{$station->name}] with printer [{$station->printer_model}].",
            $station,
            null,
            $station->toArray(),
            $branchId
        );

        return redirect()->route('admin.stations.index')
            ->with('success', __('Order station ":name" created with printer ":model" configured.', [
                'name' => $station->name,
                'model' => $station->printer_model,
            ]));
    }

    public function update(Request $request, OrderStation $station): RedirectResponse
    {
        $this->authorize('permission', 'settings.manage');

        $user = Auth::user();
        $branchId = $this->branchContext->getActiveBranchId($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
            'station_type' => ['required', 'string', 'in:kitchen,bar,grill,cafe,other'],
            'printer_model' => ['required', 'string', 'max:100'],
            'printer_connection' => ['required', 'string', 'in:thermal_80mm,network_ip,usb_spooler'],
            'printer_ip' => ['nullable', 'string', 'max:100'],
            'printer_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'paper_width_mm' => ['required', 'integer', 'in:80,58'],
            'auto_print' => ['nullable', 'boolean'],
            'print_copies' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:500'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:menu_categories,id'],
        ]);

        $old = $station->toArray();
        $validated['auto_print'] = $request->has('auto_print');

        $station->update($validated);

        // Update category mappings
        if (isset($validated['category_ids'])) {
            // Detach previous categories
            MenuCategory::where('order_station_id', $station->id)->update(['order_station_id' => null]);
            // Attach selected
            MenuCategory::whereIn('id', $validated['category_ids'])->update(['order_station_id' => $station->id]);
            MenuItem::whereIn('category_id', $validated['category_ids'])->update(['order_station_id' => $station->id]);
        }

        $this->auditLog->log(
            'update',
            'admin',
            "Updated order station [{$station->name}].",
            $station,
            $old,
            $station->fresh()->toArray(),
            $branchId
        );

        return redirect()->route('admin.stations.index')
            ->with('success', __('Order station ":name" updated successfully.', ['name' => $station->name]));
    }

    public function toggle(OrderStation $station): RedirectResponse
    {
        $this->authorize('permission', 'settings.manage');

        $user = Auth::user();
        $station->is_active = ! $station->is_active;
        $station->save();

        $statusStr = $station->is_active ? 'activated' : 'deactivated';

        $this->auditLog->log(
            'toggle_status',
            'admin',
            "Station [{$station->name}] {$statusStr}.",
            $station,
            null,
            ['is_active' => $station->is_active],
            $station->branch_id
        );

        return redirect()->route('admin.stations.index')
            ->with('success', __('Station :name has been :status.', ['name' => $station->name, 'status' => $statusStr]));
    }

    public function destroy(OrderStation $station): RedirectResponse
    {
        $this->authorize('permission', 'settings.manage');

        $user = Auth::user();

        if ($station->tickets()->exists()) {
            $station->is_active = false;
            $station->save();

            return redirect()->route('admin.stations.index')
                ->with('info', __('Station :name has associated ticket history and has been deactivated rather than deleted.', ['name' => $station->name]));
        }

        $old = $station->toArray();
        $station->delete();

        $this->auditLog->log(
            'delete',
            'admin',
            "Deleted station [{$old['name']}].",
            null,
            $old,
            null,
            $old['branch_id']
        );

        return redirect()->route('admin.stations.index')
            ->with('success', __('Station :name deleted successfully.', ['name' => $old['name']]));
    }
}
