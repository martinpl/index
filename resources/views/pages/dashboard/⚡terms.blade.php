<?php

use App\Facades\TermType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $type;

    public function mount(string $type): void
    {
        abort_unless(TermType::has($type), 404);

        $this->type = $type;
    }

    public function render(): View
    {
        return $this->view()->title(__(Str::plural(TermType::get($this->type)['name'])));
    }
};
?>

<div></div>
