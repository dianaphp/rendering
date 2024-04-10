<?php

namespace Diana\Tests;

class BladeOverwriteSectionTest extends AbstractBladeTestCase
{
    public function testOverwriteSectionsAreCompiled()
    {
        $this->assertSame('<?php $__env->stopSection(true); ?>', $this->compiler->compileString('@overwrite'));
    }
}
