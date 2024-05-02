<?php

namespace Diana\Tests;

use Diana\Rendering\Compiler;
use Diana\Rendering\ComponentTagCompiler;
use Diana\Rendering\Components\Component;
use Diana\Rendering\ComponentAttributeBag;
use InvalidArgumentException;
use Mockery as m;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    public function testSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo">
</x-slot>');

        $this->assertSame("@slot('foo', null, []) \n" . ' @endslot', trim($result));
    }

    public function testInlineSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot:foo>
</x-slot>');

        $this->assertSame("@slot('foo', null, []) \n" . ' @endslot', trim($result));
    }

    public function testDynamicSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot :name="$foo">
</x-slot>');

        $this->assertSame("@slot(\$foo, null, []) \n" . ' @endslot', trim($result));
    }

    public function testDynamicSlotsCanBeCompiledWithKeyOfObjects()
    {
        $result = $this->compiler()->compileSlots('<x-slot :name="$foo->name">
</x-slot>');

        $this->assertSame("@slot(\$foo->name, null, []) \n" . ' @endslot', trim($result));
    }

    public function testSlotsWithAttributesCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo" class="font-bold">
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => 'font-bold']) \n" . ' @endslot', trim($result));
    }

    public function testInlineSlotsWithAttributesCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot:foo class="font-bold">
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => 'font-bold']) \n" . ' @endslot', trim($result));
    }

    public function testSlotsWithDynamicAttributesCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo" :class="$classes">
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => \Diana\Rendering\Compiler::sanitizeComponentAttribute(\$classes)]) \n" . ' @endslot', trim($result));
    }

    public function testSlotsWithClassDirectiveCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo" @class($classes)>
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => \Diana\Rendering\Compiler::sanitizeComponentAttribute(\Diana\Support\Helpers\Arr::toCssClasses(\$classes))]) \n" . ' @endslot', trim($result));
    }

    public function testSlotsWithStyleDirectiveCanBeCompiled()
    {

        $result = $this->compiler()->compileSlots('<x-slot name="foo" @style($styles)>
</x-slot>');

        $this->assertSame("@slot('foo', null, ['style' => \Diana\Rendering\Compiler::sanitizeComponentAttribute(\Diana\Support\Helpers\Arr::toCssStyles(\$styles))]) \n" . ' @endslot', trim($result));
    }

    public function testBasicComponentParsing()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="foo" limit="5" @click="foo" wire:click="changePlan(\'{{ $plan }}\')" required x-intersect.margin.-50%.0px="visibleSection = \'profile\'" /><x-alert /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => 'foo','limit' => '5','@click' => 'foo','wire:click' => 'changePlan(\''.\Diana\Support\Helpers\Emit::e(\$plan).'\')','required' => true,'x-intersect.margin.-50%.0px' => 'visibleSection = \'profile\'']); ?>\n" .
            "@endComponentClass##END-COMPONENT-CLASS####BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testBasicComponentWithEmptyAttributesParsing()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="" limit=\'\' @click="" required /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => '','limit' => '','@click' => '','required' => true]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testDataCamelCasing()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', ['userId' => '1'])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonData()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', ['userId' => 1])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataShortSyntax()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :$userId></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', ['userId' => \$userId])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataWithStaticClassProperty()
    {

        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :userId="User::$id"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', ['userId' => User::\$id])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataWithStaticClassPropertyAndMultipleAttributes()
    {
        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :label="Input::$label" :$name value="Joe"></x-input>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestInputComponent', 'input', ['label' => Input::\$label,'name' => \$name,'value' => 'Joe'])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));

        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input value="Joe" :$name :label="Input::$label"></x-input>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestInputComponent', 'input', ['value' => 'Joe','name' => \$name,'label' => Input::\$label])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentWithColonDataShortSyntax()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :$userId/>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', ['userId' => \$userId])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentWithColonDataAndStaticClassPropertyShortSyntax()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :userId="User::$id"/>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', ['userId' => User::\$id])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentWithColonDataMultipleAttributesAndStaticClassPropertyShortSyntax()
    {
        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :label="Input::$label" value="Joe" :$name />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestInputComponent', 'input', ['label' => Input::\$label,'value' => 'Joe','name' => \$name])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));

        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :$name :label="Input::$label" value="Joe" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestInputComponent', 'input', ['name' => \$name,'label' => Input::\$label,'value' => 'Joe'])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testEscapedColonAttribute()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1" ::title="user.name"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', ['userId' => 1])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([':title' => 'user.name']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonAttributesIsEscapedIfStrings()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :src="\'foo\'"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['src' => \Diana\Rendering\Compiler::sanitizeComponentAttribute('foo')]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testClassDirective()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile @class(["bar"=>true])></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => \Diana\Rendering\Compiler::sanitizeComponentAttribute(\Diana\Support\Helpers\Arr::toCssClasses(['bar'=>true]))]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testStyleDirective()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile @style(["bar"=>true])></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['style' => \Diana\Rendering\Compiler::sanitizeComponentAttribute(\Diana\Support\Helpers\Arr::toCssStyles(['bar'=>true]))]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonNestedComponentParsing()
    {
        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x-foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonStartingNestedComponentParsing()
    {

        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x:foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiled()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert/></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testComponentsCanBeCompiledWithHyphenAttributes()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert class="bar" wire:model="foo" x-on:click="bar" @click="baz" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo','x-on:click' => 'bar','@click' => 'baz']); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithDataAndAttributes()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert title="foo" class="bar" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo']); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testComponentCanReceiveAttributeBag()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile class="bar" {{ $attributes }} wire:model="foo"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Diana\Rendering\Compiler::sanitizeComponentAttribute(\$attributes),'wire:model' => 'foo']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentCanReceiveAttributeBag()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert title="foo" class="bar" {{ $attributes->merge([\'class\' => \'test\']) }} wire:model="foo" /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Diana\Rendering\Compiler::sanitizeComponentAttribute(\$attributes->merge(['class' => 'test'])),'wire:model' => 'foo']); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testComponentsCanHaveAttachedWord()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile></x-profile>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##Words", trim($result));
    }

    public function testSelfClosingComponentsCanHaveAttachedWord()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert/>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##Words', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithBoundData()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert :title="$title" class="bar" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', ['title' => \$title])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar']); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testPairedComponentTags()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert>
</x-alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Tests\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Tests\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>
 @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testClasslessComponents()
    {
        $result = $this->compiler()->compileTags('<x-res:anonymous-component.blade.php :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Diana\Rendering\Components\AnonymousComponent', 'res:anonymous-component.blade.php', ['view' => '" . join(DIRECTORY_SEPARATOR, [__DIR__, 'res', 'anonymous-component.blade.php']) . "','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Diana\Rendering\ComponentAttributeBag && \$constructor = (new ReflectionClass(Diana\Rendering\Components\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(array_map(fn(\$param) => \$param->getName(), \$constructor->getParameters())); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Diana\Rendering\Compiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n" .
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testAttributeSanitization()
    {
        $class = new class {
            public function __toString()
            {
                return '<hi>';
            }
        };

        $this->assertEquals(\Diana\Support\Helpers\Emit::e('<hi>'), Compiler::sanitizeComponentAttribute('<hi>'));
        $this->assertEquals(\Diana\Support\Helpers\Emit::e('1'), Compiler::sanitizeComponentAttribute('1'));
        $this->assertEquals(1, Compiler::sanitizeComponentAttribute(1));
        $this->assertEquals(\Diana\Support\Helpers\Emit::e('<hi>'), Compiler::sanitizeComponentAttribute($class));
    }

    public function testItThrowsAnExceptionForNonExistingAliases()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->compiler(['alert' => 'foo.bar'])->compileTags('<x-alert />');
    }

    public function testItThrowsAnExceptionForNonExistingClass()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->compiler()->compileTags('<x-alert />');
    }

    public function testAttributesTreatedAsPropsAreRemovedFromFinalAttributes()
    {
        $__env = $this->renderer;

        $attributes = new ComponentAttributeBag(['userId' => 'bar', 'other' => 'ok']);

        $template = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile {{ $attributes }} />');
        $template = $this->compiler->compileString($template);

        ob_start();
        eval (" ?> $template <?php ");
        ob_get_clean();

        $this->assertSame($attributes->get('userId'), 'bar');
        $this->assertSame($attributes->get('other'), 'ok');
    }

    public function testOriginalAttributesAreRestoredAfterRenderingChildComponentWithProps()
    {
        $__env = $this->renderer;

        $attributes = new ComponentAttributeBag(['userId' => 'bar', 'other' => 'ok']);

        $template = $this->compiler([
            'container' => TestContainerComponent::class,
            'profile' => TestProfileComponent::class,
        ])->compileTags('<x-container><x-profile {{ $attributes }} /></x-container>');
        $template = $this->compiler->compileString($template);

        ob_start();

        $this->renderer->incrementRenderCount();
        eval (" ?> $template <?php ");
        $this->renderer->decrementRenderCount();
        if ($this->renderer->doneRendering())
            $this->renderer->flushState();

        ob_get_clean();

        $this->assertSame($attributes->get('userId'), 'bar');
        $this->assertSame($attributes->get('other'), 'ok');
    }

    protected function compiler(array $aliases = [], array $namespaces = [])
    {
        return new ComponentTagCompiler(
            $aliases,
            $namespaces,
            $this->compiler
        );
    }
}

class TestAlertComponent extends Component
{
    public $title;

    public function __construct($title = 'foo', $userId = 1)
    {
        $this->title = $title;
    }

    public function render(): string
    {
        return 'alert';
    }
}

class TestProfileComponent extends Component
{
    public $userId;

    public function __construct($userId = 'foo')
    {
        $this->userId = $userId;
    }

    public function render(): string
    {
        return 'profile';
    }
}

class TestInputComponent extends Component
{
    public $userId;

    public function __construct($name, $label, $value)
    {
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
    }

    public function render(): string
    {
        return 'input';
    }
}

class TestContainerComponent extends Component
{
    public function render(): string
    {
        return 'container';
    }
}
