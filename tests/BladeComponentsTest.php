<?php

namespace Diana\Tests;

use Diana\Rendering\Components\Component;
use Diana\Rendering\ComponentAttributeBag;
use Diana\Rendering\Contracts\Renderer;

class BladeComponentsTest extends AbstractBladeTestCase
{
    public function testComponentsAreCompiled()
    {
        $this->assertSame('<?php $__env->startComponent(\'foo\', ["foo" => "bar"]); ?>', $this->compiler->compileString('@component(\'foo\', ["foo" => "bar"])'));
        $this->assertSame('<?php $__env->startComponent(\'foo\'); ?>', $this->compiler->compileString('@component(\'foo\')'));
    }

    public function testClassComponentsAreCompiled()
    {
        $this->assertSame('<?php if (isset($component)) { $__componentOriginalfdb6796988e042087929984f8dbdc850 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfdb6796988e042087929984f8dbdc850 = $attributes; } ?>
<?php $component = Diana\Tests\ComponentStub::class::resolve(["foo" => "bar"] + (isset($attributes) && $attributes instanceof Diana\Rendering\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName(\'test\'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>', $this->compiler->compileString('@component(\'Diana\Tests\ComponentStub::class\', \'test\', ["foo" => "bar"])'));
    }

    public function testEndComponentsAreCompiled()
    {
        $this->compiler->newComponentHash('foo');

        $this->assertSame('<?php echo $__env->renderComponent(); ?>', $this->compiler->compileString('@endcomponent'));
    }

    public function testEndComponentClassesAreCompiled()
    {
        $this->compiler->newComponentHash('foo');

        $this->assertSame('<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal79aef92e83454121ab6e5f64077e7d8a)): ?>
<?php $attributes = $__attributesOriginal79aef92e83454121ab6e5f64077e7d8a; ?>
<?php unset($__attributesOriginal79aef92e83454121ab6e5f64077e7d8a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal79aef92e83454121ab6e5f64077e7d8a)): ?>
<?php $component = $__componentOriginal79aef92e83454121ab6e5f64077e7d8a; ?>
<?php unset($__componentOriginal79aef92e83454121ab6e5f64077e7d8a); ?>
<?php endif; ?>', $this->compiler->compileString('@endcomponentClass'));
    }

    public function testSlotsAreCompiled()
    {
        $this->assertSame('<?php $__env->slot(\'foo\', null, ["foo" => "bar"]); ?>', $this->compiler->compileString('@slot(\'foo\', null, ["foo" => "bar"])'));
        $this->assertSame('<?php $__env->slot(\'foo\'); ?>', $this->compiler->compileString('@slot(\'foo\')'));
    }

    public function testEndSlotsAreCompiled()
    {
        $this->assertSame('<?php $__env->endSlot(); ?>', $this->compiler->compileString('@endslot'));
    }
}

class ComponentStub extends Component
{
    public function render(): string
    {
        return '';
    }
}
