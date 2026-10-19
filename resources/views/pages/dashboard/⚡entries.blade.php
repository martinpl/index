<?php

use App\Facades\EntryType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $type;

    public function mount(string $type): void
    {
        abort_unless(EntryType::has($type), 404);

        $this->type = $type;
    }

    public function render(): View
    {
        return $this->view()->title(__(Str::plural(EntryType::get($this->type)['name'])));
    }
};
?>

<div></div>
