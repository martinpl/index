<?php

namespace App\Console\Commands\Concerns;

use App\Models\Entry;
use App\Models\Meta;
use App\Models\Term;

trait ManagesMeta
{
    protected function isSupportedMetaAction(): bool
    {
        $action = $this->argument('action');
        if (in_array($action, ['get', 'set', 'delete', 'list'])) {
            return true;
        }

        $this->error("Action [$action] is not supported.");

        return false;
    }

    protected function manageMeta(Entry|Term $model): int
    {
        if ($this->argument('action') === 'list') {
            return $this->list($model);
        }

        $key = $this->argument('key');
        if ($key === null) {
            $this->error('No key given.');

            return self::FAILURE;
        }

        return match ($this->argument('action')) {
            'get' => $this->get($model, $key),
            'set' => $this->set($model, $key),
            'delete' => $this->delete($model, $key),
        };
    }

    private function list(Entry|Term $model): int
    {
        $meta = $model->meta()->orderBy('key')->get();
        if ($meta->isEmpty()) {
            $this->info('No meta found.');

            return self::SUCCESS;
        }

        $this->table(['key', 'value'], $meta
            ->map(fn (Meta $meta): array => [$meta->key, $this->formatMetaValue($meta->value)]));

        return self::SUCCESS;
    }

    private function get(Entry|Term $model, string $key): int
    {
        if (! $model->hasMeta($key)) {
            return $this->metaDoesNotExist($model, $key);
        }

        $this->line($this->formatMetaValue($model->getMeta($key)));

        return self::SUCCESS;
    }

    private function set(Entry|Term $model, string $key): int
    {
        $value = $this->argument('value');
        if ($value === null) {
            $this->error('No value given.');

            return self::FAILURE;
        }

        $model->setMeta($key, $value);
        $this->info("Set meta [$key] of {$model->getMorphClass()} [{$model->getKey()}].");

        return self::SUCCESS;
    }

    private function delete(Entry|Term $model, string $key): int
    {
        if (! $model->forgetMeta($key)) {
            return $this->metaDoesNotExist($model, $key);
        }

        $this->info("Deleted meta [$key] of {$model->getMorphClass()} [{$model->getKey()}].");

        return self::SUCCESS;
    }

    private function metaDoesNotExist(Entry|Term $model, string $key): int
    {
        $this->error("Meta [$key] of {$model->getMorphClass()} [{$model->getKey()}] does not exist.");

        return self::FAILURE;
    }

    /**
     * Meta set from code may hold non-string values, which the console can only print as JSON.
     */
    private function formatMetaValue(mixed $value): string
    {
        return is_string($value) ? $value : json_encode($value);
    }
}
