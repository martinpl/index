<?php

use App\Actions\StoreMedia;
use App\EntryTypes\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Chooses a media file from the media library, or uploads one there, binding its ID to the parent's `wire:model`.
 */
new class extends Component
{
    use WithFileUploads;

    /**
     * The ID of the chosen media file.
     */
    #[Modelable]
    public ?int $value = null;

    /**
     * Only offer media files of this type, one of `Media::TYPES`.
     */
    #[Locked]
    public ?string $type = null;

    public string $search = '';

    /**
     * The number of media files shown in the library, growing as more are loaded.
     */
    public int $limit = 24;

    public ?TemporaryUploadedFile $upload = null;

    #[Computed]
    public function selected(): ?Media
    {
        return $this->value ? $this->query()->find($this->value) : null;
    }

    /**
     * The most recent media files matching the search, with one more than shown to tell whether there are more.
     *
     * @return Collection<int, Media>
     */
    #[Computed]
    public function library(): Collection
    {
        return $this->query()
            ->when($this->search !== '', fn (Builder $query) => $query->whereLike('name', '%'.trim($this->search).'%'))
            ->latest('date')
            ->latest('id')
            ->limit($this->limit + 1)
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->limit = 24;
    }

    public function loadMore(): void
    {
        $this->limit += 24;
    }

    public function select(int $id): void
    {
        $this->value = $this->query()->findOrFail($id)->getKey();

        $this->dispatch("close-dialog-{$this->getId()}");
    }

    public function clear(): void
    {
        $this->value = null;
    }

    /**
     * Add the uploaded file to the media library as soon as it is uploaded, and choose it.
     */
    public function updatedUpload(StoreMedia $storeMedia): void
    {
        abort_unless(Gate::allows('entries.media.manage'), 403);

        $this->validate(['upload' => [...Media::uploadRules(), function (string $attribute, TemporaryUploadedFile $file, Closure $fail): void {
            if ($this->type && Media::typeOf($file->getMimeType()) !== $this->type) {
                $fail(__('The :attribute must be of the :type type.', ['type' => __($this->type)]));
            }
        }]], [], ['upload' => __('file')]);

        $this->select($storeMedia($this->upload, ['user_id' => Auth::id()])->getKey());

        $this->upload = null;
    }

    /**
     * @return Builder<Media>
     */
    protected function query(): Builder
    {
        return Media::with('meta')->when($this->type, fn (Builder $query, string $type) => $query->ofType($type));
    }

    public function render(): View
    {
        return $this->view();
    }
};
?>

<div class="flex flex-col gap-2">
    @if ($this->selected)
        <div class="flex items-center gap-3 rounded-md border p-2">
            <x-media.thumbnail :media="$this->selected" class="size-12" />
            <div class="flex min-w-0 flex-1 flex-col text-sm">
                <span class="truncate font-medium">{{ $this->selected->name }}</span>
                <span class="text-muted-foreground truncate text-xs">{{ $this->selected->fileName() }}</span>
            </div>
            <x-ui.button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                wire:click="clear"
                :aria-label="__('Remove')"
            >
                <x-lucide-x />
            </x-ui.button>
        </div>
    @endif

    <x-ui.dialog :id="$this->getId()">
        <x-ui.dialog-trigger>
            <x-ui.button type="button" variant="outline" size="sm" class="w-full">
                <x-lucide-images />
                {{ $this->selected ? __('Replace') : __('Choose from library') }}
            </x-ui.button>
        </x-ui.dialog-trigger>
        <x-ui.dialog-content class="max-h-[calc(100svh-4rem)] overflow-y-auto sm:max-w-3xl">
            <x-ui.dialog-header>
                <x-ui.dialog-title>
                    {{ $type === 'image' ? __('Choose an image') : __('Choose media') }}</x-ui.dialog-title>
            </x-ui.dialog-header>

            @can('entries.media.manage')
                <x-media.dropzone model="upload" :multiple="false" />
            @endcan

            <x-ui.input
                type="search"
                size="sm"
                :placeholder="__('Search')"
                :aria-label="__('Search')"
                wire:model.live.debounce.300ms="search"
            >
                <x-slot:leading>
                    <x-lucide-search class="size-4" />
                </x-slot:leading>
            </x-ui.input>

            @if ($this->library->isEmpty())
                <x-ui.empty>
                    <x-ui.empty-header>
                        <x-ui.empty-media variant="icon"><x-lucide-images /></x-ui.empty-media>
                        <x-ui.empty-title>{{ __('No media found') }}</x-ui.empty-title>
                    </x-ui.empty-header>
                </x-ui.empty>
            @else
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6" wire:loading.class="opacity-60">
                    @foreach ($this->library->take($limit) as $media)
                        <button
                            type="button"
                            wire:key="media-{{ $media->id }}"
                            wire:click="select({{ $media->id }})"
                            title="{{ $media->name }}"
                            aria-label="{{ $media->name }}"
                            aria-pressed="{{ $media->id === $value ? 'true' : 'false' }}"
                            @class([
                                'focus-visible:ring-ring/50 rounded-md outline-none focus-visible:ring-[3px]',
                                'ring-primary ring-2 ring-offset-2' => $media->id === $value,
                            ])
                        >
                            <x-media.thumbnail :$media class="aspect-square h-auto w-full" />
                        </button>
                    @endforeach
                </div>
                @if ($this->library->count() > $limit)
                    <x-ui.button type="button" variant="outline" size="sm" class="self-center" wire:click="loadMore">
                        {{ __('Load more') }}
                    </x-ui.button>
                @endif
            @endif
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
