<?php

namespace App\Console\Commands;

use App\Services\ProductPhotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Las carpetas de color se llaman igual que el color, con espacios ("ORO ROSA").
 * Renombra las antiguas con guion ("ORO-ROSA") a ese nombre.
 */
class RenameColorFolders extends Command
{
    protected $signature = 'siigo:rename-color-folders {--dry-run : Solo muestra qué carpetas se renombrarían}';

    protected $description = 'Renombra las carpetas de color con guion (ORO-ROSA) a su nombre con espacios (ORO ROSA)';

    public function handle(ProductPhotoService $photos): int
    {
        $disk = Storage::disk(ProductPhotoService::DISK);
        $dry = $this->option('dry-run');
        $renamed = 0;
        $skipped = 0;

        foreach ($disk->directories(ProductPhotoService::BASE_PATH) as $referenceDir) {
            foreach ($disk->directories($referenceDir) as $colorDir) {
                $name = basename($colorDir);

                if (!str_contains($name, '-')) {
                    continue;
                }

                $color = $photos->colorPorCarpeta($name);

                if ($color === null || $photos->carpetaDeColor($color) === $name) {
                    continue; // no es un color del catálogo
                }

                $target = $referenceDir . '/' . $photos->carpetaDeColor($color);

                if ($disk->exists($target)) {
                    $this->warn("Omitida (ya existe {$target}): {$colorDir}");
                    $skipped++;
                    continue;
                }

                $this->line(($dry ? '[simulación] ' : '') . "{$colorDir} -> {$target}");

                if (!$dry) {
                    $disk->move($colorDir, $target);
                }

                $renamed++;
            }
        }

        $this->info(($dry ? 'Se renombrarían ' : 'Renombradas ') . "{$renamed} carpeta(s); omitidas: {$skipped}.");

        return self::SUCCESS;
    }
}
