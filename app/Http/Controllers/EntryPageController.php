<?php

namespace App\Http\Controllers;

use App\Facades\EntryType;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the dashboard pages of an entry type with the Livewire components its type is registered with,
 * like the media library for attachments.
 */
class EntryPageController extends Controller
{
    /**
     * The page listing the entries of the type.
     */
    public function listing(string $type): Response
    {
        return $this->render($type, 'listing');
    }

    /**
     * The page adding or editing an entry of the type.
     */
    public function editor(string $type): Response
    {
        return $this->render($type, 'editor');
    }

    /**
     * Render the given page component of the entry type as a full page, as `Route::livewire()` does.
     */
    protected function render(string $type, string $page): Response
    {
        $component = EntryType::get($type)[$page] ?? abort(404);

        return Livewire::new($component)();
    }
}
