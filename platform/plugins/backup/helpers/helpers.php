<?php

use Illuminate\Support\Facades\File;

if (! function_exists('get_backup_size')) {
    function get_backup_size(string $key): int
    {
        $path = storage_path('app/backup/' . $key);

        if (! File::isDirectory($path)) {
            return 0;
        }

        $size = 0;

        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
