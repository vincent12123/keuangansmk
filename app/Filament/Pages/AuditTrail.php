<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class AuditTrail extends Page
{
    use WithPagination;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Audit Trail';

    protected static ?string $slug = 'audit-trail';

    protected string $view = 'filament.pages.audit-trail';

    public ?string $logName = null;

    public ?string $event = null;

    public ?int $causerId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $search = '';

    public int $perPage = 20;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || ($user?->hasPermissionTo('view_audit_trail') ?? false);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['logName', 'event', 'causerId', 'dateFrom', 'dateTo', 'search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->logName = null;
        $this->event = null;
        $this->causerId = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->search = '';
        $this->perPage = 20;
        $this->resetPage();
    }

    public function getActivitiesProperty(): LengthAwarePaginator
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when(filled($this->logName), fn ($query) => $query->where('log_name', $this->logName))
            ->when(filled($this->event), fn ($query) => $query->where('event', $this->event))
            ->when(filled($this->causerId), fn ($query) => $query->where('causer_id', $this->causerId))
            ->when(filled($this->dateFrom), fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when(filled($this->dateTo), fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when(filled($this->search), function ($query) {
                $search = '%' . trim($this->search) . '%';

                $query->where(function ($inner) use ($search) {
                    $inner->where('description', 'like', $search)
                        ->orWhere('log_name', 'like', $search)
                        ->orWhere('subject_type', 'like', $search)
                        ->orWhere('causer_type', 'like', $search)
                        ->orWhere('properties', 'like', $search);
                });
            })
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function getLogNameOptionsProperty(): array
    {
        return Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name', 'log_name')
            ->all();
    }

    public function getEventOptionsProperty(): array
    {
        return Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event', 'event')
            ->all();
    }

    public function getCauserOptionsProperty(): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function subjectLabel(Activity $activity): string
    {
        if (! $activity->subject_type) {
            return '-';
        }

        $base = class_basename($activity->subject_type);

        return $activity->subject_id
            ? "{$base} #{$activity->subject_id}"
            : $base;
    }

    public function causerLabel(Activity $activity): string
    {
        if ($activity->causer?->name) {
            return $activity->causer->name;
        }

        if ($activity->causer_type && $activity->causer_id) {
            return class_basename($activity->causer_type) . " #{$activity->causer_id}";
        }

        return 'System';
    }

    public function propertiesJson(Activity $activity): string
    {
        $properties = $activity->properties?->toArray() ?? [];

        return $properties === []
            ? '{}'
            : (string) json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
