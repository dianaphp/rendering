<?php

require_once __DIR__ . '/vendor/autoload.php';

use Diana\Rendering\Drivers\BladeRenderer;
use Diana\Rendering\Compiler;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Rendering\Components\Component;

Component::setCompilationPath(__DIR__ . '/tmp/compiled');

$compiler = new Compiler(__DIR__ . '/tmp/cached', false);

$renderer = new BladeRenderer($compiler);

$renderer->registerEngine('blade.php', new CompilerEngine($compiler));

echo $renderer->make(__DIR__ . '/view.blade.php');