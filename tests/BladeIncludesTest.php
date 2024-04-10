<?php

namespace Diana\Tests;

class BladeIncludesTest extends AbstractBladeTestCase
{
    public function testEachsAreCompiled()
    {
        $this->assertSame('<?php echo $__env->renderEach(\'foo\', \'bar\'); ?>', $this->compiler->compileString('@each(\'foo\', \'bar\')'));
        $this->assertSame('<?php echo $__env->renderEach(\'foo\', \'(bar))\'); ?>', $this->compiler->compileString('@each(\'foo\', \'(bar))\')'));
        $this->assertSame('<?php echo $__env->renderEach(name(foo)); ?>', $this->compiler->compileString('@each(name(foo))'));
    }

    public function testIncludesAreCompiled()
    {
        $this->assertSame('<?php echo $__env->make(\'foo\', \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@include(\'foo\')'));
        $this->assertSame('<?php echo $__env->make(\'foo\', [\'((\'], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@include(\'foo\', [\'((\'])'));
        $this->assertSame('<?php echo $__env->make(\'foo\', [\'((a)\' => \'((a)\'], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@include(\'foo\', [\'((a)\' => \'((a)\'])'));
        $this->assertSame('<?php echo $__env->make(name(foo), \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@include(name(foo))'));
    }

    public function testIncludeIfsAreCompiled()
    {
        $this->assertSame('<?php if ($__env->exists(\'foo\')) echo $__env->make(\'foo\', \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeIf(\'foo\')'));
        $this->assertSame('<?php if ($__env->exists(name(foo))) echo $__env->make(name(foo), \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeIf(name(foo))'));
    }

    public function testIncludeWhensAreCompiled()
    {
        $this->assertSame('<?php echo $__env->renderWhen(true, \'foo\', ["foo" => "bar"], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\', ["foo" => "bar"])'));
        $this->assertSame('<?php echo $__env->renderWhen(true, \'foo\', \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\')'));
    }

    public function testIncludeUnlessesAreCompiled()
    {
        $this->assertSame('<?php echo $__env->renderUnless(true, \'foo\', ["foo" => "bar"], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeUnless(true, \'foo\', ["foo" => "bar"])'));
        $this->assertSame('<?php echo $__env->renderUnless(true, \'foo\', ["foo" => "bar_))-))>"], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeUnless(true, \'foo\', ["foo" => "bar_))-))>"])'));
        $this->assertSame('<?php echo $__env->renderUnless($undefined ?? true, \'foo\', \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeUnless($undefined ?? true, \'foo\')'));
    }

    public function testIncludeFirstsAreCompiled()
    {
        $this->assertSame('<?php echo $__env->first(["one", "two"], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeFirst(["one", "two"])'));
        $this->assertSame('<?php echo $__env->first(["one", "two"], ["foo" => "bar"], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeFirst(["one", "two"], ["foo" => "bar"])'));
        $this->assertSame('<?php echo $__env->first(["issue", "#45424)"], ["foo()" => "bar)-))"], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeFirst(["issue", "#45424)"], ["foo()" => "bar)-))"])'));
        $this->assertSame('<?php echo $__env->first(["issue", "#45424)"], ["foo" => "bar(-(("], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeFirst(["issue", "#45424)"], ["foo" => "bar(-(("])'));
        $this->assertSame('<?php echo $__env->first(["issue", "#45424)"], [(string) "foo()" => "bar(-(("], \Diana\Support\Helpers\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeFirst(["issue", "#45424)"], [(string) "foo()" => "bar(-(("])'));
    }
}
