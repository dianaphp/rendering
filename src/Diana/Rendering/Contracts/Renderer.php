<?php

namespace Diana\Rendering\Contracts;

use Diana\Rendering\Contracts\Engine;
use Diana\Rendering\View;

interface Renderer
{
    public function render(string $path, array $data = []): string;
}