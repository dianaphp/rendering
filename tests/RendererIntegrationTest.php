<?php

namespace Diana\Tests;

use Diana\Config\Config;
use Diana\Contracts\RendererContract;
use Diana\Rendering\Compiler;
use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\Drivers\TwigRenderer;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Runtime\IlluminateContainer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class RendererIntegrationTest extends TestCase
{
    public function testTwigRendersAndEscapesThroughTheCurrentContract(): void
    {
        $loader = new ArrayLoader(['hello' => 'Hello {{ name }}']);
        $renderer = new TwigRenderer($loader, new Environment($loader, ['autoescape' => 'html']));
        self::assertInstanceOf(RendererContract::class, $renderer);
        self::assertSame('Hello &lt;Diana&gt;', $renderer->render('hello', ['name' => '<Diana>']));
    }

    public function testBladeDirectivesUseTheProvidedContainerAndConfiguration(): void
    {
        $directory = sys_get_temp_dir() . '/diana-directives-' . bin2hex(random_bytes(6));
        mkdir($directory);
        $path = $directory . '/template.blade.php';
        file_put_contents($path, <<<'BLADE'
@env('staging', 'production')
yes
@else
no
@endenv
|
@env(['staging', 'production'])
yes
@else
no
@endenv
|
@production
yes
@else
no
@endproduction
|
@inject('service', 'test.service')
{{ $service->name }}
BLADE);
        $compiler = new Compiler($directory, false);
        $container = new IlluminateContainer();
        $container->instance('test.service', (object) ['name' => 'Diana']);
        $config = new Config();
        $renderer = new BladeRenderer($compiler, $container, $config);
        $renderer->registerEngine('blade.php', new CompilerEngine($compiler));
        try {
            $config->set('env', 'production');
            self::assertSame('yes|yes|yes|Diana', preg_replace('/\s+/', '', $renderer->render($path)));
            $config->set('env', 'dev');
            self::assertSame('no|no|no|Diana', preg_replace('/\s+/', '', $renderer->render($path)));
        } finally {
            foreach (glob($directory . '/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
