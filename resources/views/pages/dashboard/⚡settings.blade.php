<?php

use App\Facades\Settings;
use App\Fields\Field;
use Illuminate\Contracts\View\View;
use Livewire\Component;

new class extends Component
{
    public string $page;

    /**
     * @var array<string, mixed>
     */
    public array $values = [];

    public function mount(string $page): void
    {
        abort_unless(Settings::has($page), 404);
        abort_unless(Settings::allows($page), 403);

        $this->page = $page;
        $this->values = Settings::values($page);
    }

    /**
     * Validate and store the values of the settings page.
     */
    public function save(): void
    {
        abort_unless(Settings::allows($this->page), 403);

        $fields = Settings::fields($this->page);

        $validated = $this->validate(
            collect($fields)->flatMap(fn (Field $field): array => $field->getRules("values.$field->name"))->all(),
            [],
            collect($fields)->flatMap(fn (Field $field): array => $field->getValidationAttributes("values.$field->name"))->all(),
        );

        Settings::save($this->page, $validated['values'] ?? []);

        $this->values = Settings::values($this->page);

        $this->dispatch('toast', type: 'success', title: __('Settings saved.'));
    }

    public function render(): View
    {
        return $this->view(['fields' => Settings::fields($this->page)])->title(__(Settings::get($this->page)['title']));
    }
};
?>

<form wire:submit="save" class="flex w-full max-w-lg flex-col gap-6">
    <x-ui.field-group>
        @foreach ($fields as $field)
            <x-dynamic-component
                :component="$field->component"
                :field="$field"
                :path="'values.'.$field->name"
                :value="$values[$field->name]"
            />
        @endforeach
    </x-ui.field-group>
    <div>
        <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
    </div>
</form>
