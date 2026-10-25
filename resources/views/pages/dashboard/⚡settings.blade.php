<?php

use App\Facades\Settings;
use App\Fields\Field;
use App\Fields\WithFields;
use Illuminate\Contracts\View\View;
use Livewire\Component;

new class extends Component
{
    use WithFields;

    public string $page;

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

        Settings::save($this->page, $this->validateValues());

        $this->values = Settings::values($this->page);

        $this->dispatch('toast', type: 'success', title: __('Settings saved.'));
    }

    /**
     * @return list<Field>
     */
    protected function fields(): array
    {
        return Settings::fields($this->page);
    }

    public function render(): View
    {
        return $this->view()->title(__(Settings::get($this->page)['page_title']));
    }
};
?>

<form wire:submit="save" class="flex w-full max-w-lg flex-col gap-6">
    <x-fields :$fields :$values />
    <div>
        <x-ui.button type="submit">{{ __('Save') }}</x-ui.button>
    </div>
</form>
