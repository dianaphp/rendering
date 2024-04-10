<?php

namespace Diana\Tests;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Stringable;

class StringableClass implements Stringable
{
    public function __toString(): string
    {
        return 'StringableClass';
    }
}

class BladeEchoHandlerTest extends AbstractBladeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler->stringable(function (StringableClass $object) {
            return 'Hello World';
        });
    }

    public function testBladeHandlerCanInterceptRegularEchos()
    {
        $this->assertSame(
            "<?php echo \Diana\Support\Helpers\Emit::e(\$__compiler->applyEchoHandler(\$exampleObject)); ?>",
            $this->compiler->compileString('{{$exampleObject}}')
        );
    }

    public function testBladeHandlerCanInterceptRawEchos()
    {
        $this->assertSame(
            "<?php echo \$__compiler->applyEchoHandler(\$exampleObject); ?>",
            $this->compiler->compileString('{!!$exampleObject!!}')
        );
    }

    public function testBladeHandlerCanInterceptEscapedEchos()
    {
        $this->assertSame(
            "<?php echo \Diana\Support\Helpers\Emit::e(\$__compiler->applyEchoHandler(\$exampleObject)); ?>",
            $this->compiler->compileString('{{{$exampleObject}}}')
        );
    }

    public function testWhitespaceIsPreservedCorrectly()
    {
        $this->assertSame(
            "<?php echo \Diana\Support\Helpers\Emit::e(\$__compiler->applyEchoHandler(\$exampleObject)); ?>\n\n",
            $this->compiler->compileString("{{\$exampleObject}}\n")
        );
    }

    #[DataProvider('handlerLogicDataProvider')]
    public function testHandlerLogicWorksCorrectly($blade)
    {
        $__compiler = $this->compiler;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The stringable object has been successfully handled!');

        $this->compiler->stringable(StringableClass::class, function ($object) {
            throw new Exception('The stringable object has been successfully handled!');
        });

        $exampleObject = new StringableClass;

        eval (str_replace(['<?php', '?>'], '', $this->compiler->compileString($blade)));
    }

    public static function handlerLogicDataProvider()
    {
        return [
            ['{{$exampleObject}}'],
            ['{{$exampleObject;}}'],
            ['{{{$exampleObject;}}}'],
            ['{!!$exampleObject;!!}'],
        ];
    }

    #[DataProvider('handlerWorksWithIterableDataProvider')]
    public function testHandlerWorksWithIterables($blade, $closure, $expectedOutput)
    {
        $__compiler = $this->compiler;

        $this->compiler->stringable('iterable', $closure);

        ob_start();
        eval (str_replace(['<?php', '?>'], '', $this->compiler->compileString($blade)));
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame($expectedOutput, $output);
    }

    public static function handlerWorksWithIterableDataProvider()
    {
        return [
            [
                '{{[1,"two",3]}}',
                function (iterable $arr) {
                    return implode(', ', $arr);
                },
                '1, two, 3'
            ],
        ];
    }

    #[DataProvider('nonStringableDataProvider')]
    public function testHandlerWorksWithNonStringables($blade, $expectedOutput)
    {
        $__compiler = $this->compiler;

        ob_start();
        eval (str_replace(['<?php', '?>'], '', $this->compiler->compileString($blade)));
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertSame($expectedOutput, $output);
    }

    public static function nonStringableDataProvider()
    {
        return [
            ['{{"foo" . "bar"}}', 'foobar'],
            ['{{ 1 + 2 }}{{ "test"; }}', '3test'],
            ['@php($test = "hi"){{ $test }}', 'hi'],
            ['{!! "&nbsp;" !!}', '&nbsp;'],
        ];
    }
}
