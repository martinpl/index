<?php

namespace App\Foundation;

use App\Facades\Filter;
use App\Models\Concerns\HasMeta;
use App\Schema\Component;
use App\Schema\Field;
use App\Schema\Group;
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
     * @var array<string, array{key: string, render: Closure|null, schema: Closure|null, values: Closure|null, save: Closure|null, with: array<string, mixed>, location: string|list<string>|Closure, can: string|null, position: int, owner: string}>
     */
    protected array $metaboxes = [];

    /**
     * Register a box shown at the given location, or any of a list of them, or of the ones a closure returns when the editor
     * shows, like the editors of the entry types registered after the box, whose `with` data, like
     * the `title` heading the box, describes it, while a `seamless` box shows its content in line,
     * with no collapsible heading. The box shows its `schema`, whose fields are stored as meta of the edited
     * model named after each field, followed by whatever its `render` callback returns. Fields stored
     * elsewhere are read by the `values` callback, receiving the model, and stored by the `save`
     * callback, receiving the model and the cast values.
     * The entry editor shows the boxes of the `entry` location and of `entry.{type}` for its entry type,
     * passing their callbacks the entry being edited, or null when adding one.
     *
     * @param  array{render?: Closure(mixed...): (View|Htmlable|string), schema?: Closure(): list<Component>, values?: Closure(Model|null): array<string, mixed>, save?: Closure(Model, array<string, mixed>): void, with?: array<string, mixed>, location?: string|list<string>|Closure(): list<string>, can?: string|null, position?: int}  $config
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
            'schema' => $config['schema'] ?? null,
            'values' => $config['values'] ?? null,
            'save' => $config['save'] ?? null,
            'with' => ['title' => Str::headline($key), 'seamless' => false, ...$config['with'] ?? []],
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
     * @return array{key: string, render: Closure|null, schema: Closure|null, values: Closure|null, save: Closure|null, with: array<string, mixed>, location: string|list<string>|Closure, can: string|null, position: int, owner: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->metaboxes[$key] ?? null;
    }

    /**
     * @return Collection<string, array{key: string, render: Closure|null, schema: Closure|null, values: Closure|null, save: Closure|null, with: array<string, mixed>, location: string|list<string>|Closure, can: string|null, position: int, owner: string}>
     */
    public function list(): Collection
    {
        return collect($this->metaboxes)->sortBy('position');
    }

    /**
     * The metaboxes of any of the locations the current user may see, sorted by position.
     *
     * @param  string|list<string>  $locations
     * @return Collection<string, array{key: string, render: Closure|null, schema: Closure|null, values: Closure|null, save: Closure|null, with: array<string, mixed>, location: string|list<string>|Closure, can: string|null, position: int, owner: string}>
     */
    public function for(string|array $locations): Collection
    {
        return $this->list()->filter(fn (array $metabox): bool => array_intersect((array) value($metabox['location']), (array) $locations)
            && (is_null($metabox['can']) || Gate::allows($metabox['can'])));
    }

    /**
     * The visible components of the metabox, which plugins may change through the `metaboxes.{key}.schema` filter.
     *
     * @return list<Component>
     */
    public function schema(string $key): array
    {
        if (! $this->metaboxes[$key]['schema']) {
            return [];
        }

        $schema = Filter::apply("metaboxes.$key.schema", ($this->metaboxes[$key]['schema'])(), $key);

        return array_values(array_filter($schema, fn (Component $component): bool => $component->isVisible()));
    }

    /**
     * The schema of the metabox grouped under its key.
     */
    public function form(string $key): Group
    {
        return Group::make($key)->schema($this->schema($key));
    }

    /**
     * The values of the metabox stored as meta of the model, or read by its `values` callback, keyed by field name,
     * falling back to each field's default.
     *
     * @param  (Model&HasMeta)|null  $model  Null when the model is being added.
     * @return array<string, mixed>
     */
    public function values(string $key, ?Model $model): array
    {
        $values = $this->metaboxes[$key]['values']
            ? ($this->metaboxes[$key]['values'])($model)
            : collect($this->form($key)->getChildFields())->mapWithKeys(fn (Field $field): array => [$field->name => $model?->getMeta($field->name)])->all();

        return $this->form($key)->cast($values);
    }

    /**
     * Store the given values of the metabox as meta of the model, forgetting empty ones, or pass them to its `save` callback,
     * ignoring any value the metabox has no field for.
     *
     * @param  Model&HasMeta  $model
     * @param  array<string, mixed>  $values
     */
    public function save(string $key, Model $model, array $values): void
    {
        $values = $this->form($key)->cast($values);

        if ($this->metaboxes[$key]['save']) {
            ($this->metaboxes[$key]['save'])($model, $values);

            return;
        }

        foreach ($values as $name => $value) {
            $model->setMeta($name, $value, $this->metaboxes[$key]['owner']);
        }
    }
}
