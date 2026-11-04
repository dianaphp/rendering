<?php

namespace Diana\Tests;

use Diana\Config\Config;
use Diana\Contracts\RendererContract;
use Diana\Framework\Core\Application;
use Diana\IO\Event\NullDispatcher;
use Diana\Rendering\Compiler;
use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\Drivers\TwigRenderer;
use Diana\Rendering\RenderingPackage;
use Diana\Runtime\IlluminateContainer;
use PHPUnit\Framework\TestCase;

class RenderingPackageTest extends TestCase
{
    public function testPackageProvidesRenderersWithoutProjectExtensionsOrAGlobalCompiler(): void
    {
        $container = new IlluminateContainer();
        $app = $this->createMock(Application::class);
        $app->method('path')->willReturnCallback(static fn(string $path) => $path === 'res' ? __DIR__ . '/res' : sys_get_temp_dir() . '/' . $path);
        $config = new Config('rendering');
        $package = new RenderingPackage($container, $app, $config, new NullDispatcher($container), new Config());
        // Config's in-memory implementation keeps defaults separate from values.
        foreach ($package->getDefaultConfig() as $key => $value) {
            $config->set($key, $value);
        }

        $blade = $container->get(BladeRenderer::class);
        self::assertSame($blade, $container->get(RendererContract::class));
        self::assertSame($blade, $container->get(BladeRenderer::class));
        self::assertSame($blade->getCompiler(), $blade->getEngineFromPath('test.blade.php')->getCompiler());
        self::assertFalse($container->has(Compiler::class));
        self::assertSame('@vite("main.jsx")', $blade->getCompiler()->compileString('@vite("main.jsx")'));
        self::assertSame([], $blade->getCompiler()->getClassComponentAliases());

        $twig = $container->get(TwigRenderer::class);
        self::assertSame($twig, $container->get(TwigRenderer::class));
    }
}
