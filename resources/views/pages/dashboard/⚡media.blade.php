<?php

use App\Actions\StoreMedia;
use App\EntryTypes\Media;
use App\Facades\EntryType;
use App\Fields\Field;
use App\Fields\Text;
use App\Fields\WithFields;
use App\Listing\Action;
use App\Listing\Column;
use App\Listing\Listing;
use App\Listing\Tab;
use App\Listing\WithListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFields;
    use WithFileUploads;
    use WithListing;

    /**
     * The files being added to the media library.
     *
     * @var list<TemporaryUploadedFile>
     */
    public array $uploads = [];

    /**
     * The ID of the media file being edited, or null when editing none.
     */
    public ?int $editing = null;

    /**
     * Whether the dropzone starts shown, as when the media library is opened at its create URL.
     */
    #[Locked]
    public bool $adding = false;

    /**
     * Mount the media library, showing the dropzone at its create URL, or editing the given media file at its edit URL.
     */
    public function mount(?string $entry = null): void
    {
        $this->adding = request()->routeIs('dashboard.entries.create');

        if ($entry) {
            $this->fillForm(Media::findOrFail($entry));
        }
    }

    public function booted(): void
    {
        abort_unless(Gate::allows(EntryType::get(Media::$type)['can']), 403);
    }

    /**
     * Add the uploaded files to the media library as soon as they are uploaded.
     */
    public function updatedUploads(StoreMedia $storeMedia): void
    {
        $this->validate(['uploads.*' => Media::uploadRules()], [], ['uploads.*' => __('file')]);

        foreach ($this->uploads as $file) {
            $storeMedia($file, ['user_id' => Auth::id()]);
        }

        $this->dispatch('toast', type: 'success', title: trans_choice(':count file uploaded.|:count files uploaded.', count($this->uploads)));

        $this->uploads = [];
    }

    /**
     * Validate and store the details of the media file being edited.
     */
    public function save(): void
    {
        $media = $this->editedMedia ?? abort(404);

        $values = $this->validateValues();

        $media->update(['name' => $values['name']]);

        if ($media->isImage()) {
            $media->setMeta('alt', $values['alt'], 'core');
        }

        $this->dispatch('toast', type: 'success', title: __('Media updated.'));

        $this->resetForm();
    }

    /**
     * Stop editing the media file.
     */
    public function resetForm(): void
    {
        $this->fillForm();
        $this->resetValidation();
    }

    /**
     * Fill the form with the details of the given media file, or empty it when editing none.
     */
    protected function fillForm(?Media $media = null): void
    {
        $this->editing = $media?->getKey();
        unset($this->editedMedia);
        $this->fillValues($media ? ['name' => $media->name, 'alt' => $media->alt()] : []);
    }

    #[Computed]
    public function editedMedia(): ?Media
    {
        return $this->editing ? Media::with(['meta', 'user'])->findOrFail($this->editing) : null;
    }

    /**
     * @return list<Field>
     */
    protected function fields(): array
    {
        return [
            Text::make('name')
                ->label(__('Name'))
                ->required()
                ->rules('string', 'max:255'),
            Text::make('alt')
                ->label(__('Alternative text'))
                ->description(__('Describes the image for people who cannot see it.'))
                ->rules('string', 'max:255')
                ->visible(fn (): bool => (bool) $this->editedMedia?->isImage()),
        ];
    }

    /**
     * @return Builder<Media>
     */
    protected function records(): Builder
    {
        return Media::with(['meta', 'user']);
    }

    /**
     * @return list<Tab>
     */
    protected function tabs(): array
    {
        $labels = ['image' => __('Images'), 'video' => __('Videos'), 'audio' => __('Audio'), 'document' => __('Documents')];

        return [
            Tab::make('all')->label(__('All')),
            ...array_map(fn (string $type): Tab => Tab::make($type)
                ->label($labels[$type])
                ->query(fn (Builder $query) => $query->ofType($type)), Media::TYPES),
        ];
    }

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            Column::make('name')
                ->label(__('File'))
                ->sortable()
                ->searchable()
                ->hideable(false)
                ->render(fn (Media $media): HtmlString => new HtmlString(
                    '<div class="flex items-center gap-3">'
                    .view('components.media.thumbnail', ['media' => $media, 'class' => 'size-12'])->render()
                    .'<div class="flex min-w-0 flex-col"><span class="truncate">'.e($media->name).'</span>'
                    .'<span class="text-muted-foreground truncate text-xs font-normal">'.e($media->fileName()).'</span></div></div>'
                )),
            Column::make('mime_type')->label(__('Type'))->render(fn (Media $media): string => $media->mimeType()),
            Column::make('user')->label(__('Author'))->render(fn (Media $media): ?string => $media->user?->name),
            Column::make('size')->label(__('Size'))->render(fn (Media $media): ?string => $media->fileSize() === null ? null : Number::fileSize($media->fileSize(), 1)),
            Column::make('date')->label(__('Uploaded'))->sortable()->date(),
        ];
    }

    /**
     * @return list<Action>
     */
    protected function actions(): array
    {
        return [
            Action::make('edit')
                ->label(__('Edit'))
                ->icon('lucide-pencil')
                ->handle(fn (Collection $mediaItems) => $this->fillForm($mediaItems->first())),
            Action::make('view')
                ->label(__('View'))
                ->icon('lucide-external-link')
                ->url(fn (Media $media): string => $media->url()),
            Action::make('delete')
                ->label(__('Delete permanently'))
                ->icon('lucide-trash-2')
                ->destructive()
                ->bulk()
                ->confirm(__('Are you sure you want to permanently delete the selected files?'))
                ->handle(function (Collection $mediaItems): void {
                    $mediaItems->each->delete();

                    if ($this->editing && $mediaItems->has($this->editing)) {
                        $this->resetForm();
                    }
                })
                ->successMessage(fn (Collection $mediaItems): string => trans_choice(':count file deleted.|:count files deleted.', $mediaItems->count())),
        ];
    }

    protected function configure(Listing $listing): void
    {
        $listing->defaultSort('date', 'desc');
    }

    public function render(): View
    {
        return $this->view(['media' => $this->editedMedia])->title($this->editing ? __('Edit Media') : __('Media Library'));
    }
};
?>

<x-slot:actions>
    <x-ui.button
        type="button"
        size="sm"
        x-data="{ open: {{ Js::from($adding) }} }"
        x-on:toggle-media-dropzone.window="open = ! open"
        x-on:click="$dispatch('toggle-media-dropzone')"
        x-bind:aria-expanded="open"
        aria-controls="media-dropzone"
    >
        <x-lucide-plus />
        {{ __('Add :type', ['type' => __(EntryType::get(Media::$type)['name'])]) }}
    </x-ui.button>
</x-slot:actions>

<div class="flex flex-col gap-8">
    <x-media.dropzone
        model="uploads"
        id="media-dropzone"
        x-data="{ open: {{ Js::from($adding) }} }"
        x-on:toggle-media-dropzone.window="open = ! open"
        x-show="open"
        :x-cloak="! $adding"
    />
    <div @class(['grid items-start gap-8', 'lg:grid-cols-[minmax(0,2fr)_minmax(0,4fr)]' => $media])>
        @if ($media)
            <form wire:submit="save" wire:key="media-form-{{ $media->id }}" class="flex flex-col gap-6">
                <x-media.thumbnail :$media class="aspect-video h-auto w-full" />
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 text-sm">
                    <dt class="text-muted-foreground">{{ __('File name') }}</dt>
                    <dd class="truncate">{{ $media->fileName() }}</dd>
                    <dt class="text-muted-foreground">{{ __('Type') }}</dt>
                    <dd>{{ $media->mimeType() }}</dd>
                    @if ($media->fileSize() !== null)
                        <dt class="text-muted-foreground">{{ __('Size') }}</dt>
                        <dd>{{ Number::fileSize($media->fileSize(), 1) }}</dd>
                    @endif
                    @if ($media->width() && $media->height())
                        <dt class="text-muted-foreground">{{ __('Dimensions') }}</dt>
                        <dd>{{ $media->width() }} × {{ $media->height() }}</dd>
                    @endif
                    <dt class="text-muted-foreground">{{ __('Uploaded') }}</dt>
                    <dd>
                        {{ $media->date->toDateFormat() }}
                        @if ($media->user)
                            {{ __('by :name', ['name' => $media->user->name]) }}
                        @endif
                    </dd>
                </dl>
                <x-ui.field>
                    <x-ui.field-label for="media-url">{{ __('File URL') }}</x-ui.field-label>
                    <div class="flex gap-2" x-data="{ copied: false }">
                        <x-ui.input
                            id="media-url"
                            :value="$media->url()"
                            readonly
                            class="flex-1"
                            x-on:focus="$el.select()"
                        />
                        <x-ui.button
                            type="button"
                            variant="outline"
                            size="icon"
                            :aria-label="__('Copy URL')"
                            x-on:click="navigator.clipboard.writeText({{ Js::from($media->url()) }}).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        >
                            <x-lucide-check x-show="copied" x-cloak />
                            <x-lucide-copy x-show="! copied" />
                        </x-ui.button>
                    </div>
                </x-ui.field>
                <x-fields :$fields :$values />
                <div class="flex gap-2">
                    <x-ui.button type="submit">{{ __('Update') }}</x-ui.button>
                    <x-ui.button type="button" variant="outline" wire:click="resetForm">{{ __('Cancel') }}</x-ui.button>
                </div>
            </form>
        @endif
        <x-listing :$listing />
    </div>
</div>
