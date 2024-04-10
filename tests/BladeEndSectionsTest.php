<?php

namespace Diana\Tests;


class BladeEndSectionsTest extends AbstractBladeTestCase
{
    public function testEndSectionsAreCompiled()
    {
        $this->assertSame('<?php $__env->stopSection(); ?>', $this->compiler->compileString('@endsection'));
    }
}
