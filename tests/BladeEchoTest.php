<?php

namespace Diana\Tests;

class BladeEchoTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertSame('<?php echo $name; ?>', $this->compiler->compileString('{!!$name!!}'));
        $this->assertSame('<?php echo $name; ?>', $this->compiler->compileString('{!! $name !!}'));
        $this->assertSame('<?php echo $name; ?>', $this->compiler->compileString('{!!
            $name
        !!}'));

        $this->assertSame('<?php echo \Diana\Support\Helpers\Emit::e($name); ?>', $this->compiler->compileString('{{{$name}}}'));
        $this->assertSame('<?php echo \Diana\Support\Helpers\Emit::e($name); ?>', $this->compiler->compileString('{{$name}}'));
        $this->assertSame('<?php echo \Diana\Support\Helpers\Emit::e($name); ?>', $this->compiler->compileString('{{ $name }}'));
        $this->assertSame('<?php echo \Diana\Support\Helpers\Emit::e($name); ?>', $this->compiler->compileString('{{
            $name
        }}'));
        $this->assertSame("<?php echo \Diana\Support\Helpers\Emit::e(\$name); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertSame("<?php echo \Diana\Support\Helpers\Emit::e(\$name); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));
        $this->assertSame("<?php echo \Diana\Support\Helpers\Emit::e(\$name); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertSame("<?php echo \Diana\Support\Helpers\Emit::e(\$name); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));

        $this->assertSame(
            '<?php echo \Diana\Support\Helpers\Emit::e("Hello world or foo"); ?>',
            $this->compiler->compileString('{{ "Hello world or foo" }}')
        );
        $this->assertSame(
            '<?php echo \Diana\Support\Helpers\Emit::e("Hello world or foo"); ?>',
            $this->compiler->compileString('{{"Hello world or foo"}}')
        );
        $this->assertSame('<?php echo \Diana\Support\Helpers\Emit::e($foo + $or + $baz); ?>', $this->compiler->compileString('{{$foo + $or + $baz}}'));
        $this->assertSame('<?php echo \Diana\Support\Helpers\Emit::e("Hello world or foo"); ?>', $this->compiler->compileString('{{
            "Hello world or foo"
        }}'));

        $this->assertSame(
            '<?php echo \Diana\Support\Helpers\Emit::e(\'Hello world or foo\'); ?>',
            $this->compiler->compileString('{{ \'Hello world or foo\' }}')
        );
        $this->assertSame(
            '<?php echo \Diana\Support\Helpers\Emit::e(\'Hello world or foo\'); ?>',
            $this->compiler->compileString('{{\'Hello world or foo\'}}')
        );
        $this->assertSame('<?php echo \Diana\Support\Helpers\Emit::e(\'Hello world or foo\'); ?>', $this->compiler->compileString('{{
            \'Hello world or foo\'
        }}'));

        $this->assertSame(
            '<?php echo \Diana\Support\Helpers\Emit::e(myfunc(\'foo or bar\')); ?>',
            $this->compiler->compileString('{{ myfunc(\'foo or bar\') }}')
        );
        $this->assertSame(
            '<?php echo \Diana\Support\Helpers\Emit::e(myfunc("foo or bar")); ?>',
            $this->compiler->compileString('{{ myfunc("foo or bar") }}')
        );
        $this->assertSame(
            '<?php echo \Diana\Support\Helpers\Emit::e(myfunc("$name or \'foo\'")); ?>',
            $this->compiler->compileString('{{ myfunc("$name or \'foo\'") }}')
        );
    }

    public function testEscapedWithAtEchosAreCompiled()
    {
        $this->assertSame('{{$name}}', $this->compiler->compileString('@{{$name}}'));
        $this->assertSame('{{ $name }}', $this->compiler->compileString('@{{ $name }}'));
        $this->assertSame('{{
            $name
        }}',
            $this->compiler->compileString('@{{
            $name
        }}')
        );
        $this->assertSame('{{ $name }}
            ',
            $this->compiler->compileString('@{{ $name }}
            ')
        );
    }
}
