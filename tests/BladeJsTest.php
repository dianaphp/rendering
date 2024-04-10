<?php

namespace Diana\Tests;

class BladeJsTest extends AbstractBladeTestCase
{
    public function testStatementIsCompiledWithoutAnyOptions()
    {
        $string = '<div x-data="@js($data)"></div>';
        $expected = '<div x-data="<?php echo \Diana\Rendering\Js::from($data); ?>"></div>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testJsonFlagsCanBeSet()
    {
        $string = '<div x-data="@js($data, JSON_FORCE_OBJECT)"></div>';
        $expected = '<div x-data="<?php echo \Diana\Rendering\Js::from($data, JSON_FORCE_OBJECT); ?>"></div>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testEncodingDepthCanBeSet()
    {
        $string = '<div x-data="@js($data, JSON_FORCE_OBJECT, 256)"></div>';
        $expected = '<div x-data="<?php echo \Diana\Rendering\Js::from($data, JSON_FORCE_OBJECT, 256); ?>"></div>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
