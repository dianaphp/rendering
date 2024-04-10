<?php

namespace Diana\Tests;

use Diana\Rendering\Compiler;

use Diana\Rendering\Components\Component;

use Diana\Rendering\Contracts\Renderer;
use Diana\Rendering\Driver;
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

    protected $driver;

    protected function setUp(): void
    {
        Filesystem::setBasePath(__DIR__);

        $this->compiler = new Compiler(sys_get_temp_dir());

        $this->driver = new Driver($this->compiler);
        $this->driver->registerEngine('blade.php', function () {
            $this->compilerEngine = new CompilerEngine($this->compiler);
            return $this->compilerEngine;
        });
        $this->driver->registerEngine('php', PhpEngine::class);
        $this->driver->registerEngine(['html', 'css'], FileEngine::class);

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