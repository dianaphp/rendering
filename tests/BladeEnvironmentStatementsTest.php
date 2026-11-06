<?php

namespace Diana\Tests;

class BladeEnvironmentStatementsTest extends AbstractBladeTestCase
{
        public function testEnvStatementsAreCompiled()
        {
                $string = "@env('staging')
breeze
@else
boom
@endenv";
                $expected = "<?php if(\$__env->environment('staging')): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
                $this->assertEquals($expected, $this->compiler->compileString($string));
        }

        public function testEnvStatementsWithMultipleStringParamsAreCompiled()
        {
                $string = "@env('staging', 'production')
breeze
@else
boom
@endenv";
                $expected = "<?php if(\$__env->environment('staging', 'production')): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
                $this->assertEquals($expected, $this->compiler->compileString($string));
        }

        public function testEnvStatementsWithArrayParamAreCompiled()
        {
                $string = "@env(['staging', 'production'])
breeze
@else
boom
@endenv";
                $expected = "<?php if(\$__env->environment(['staging', 'production'])): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
                $this->assertEquals($expected, $this->compiler->compileString($string));
        }

        public function testProductionStatementsAreCompiled()
        {
                $string = '@production
breeze
@else
boom
@endproduction';
                $expected = "<?php if(\$__env->environment('prod')): ?>
breeze
<?php else: ?>
boom
<?php endif; ?>";
                $this->assertEquals($expected, $this->compiler->compileString($string));
        }
}
