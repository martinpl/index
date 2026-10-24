<?php

namespace App\Listing;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

class Action
{
    use Conditionable, Macroable;

    protected ?string $label = null;

    protected ?string $icon = null;

    protected bool $destructive = false;

    protected bool $bulk = false;

    protected ?string $confirmation = null;

    /**
     * @var (Closure(mixed): bool)|null
     */
    protected ?Closure $visible = null;

    /**
     * @var (Closure(mixed): string)|null
     */
    protected ?Closure $url = null;

    /**
     * @var (Closure(Collection<array-key, mixed>): mixed)|null
     */
    protected ?Closure $handle = null;

    /**
     * @var string|(Closure(Collection<array-key, mixed>): string)|null
     */
    protected string|Closure|null $successMessage = null;

    final public function __construct(public string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->name);
    }

    /**
     * @param  string  $icon  A Blade component name, e.g. `lucide-trash`.
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function destructive(bool $destructive = true): static
    {
        $this->destructive = $destructive;

        return $this;
    }

    public function isDestructive(): bool
    {
        return $this->destructive;
    }

    /**
     * Allow running the action on all selected records at once.
     */
    public function bulk(bool $bulk = true): static
    {
        $this->bulk = $bulk;

        return $this;
    }

    public function isBulk(): bool
    {
        return $this->bulk;
    }

    /**
     * Ask the user to confirm before running the action.
     */
    public function confirm(string $confirmation): static
    {
        $this->confirmation = $confirmation;

        return $this;
    }

    public function getConfirmation(): ?string
    {
        return $this->confirmation;
    }

    /**
     * @param  Closure(mixed): bool  $visible  Whether the action is offered for, and may run on, the record.
     */
    public function visible(Closure $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isVisibleFor(mixed $record): bool
    {
        return is_null($this->visible) || ($this->visible)($record);
    }

    /**
     * Link the action to a URL instead of handling it.
     *
     * @param  Closure(mixed): string  $url
     */
    public function url(Closure $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(mixed $record): ?string
    {
        return $this->url ? ($this->url)($record) : null;
    }

    /**
     * @param  Closure(Collection<array-key, mixed>): mixed  $handle
     */
    public function handle(Closure $handle): static
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * @param  string|Closure(Collection<array-key, mixed>): string  $message
     */
    public function successMessage(string|Closure $message): static
    {
        $this->successMessage = $message;

        return $this;
    }

    /**
     * Run the action on the given records it is visible for, returning the success message.
     *
     * @param  Collection<array-key, mixed>  $records
     */
    public function run(Collection $records): ?string
    {
        $records = $records->filter(fn (mixed $record): bool => $this->isVisibleFor($record));

        if ($records->isEmpty() || is_null($this->handle)) {
            return null;
        }

        ($this->handle)($records);

        return value($this->successMessage, $records);
    }
}
