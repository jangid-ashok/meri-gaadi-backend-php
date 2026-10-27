<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    private const SENSITIVE = '/password|token|secret|api.?key|credential|authorization|cookie|private.?key/i';

    public function record(string $action, string $resource, string $description, ?Model $auditable = null, array $old = [], array $new = [], ?User $actor = null): void
    {
        $request = app()->bound('request') ? request() : null;
        $data = [
            'user_id' => ($actor ?? Auth::user())?->getAuthIdentifier(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'resource' => $resource,
            'record_label' => $auditable ? $this->label($auditable) : null,
            'description' => $description,
            'old_values' => $this->cleanFor($auditable, $old),
            'new_values' => $this->cleanFor($auditable, $new),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? mb_substr($request->userAgent(), 0, 2000) : null,
            'request_id' => ($request?->header('X-Request-ID') ?: $request?->header('X-Correlation-ID')) ? mb_substr((string) ($request->header('X-Request-ID') ?: $request->header('X-Correlation-ID')), 0, 100) : null,
            'created_at' => now(),
        ];

        $write = static fn () => AuditLog::query()->create($data);
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($write);
        } else {
            $write();
        }
    }

    public function label(Model $model): string
    {
        foreach (['name', 'title', 'question', 'key', 'file_name', 'filename', 'slug'] as $field) {
            if (filled($model->getAttribute($field))) return mb_substr((string) $model->getAttribute($field), 0, 250);
        }
        return class_basename($model).' #'.$model->getKey();
    }

    public function clean(array $values): array
    {
        $safe = [];
        foreach ($values as $key => $value) {
            if (preg_match(self::SENSITIVE, (string) $key)) continue;
            if (is_object($value)) $value = method_exists($value, 'toArray') ? $value->toArray() : (string) $value;
            if (is_array($value)) $value = $this->clean($value);
            if (is_string($value) && strlen($value) > 4000) $value = mb_substr($value, 0, 4000).'…';
            $safe[$key] = $value;
        }
        return $safe;
    }

    private function cleanFor(?Model $model, array $values): array
    {
        if ($model instanceof Setting && preg_match(self::SENSITIVE, (string) $model->key)) unset($values['value']);
        return $this->clean($values);
    }
}
