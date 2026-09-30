<?php

namespace CarlJanzell\FilamentPageBuilder\Support;

use CarlJanzell\FilamentPageBuilder\BlockRegistry;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Reconciles Filament's in-form state with the shape stored in the database.
 *
 * FileUpload differs between editing and rest, and will crash a renderer that assumes
 * the stored shape: it always holds an array (Arr::wrap'd path, or uuid =>
 * TemporaryUploadedFile) while the stored column is a plain path string. Uploads can
 * sit inside repeaters, so the walk is recursive.
 *
 * Filament 3's RichEditor is Trix, which holds the same HTML string while editing that
 * it stores, so rich text needs no conversion here.
 *
 * Uploads are resolved by declared field name rather than by shape, because a map of
 * strings is indistinguishable from a repeater item.
 */
class BlockStateNormaliser
{
    public function __construct(protected BlockRegistry $registry) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalise(mixed $blocks): array
    {
        if (! is_array($blocks)) {
            return [];
        }

        return collect($blocks)
            ->values()
            ->filter(fn (mixed $block): bool => is_array($block) && isset($block['type']))
            ->map(fn (array $block): array => [
                ...$block,
                'id' => $block['id'] ?? null,
                'type' => $block['type'],
                'data' => $this->normaliseData(
                    $block['data'] ?? [],
                    $this->registry->fileFields($block['type']),
                ),
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $fileFields
     * @return array<string, mixed>
     */
    public function normaliseData(mixed $data, array $fileFields = []): array
    {
        if (! is_array($data)) {
            return [];
        }

        return collect($data)
            ->map(function (mixed $value, mixed $key) use ($fileFields): mixed {
                if (in_array($key, $fileFields, true)) {
                    return $this->resolveFileState($value);
                }

                return match (true) {
                    is_array($value) => $this->normaliseData($value, $fileFields),
                    default => $value,
                };
            })
            ->all();
    }

    /**
     * A file mid-upload has no stored path yet, so it is shown from Livewire's temporary URL.
     */
    public function resolveFileState(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        foreach (is_array($value) ? $value : [$value] as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                try {
                    return $file->temporaryUrl();
                } catch (Throwable) {
                    return null;
                }
            }

            if (is_string($file) && filled($file)) {
                return $file;
            }
        }

        return null;
    }
}
