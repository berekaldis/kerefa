<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'settings.view');

        $settings = SystemSetting::whereNull('branch_id')->get()->groupBy('category');

        return view('core.settings.index', [
            'groupedSettings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('permission', 'settings.manage');

        $data = $request->except(['_token', '_method']);

        // Explicit check: Ensure bill_generation_enabled cannot be inadvertently turned on without management override
        if (isset($data['bill_generation_enabled']) && $data['bill_generation_enabled'] == '1') {
            return back()->with('error', __('Customer bill generation is not authorized for activation in this release.'));
        }

        foreach ($data as $key => $value) {
            $setting = SystemSetting::whereNull('branch_id')->where('key', $key)->first();
            if ($setting) {
                $setting->value = $value;
                $setting->save();
            }
        }

        $this->auditLog->log(
            action: 'update',
            module: 'settings',
            description: 'Updated global system settings'
        );

        return back()->with('success', __('System settings updated successfully.'));
    }
}
