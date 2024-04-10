<?php

namespace Diana\Rendering\Contracts;

interface Engine
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function run(string $path, array $data = []): string;
}