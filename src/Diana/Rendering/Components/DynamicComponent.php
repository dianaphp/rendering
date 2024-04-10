<?php

namespace Diana\Rendering\Components;

use Closure;
use Diana\Rendering\ComponentTagCompiler;
use Diana\Support\Helpers\Arr;
use Diana\Support\Helpers\Str;

class DynamicComponent extends Component
{
    /**
     * The name of the component.
     *
     * @var string
     */
    public $component;

    /**
     * The component tag compiler instance.
     *
     * @var ComponentTagCompiler
     */
    protected static $compiler;

    /**
     * The cached component classes.
     *
     * @var array
     */
    protected static $componentClasses = [];

    /**
     * Create a new component instance.
     *
     * @param  string  $component
     * @return void
     */
    public function __construct(string $component)
    {
        $this->component = $component;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): Closure
    {
        $template = <<<'EOF'
<?php extract(\Diana\Support\Helpers\Arr::mapWithKeys($attributes->getAttributes(), function ($value, $key) { return [Diana\Support\Helpers\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; }), EXTR_SKIP); ?>
{{ props }}
<x-{{ component }} {{ bindings }} {{ attributes }}>
{{ slots }}
{{ defaultSlot }}
</x-{{ component }}>
EOF;


        return function ($data) use ($template) {
            $bindings = $this->bindings($class = $this->classForComponent());

            return str_replace(
                [
                    '{{ component }}',
                    '{{ props }}',
                    '{{ bindings }}',
                    '{{ attributes }}',
                    '{{ slots }}',
                    '{{ defaultSlot }}',
                ],
                [
                    $this->component,
                    $this->compileProps($bindings),
                    $this->compileBindings($bindings),
                    class_exists($class) ? '{{ $attributes }}' : '',
                    $this->compileSlots($data['__laravel_slots']),
                    '{{ $slot ?? "" }}',
                ],
                $template
            );
        };
    }

    /**
     * Compile the @props directive for the component.
     *
     * @param  array  $bindings
     * @return string
     */
    protected function compileProps(array $bindings)
    {
        if (empty($bindings)) {
            return '';
        }

        return '@props(' . '[\'' . implode('\',\'', array_map(function ($dataKey) {
            return Str::camel($dataKey);
        }, $bindings)) . '\']' . ')';
    }

    /**
     * Compile the bindings for the component.
     *
     * @param  array  $bindings
     * @return string
     */
    protected function compileBindings(array $bindings)
    {
        return implode(' ', array_map(function ($key) {
            return ':' . $key . '="$' . Str::camel(str_replace([':', '.'], ' ', $key)) . '"';
        }, $bindings));
    }

    /**
     * Compile the slots for the component.
     *
     * @param  array  $slots
     * @return string
     */
    protected function compileSlots(array $slots)
    {
        return implode(PHP_EOL, array_filter(Arr::map($slots, function ($slot, $name) {
            return $name === '__default' ? null : '<x-slot name="' . $name . '" ' . ((string) $slot->attributes) . '>{{ $' . $name . ' }}</x-slot>';
        })));
    }

    /**
     * Get the class for the current component.
     *
     * @return string
     */
    protected function classForComponent()
    {
        if (isset(static::$componentClasses[$this->component])) {
            return static::$componentClasses[$this->component];
        }

        return static::$componentClasses[$this->component] = Str::formatClass($this->component);
    }

    /**
     * Get the names of the variables that should be bound to the component.
     *
     * @param  string  $class
     * @return array
     */
    protected function bindings(string $class)
    {
        [$data, $attributes] = ComponentTagCompiler::partitionDataAndAttributes($class, $this->attributes->getAttributes());

        return array_keys($data);
    }
}
