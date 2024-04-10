<?php

namespace Diana\Rendering\Engines;

use Diana\Rendering\Contracts\Engine;
use Diana\Support\Helpers\Filesystem;

class FileEngine implements Engine
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function run(string $path, array $data = []): string
    {
        return Filesystem::get($path);
    }
}
