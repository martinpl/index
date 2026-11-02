<?php

namespace App\Foundation;

use App\Facades\Filter;
use App\Fields\Field;
use App\Fields\Group;
use App\Models\Concerns\HasMeta;
use Closure;
use DomainException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class Metabox
{
    /**
     * @var array<string, array{key: string, render: Closure|null, fields: Closure|null, with: array<string, mixed>, location: string|list<string>, can: string|null, position: int, owner: string}>
     */
    protected array $metaboxes = [];

    /**
     * Register a box shown at the given location, or any of a list of them, whose `with` data, like
     * the `title` heading the box, describes it. The box shows its `fields`, stored as meta of the
     * edited model named after each field, followed by whatever its `render` callback returns.
     * The entry editor shows the boxes of the `entry` location and of `entry.{type}` for its entry type,
     * passing their callbacks the entry being edited, or null when adding one.
     *
     * @param  array{render?: Closure(mixed...): (View|Htmlable|string), fields?: Closure(): list<Field>, with?: array<string, mixed>, location?: string|list<string>, can?: string|null, position?: int}  $config
     * @param  string  $owner  Same format as the data owner column, e.g. `core` or `plugins/blog`.
     */
    public function register(string $key, array $config, string $owner): void
    {
        if ($existing = $this->get($key)) {
            throw new DomainException("Metabox [$key] is already registered by [{$existing['owner']}].");
        }

        $this->metaboxes[$key] = [
            'key' => $key,
            'render' => $config['render'] ?? null,
            'fields' => $config['fields'] ?? null,
            'with' => ['title' => Str::headline($key), ...$config['with'] ?? []],
            'location' => $config['location'] ?? 'entry',
            'can' => $config['can'] ?? null,
            'position' => $config['position'] ?? 10,
            'owner' => $owner,
        ];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->metaboxes);
    }

    /**
     * @return array{key: string, render: Closure|null, fields: Closure|null, with: array<string, mixed>, location: string|list<string>, can: string|null, position: int, owner: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->metaboxes[$key] ?? null;
    }

    /**
     * @return Collection<string, array{key: string, render: Closure|null, fields: Closure|null, with: array<string, mixed>, location: string|list<string>, can: string|null, position: int, owner: string}>
     */
    public function list(): Collection
    {
        return collect($this->metaboxes)->sortBy('position');
    }

    /**
     * The metaboxes of any of the locations the current user may see, sorted by position.
     *
     * @param  string|list<string>  $locations
     * @return Collection<string, array{key: string, render: Closure|null, fields: Closure|null, with: array<string, mixed>, location: string|list<string>, can: string|null, position: int, owner: string}>
     */
    public function for(string|array $locations): Collection
    {
        return $this->list()->filter(fn (array $metabox): bool => array_intersect((array) $metabox['location'], (array) $locations)
            && (is_null($metabox['can']) || Gate::allows($metabox['can'])));
    }

    /**
     * The visible fields of the metabox, which plugins may change through the `metaboxes.{key}.fields` filter.
     *
     * @return list<Field>
     */
    public function fields(string $key): array
    {
        if (! $this->metaboxes[$key]['fields']) {
            return [];
        }

        $fields = Filter::apply("metaboxes.$key.fields", ($this->metaboxes[$key]['fields'])(), $key);

        return array_values(array_filter($fields, fn (Field $field): bool => $field->isVisible()));
    }

    /**
     * The fields of the metabox grouped under its key.
     */
    public function form(string $key): Group
    {
        return Group::make($key)->fields($this->fields($key));
    }

    /**
     * The values of the metabox stored as meta of the model, keyed by field name, falling back to each field's default.
     *
     * @param  (Model&HasMeta)|null  $model  Null when the model is being added.
     * @return array<string, mixed>
     */
    public function values(string $key, ?Model $model): array
    {
        return $this->form($key)->cast(collect($this->fields($key))
            ->mapWithKeys(fn (Field $field): array => [$field->name => $model?->getMeta($field->name)])
            ->all());
    }

    /**
     * Store the given values of the metabox as meta of the model, forgetting empty ones and ignoring any value the metabox has no field for.
     *
     * @param  Model&HasMeta  $model
     * @param  array<string, mixed>  $values
     */
    public function save(string $key, Model $model, array $values): void
    {
        foreach ($this->form($key)->cast($values) as $name => $value) {
            $model->setMeta($name, $value, $this->metaboxes[$key]['owner']);
        }
    }
}
