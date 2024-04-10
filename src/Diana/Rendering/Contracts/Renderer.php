<?php

namespace Diana\Rendering\Contracts;

use Diana\Rendering\Contracts\Engine;
use Diana\Rendering\View;

interface Renderer
{
    public function incrementRenderCount(): void;
    public function decrementRenderCount(): void;

    public function getEngineFromPath(string $path): Engine;

    public function getShared(): array;

    public function flushState(): void;

    public function doneRendering(): bool;

    public function make(string $view, array $data = []): View;
}