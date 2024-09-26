<?php

namespace Diana\Rendering\Drivers;

use Diana\Rendering\Contracts\Engine;
use Diana\Rendering\Contracts\Renderer;
use Diana\Rendering\View;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\LoaderInterface;

class TwigRenderer implements Renderer {

    public function __construct(private LoaderInterface $loader, private Environment $environment) {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string $path, array $data = []): string
    {
        return $this->environment->render($path, $data);
    }
}