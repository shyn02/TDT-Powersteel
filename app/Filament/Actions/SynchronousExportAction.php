<?php

namespace App\Filament\Actions;

use App\Exports\CsvExportFailure;
use App\Exports\SynchronousCsvExporter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SynchronousExportAction extends Action
{
    /**
     * @var class-string<object>
     */
    protected string $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Export')
            ->action(function (SynchronousExportAction $action, Component $livewire): ?BinaryFileResponse {
                if (! $livewire instanceof HasTable) {
                    return null;
                }

                if (! $action->isAuthorized()) {
                    return null;
                }

                $query = $livewire->getFilteredTableQuery();

                if (! $query instanceof Builder) {
                    return null;
                }

                try {
                    return app(SynchronousCsvExporter::class)->download(
                        $query,
                        $action->getExporter()::definition(),
                        now(),
                    );
                } catch (CsvExportFailure $failure) {
                    $action->notifyExportFailure($failure);

                    return null;
                }
            });
    }

    /**
     * @param  class-string<object>  $exporter
     */
    public function exporter(string $exporter): static
    {
        $this->exporter = $exporter;

        return $this;
    }

    /**
     * @return class-string<object>
     */
    public function getExporter(): string
    {
        return $this->exporter;
    }

    public static function getDefaultName(): ?string
    {
        return 'export';
    }

    private function notifyExportFailure(CsvExportFailure $failure): void
    {
        Notification::make()
            ->danger()
            ->title('Export failed')
            ->body("The export could not be created (reference {$failure->correlationId()}). Please retry.")
            ->send();
    }
}
