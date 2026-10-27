<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use WeakMap;

class AuditObserver
{
    private static ?WeakMap $pending = null;

    public function creating(Model $model): void
    {
        self::map()[$model] = ['action' => 'create', 'old' => [], 'new' => $model->getAttributes()];
    }

    public function updating(Model $model): void
    {
        $new = $model->getDirty();
        $old = [];
        foreach ($new as $key => $_) $old[$key] = $model->getRawOriginal($key);
        self::map()[$model] = ['action' => 'update', 'old' => $old, 'new' => $new];
    }

    public function deleting(Model $model): void
    {
        self::map()[$model] = ['action' => 'delete', 'old' => $model->getAttributes(), 'new' => []];
    }

    public function created(Model $model): void { $this->finish($model, 'create'); }
    public function updated(Model $model): void { $this->finish($model, 'update'); }
    public function deleted(Model $model): void { $this->finish($model, 'delete'); }

    private function finish(Model $model, string $action): void
    {
        if ($model instanceof AuditLog || ! (Auth::user()?->is_admin ?? false)) return;
        $pending = self::map()[$model] ?? null;
        $map = self::map();
        unset($map[$model]);
        if (! $pending) return;
        $resource = class_basename($model);
        $resource = preg_replace('/([a-z])([A-Z])/', '$1 $2', $resource);
        $logger = app(AuditLogger::class);
        $label = $logger->label($model);
        $resource = match (class_basename($model)) {
            'CarModel' => 'Car', 'Blogs' => 'Article', 'BlogCategory' => 'Blog Category',
            'SeoMeta' => 'SEO Metadata', 'HomepageSection' => 'Homepage Section',
            'FeaturedContent' => 'Featured Content', 'SpecificationDefinition' => 'Specification',
            'SpecificationCategory' => 'Specification Category', 'FeatureCategory' => 'Feature Category',
            'VariantSpecification' => 'Variant Specification', 'VariantFeature' => 'Variant Feature',
            default => $resource,
        };
        $statusChange = $action === 'update' && count($pending['new']) <= 2 && array_intersect(['status', 'publication_status', 'enabled'], array_keys($pending['new']));
        $eventAction = $statusChange ? 'status_change' : $action;
        $description = match (true) {
            $statusChange => 'Changed status of '.$resource.' "'.$label.'"',
            $action === 'create' => 'Created '.$resource.' "'.$label.'"',
            $action === 'update' => 'Updated '.$resource.' "'.$label.'"',
            default => 'Deleted '.$resource.' "'.$label.'"',
        };
        $logger->record($eventAction, $resource, $description, $model, $pending['old'], $pending['new']);
    }

    private static function map(): WeakMap
    {
        return self::$pending ??= new WeakMap();
    }
}
