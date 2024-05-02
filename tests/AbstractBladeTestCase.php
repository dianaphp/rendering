<?php

namespace Diana\Tests;

use Diana\Rendering\Compiler;

use Diana\Rendering\Components\Component;

use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Rendering\Engines\FileEngine;
use Diana\Rendering\Engines\PhpEngine;
use Diana\Support\Helpers\Filesystem;
use PHPUnit\Framework\TestCase;

abstract class AbstractBladeTestCase extends TestCase
{
    /**
     * @var Compiler
     */
    protected $compiler;

    protected $compilerEngine;

    protected $renderer;

    protected function setUp(): void
    {
        Filesystem::setBasePath(__DIR__);

        $this->compiler = new Compiler(sys_get_temp_dir());

        $this->renderer = new BladeRenderer($this->compiler);
        $this->renderer->registerEngine('blade.php', function () {
            $this->compilerEngine = new CompilerEngine($this->compiler);
            return $this->compilerEngine;
        });
        $this->renderer->registerEngine('php', PhpEngine::class);
        $this->renderer->registerEngine(['html', 'css'], FileEngine::class);

        Component::setCompilationPath(sys_get_temp_dir());
    }

    protected function tearDown(): void
    {
        $this->compilerEngine->forgetCompiledOrNotExpired();

        Component::flushCache();
        Component::forgetComponentsResolver();

        parent::tearDown();
    }
}