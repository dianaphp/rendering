<?php

namespace Diana\Rendering\Components;

use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\View;

class AnonymousComponent extends Component
{
    public function __construct(public BladeRenderer $renderer, public string $view, public array $data = [])
    {
    }

    public function render(): View
    {
        return $this->renderer->make($this->view);
    }

    /**
     * Get the data that should be supplied to the view.
     *
     * @return array
     */
    public function data(): array
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        return array_merge(
            ($this->data['attributes'] ?? null)?->getAttributes() ?: [],
            $this->attributes->getAttributes(),
            $this->data,
            ['attributes' => $this->attributes]
        );
    }
}
