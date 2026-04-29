<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;

class WindowsFilesystem extends Filesystem
{
    /**
     * On Windows, rename() can fail when the destination is in use (for example, PHP has
     * required the file). For Livewire compiled classes this breaks requests/tests.
     * Use an in-place write instead of temp+rename.
     */
    public function replace($path, $content, $mode = null)
    {
        $this->ensureDirectoryExists(dirname((string) $path), 0777, true);

        // LOCK_EX avoids partial writes across concurrent compiles.
        $this->put($path, $content, true);

        if (! is_null($mode)) {
            @chmod((string) $path, $mode);
        }

        return true;
    }
}
