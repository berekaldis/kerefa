<?php

namespace App\Http\Controllers\Fb;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\Recipe;
use App\Services\AuditLogService;
use App\Services\BranchContextService;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected AuditLogService $auditLog
    ) {}

    public function index(): View
    {
        $this->authorize('permission', 'fb.recipes.view');

        $recipes = Recipe::with(['menuItem', 'ingredients.inventoryItem'])
            ->where('is_active', true)
            ->paginate(12);

        $inventoryItems = InventoryItem::where('is_active', true)->get();
        $menuItems = MenuItem::where('is_active', true)->get();

        $stats = [
            'total_recipes' => Recipe::count(),
            'avg_food_cost' => Recipe::avg('food_cost_percentage') ?? 0,
            'costing_configured' => Recipe::where('total_cost', '>', 0)->count(),
        ];

        return view('fb.recipes.index', compact('recipes', 'inventoryItems', 'menuItems', 'stats'));
    }
}
