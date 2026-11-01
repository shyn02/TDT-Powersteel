<?php

namespace App\Filament\Admin\Resources\ProductApplications\Pages;

use App\Filament\Admin\Resources\ProductApplications\ProductApplicationResource;
use App\Models\ProductApplication;
use App\Support\MapPosition;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Bulk position editor: every Product Application's circle and leader-line
 * anchor on one artwork, moved together and saved in one go.
 *
 * Nothing is written until Save, so an exploratory drag can be abandoned by
 * leaving the page.
 */
class EditProductApplicationPositions extends Page
{
    protected static string $resource = ProductApplicationResource::class;

    protected string $view = 'filament.admin.product-application-positions';

    /** @var list<array<string, mixed>> */
    public array $items = [];

    public function mount(): void
    {
        $this->items = $this->currentItems();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to list')
                ->url(ProductApplicationResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    /**
     * Persist every dragged position in one transaction.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function save(array $items): array
    {
        $allowed = ProductApplication::query()->pluck('id')->all();

        DB::transaction(function () use ($items, $allowed): void {
            foreach ($items as $item) {
                $id = (int) ($item['id'] ?? 0);

                if (! in_array($id, $allowed, true)) {
                    continue;
                }

                $position = MapPosition::from($item['x'] ?? null, $item['y'] ?? null);

                $hasAnchor = isset($item['ax'], $item['ay'])
                    && is_numeric($item['ax'])
                    && is_numeric($item['ay']);

                $anchor = $hasAnchor
                    ? MapPosition::from($item['ax'], $item['ay'])
                    : null;

                ProductApplication::query()->whereKey($id)->update([
                    'x' => $this->clamp($position->x()),
                    'y' => $this->clamp($position->y()),
                    'anchor_x' => $anchor ? $this->clamp($anchor->x()) : null,
                    'anchor_y' => $anchor ? $this->clamp($anchor->y()) : null,
                ]);
            }
        });

        $this->items = $this->currentItems();

        Notification::make()
            ->title('Positions saved')
            ->success()
            ->send();

        return $this->items;
    }

    /** @return list<array<string, mixed>> */
    private function currentItems(): array
    {
        return ProductApplication::query()
            ->inMapOrder()
            ->get()
            ->map(function (ProductApplication $application): array {
                $anchor = $application->anchorPosition();

                return [
                    'id' => $application->id,
                    'label' => $application->label,
                    'x' => $application->position()->x(),
                    'y' => $application->position()->y(),
                    'ax' => $anchor?->x(),
                    'ay' => $anchor?->y(),
                    'thumbnail' => $application->thumbnailUrl(),
                ];
            })
            ->all();
    }

    private function clamp(float $value): float
    {
        return round(max(0, min(100, $value)), 2);
    }
}
