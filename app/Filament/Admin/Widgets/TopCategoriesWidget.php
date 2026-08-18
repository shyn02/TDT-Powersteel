<?php

namespace App\Filament\Admin\Widgets;

use App\Models\QuoteRequest;
use App\Models\ProductCategory;
use Filament\Widgets\Widget;

class TopCategoriesWidget extends Widget
{
    protected static ?int $sort = -12;

    protected string $view = 'filament.admin.widgets.top-categories-widget';

    protected int|string|array $columnSpan = 'full';

    public function getTopCategories(): array
    {
        $categories = ProductCategory::withCount(['quotes as quote_count' => function ($query) {
            $query->where('status', '!=', 'closed');
        }])
            ->where('is_active', true)
            ->orderByDesc('quote_count')
            ->limit(6)
            ->get();

        $maxCount = max(1, $categories->max('quote_count') ?? 1);

        return $categories->map(function ($cat) use ($maxCount) {
            $totalForCat = QuoteRequest::where('category_id', $cat->id)->count();
            return [
                'name' => $cat->name,
                'open' => $cat->quote_count,
                'total' => $totalForCat,
                'percent' => $totalForCat > 0 ? round(($cat->quote_count / $maxCount) * 100) : 0,
            ];
        })->toArray();
    }
}
