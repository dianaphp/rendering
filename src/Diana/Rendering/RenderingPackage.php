<?php

namespace Diana\Rendering;

use Diana\Config\Config;
use Diana\Contracts\Core\Container;
use Diana\Contracts\Event\Dispatcher;
use Diana\Contracts\RendererContract;
use Diana\Events\ShutdownEvent;
use Diana\Framework\Core\Application;
use Diana\Framework\Core\Environment;
use Diana\Rendering\Components\Component;
use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\Drivers\TwigRenderer;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Rendering\Engines\FileEngine;
use Diana\Rendering\Engines\PhpEngine;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class RenderingPackage
{
    public function __construct(
        protected Container $container,
        protected Application $app,
        #[Config('rendering')] protected Config $config,
        protected Dispatcher $dispatcher,
        protected Environment $environment
    ) {
        $config->addDefault($this->getDefaultConfig());

        if (!$container->has(RendererContract::class)) {
            $container->singleton(RendererContract::class, BladeRenderer::class);
        }

        $container->singleton(BladeRenderer::class, fn(): BladeRenderer => $this->bladeRenderer());
        $container->singleton(TwigRenderer::class, fn(): TwigRenderer => $this->twigRenderer());
    }

    public function bladeRenderer(): BladeRenderer
    {
        Component::setCompilationPath($this->app->path($this->config->get('renderCompilationPath')));

        $compiler = new Compiler($this->app->path($this->config->get('renderCachePath')), false, viewBasePath: $this->app->path(''));
        $bladeEngine = new CompilerEngine($compiler);

        $this->dispatcher->addEventListener(ShutdownEvent::class, function () use ($bladeEngine) {
            Component::flushCache();
            $bladeEngine->forgetCompiledOrNotExpired();
        });

        $renderer = new BladeRenderer($compiler, $this->container, $this->environment);

        $renderer->registerEngine('blade.php', $bladeEngine);
        $renderer->registerEngine('php', PhpEngine::class);
        $renderer->registerEngine(['html', 'css'], FileEngine::class);

        return $renderer;
    }

    public function twigRenderer(): TwigRenderer
    {
        $loader = new FilesystemLoader($this->app->path('res'));
        $environment = new TwigEnvironment($loader, [
            'cache' => $this->app->path($this->config->get('renderCachePath')),
            'debug' => $this->environment->is(Environment::DEV)
        ]);

        return new TwigRenderer($loader, $environment);
    }

    public function getDefaultConfig(): array
    {
        return [
            'renderer' => BladeRenderer::class,
            'renderCachePath' => 'tmp/rendering/cached',
            'renderCompilationPath' => 'tmp/rendering/compiled',
        ];
    }
}
