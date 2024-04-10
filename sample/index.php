<?php

require_once __DIR__ . '/vendor/autoload.php';

use Diana\Rendering\Driver;
use Diana\Rendering\Compiler;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Rendering\Components\Component;

Component::setCompilationPath(__DIR__ . '/cache/compiled');

$compiler = new Compiler(__DIR__ . '/cache/cached', false);

$renderer = new Driver($compiler);

$renderer->registerEngine('blade.php', new CompilerEngine($compiler));

echo $renderer->make(__DIR__ . '/view.blade.php');