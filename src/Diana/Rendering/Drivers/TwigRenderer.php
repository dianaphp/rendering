<?php

namespace Diana\Rendering\Drivers;

use Diana\Contracts\RendererContract;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\LoaderInterface;

class TwigRenderer implements RendererContract {

    public function __construct(private LoaderInterface $loader, private Environment $environment) {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string $input, array $data = []): string
    {
        return $this->environment->render($input, $data);
    }
}