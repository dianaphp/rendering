<?php

namespace Diana\Rendering\Components;

use Closure;
use Diana\Rendering\Exceptions\CompilationPathNotSetException;
use Diana\Rendering\View;
use Diana\Runtime\Container;
use Diana\Rendering\ComponentAttributeBag;
use Diana\Rendering\InvokableComponentVariable;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class Component
{
    // The properties / methods that should not be exposed to the component.
    protected array $except = [];

    // The component alias name.
    public string $componentName;

    // The component attributes.
    public ?ComponentAttributeBag $attributes = null;


    protected static $compilationPath;

    public static function setCompilationPath(string $compilationPath)
    {
        static::$compilationPath = $compilationPath;
    }

    /**
     * The component resolver callback.
     *
     * @var (\Closure(string, array): Component)|null
     */
    protected static ?Closure $componentsResolver = null;

    /**
     * The cache of blade view names, keyed by contents.
     *
     * @var array<string, string>
     */
    protected static array $bladeViewCache = [];

    // The cache of public property names, keyed by class.
    protected static array $propertyCache = [];

    // The cache of public method names, keyed by class.
    protected static array $methodCache = [];

    /**
     * The cache of constructor parameters, keyed by class.
     *
     * @var array<class-string, array<int, string>>
     */
    protected static array $constructorParametersCache = [];

    // Get the view / view contents that represent the component.
    abstract public function render(): View|Closure|string;

    /**
     * Resolve the component instance with the given data.
     *
     * @param  array  $data
     * @return static
     */
    public static function resolve($data)
    {
        if (static::$componentsResolver) {
            return call_user_func(static::$componentsResolver, static::class, $data);
        }

        $parameters = static::extractConstructorParameters();

        $dataKeys = array_keys($data);

        if (empty(array_diff($parameters, $dataKeys)) || !class_exists(Container::class)) {
            return new static(...array_intersect_key($data, array_flip($parameters)));
        }

        // TODO: The only occurance where we need access to the container in order to resolve, check if this can't be implemented differently
        return Container::getInstance()->resolve(static::class, $data);
    }

    /**
     * Extract the constructor parameters for the component.
     *
     * @return array
     */
    protected static function extractConstructorParameters()
    {
        if (!isset(static::$constructorParametersCache[static::class])) {
            $class = new ReflectionClass(static::class);

            $constructor = $class->getConstructor();

            static::$constructorParametersCache[static::class] = $constructor
                ? array_map(fn($param) => $param->getName(), $constructor->getParameters())
                : [];
        }

        return static::$constructorParametersCache[static::class];
    }

    /**
     * Resolve the Blade view or view file that should be used when rendering the component.
     *
     * @return View|\Closure|string
     */
    public function resolveView()
    {
        $view = $this->render();

        if ($view instanceof View)
            return $view;

        $resolver = function ($view) {
            if ($view instanceof View)
                return $view;

            return $this->extractBladeViewFromString($view);
        };

        return $view instanceof Closure ? function (array $data = []) use ($view, $resolver) {
            return $resolver($view($data));
        }
            : $resolver($view);
    }

    /**
     * Create a Blade view with the raw component string content.
     *
     * @param  string  $contents
     * @return string
     */
    protected function extractBladeViewFromString($contents)
    {
        $key = sprintf('%s::%s', static::class, $contents);

        if (isset(static::$bladeViewCache[$key])) {
            return static::$bladeViewCache[$key];
        }

        if (!static::$compilationPath)
            throw new CompilationPathNotSetException('Attempted to render a component without setting a compilation output path.');

        if (strlen($contents) <= PHP_MAXPATHLEN && file_exists(trim(static::$compilationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $contents))
            return static::$bladeViewCache[$key] = $contents;

        return static::$bladeViewCache[$key] = $this->createBladeViewFromString($contents);
    }

    /**
     * Create a Blade view with the raw component string content.
     *
     * @param  string  $contents
     * @return string
     */
    protected function createBladeViewFromString($contents)
    {
        if (!is_file($viewFile = static::$compilationPath . DIRECTORY_SEPARATOR . hash('xxh128', $contents) . '.blade.php')) {
            if (!is_dir(static::$compilationPath)) {
                mkdir(static::$compilationPath, 0755, true);
            }

            file_put_contents($viewFile, $contents);
        }

        return $viewFile;
    }

    /**
     * Get the data that should be supplied to the view.
     *
     * @author Freek Van der Herten
     * @author Brent Roose
     *
     * @return array
     */
    public function data()
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        return array_merge($this->extractPublicProperties(), $this->extractPublicMethods());
    }

    /**
     * Extract the public properties for the component.
     *
     * @return array
     */
    protected function extractPublicProperties()
    {
        $class = get_class($this);

        if (!isset(static::$propertyCache[$class])) {
            $reflection = new ReflectionClass($this);

            $props = array_filter(
                $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
                function (ReflectionProperty $prop) {
                    return !$prop->isStatic() || !$this->shouldIgnore($prop->getName());
                }
            );

            static::$propertyCache[$class] = array_map(fn(ReflectionProperty $prop) => $prop->getName(), $props);
        }

        $values = [];

        foreach (static::$propertyCache[$class] as $property) {
            $values[$property] = $this->{$property};
        }

        return $values;
    }

    /**
     * Extract the public methods for the component.
     *
     * @return array
     */
    protected function extractPublicMethods()
    {
        $class = get_class($this);

        if (!isset(static::$methodCache[$class])) {
            $reflection = new ReflectionClass($this);

            $methods = array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), fn(ReflectionMethod $method) => !$this->shouldIgnore($method->getName()));
            ;

            static::$methodCache[$class] = array_map(fn(ReflectionMethod $method) => $method->getName(), $methods);
        }

        $values = [];

        foreach (static::$methodCache[$class] as $method) {
            $values[$method] = $this->createVariableFromMethod(new ReflectionMethod($this, $method));
        }

        return $values;
    }

    /**
     * Create a callable variable from the given method.
     *
     * @param  \ReflectionMethod  $method
     * @return mixed
     */
    protected function createVariableFromMethod(ReflectionMethod $method)
    {
        return $method->getNumberOfParameters() === 0
            ? $this->createInvokableVariable($method->getName())
            : Closure::fromCallable([$this, $method->getName()]);
    }

    /**
     * Create an invokable, toStringable variable for the given component method.
     *
     * @param  string  $method
     * @return InvokableComponentVariable
     */
    protected function createInvokableVariable(string $method)
    {
        return new InvokableComponentVariable(function () use ($method) {
            return $this->{$method}();
        });
    }

    /**
     * Determine if the given property / method should be ignored.
     *
     * @param  string  $name
     * @return bool
     */
    protected function shouldIgnore($name)
    {
        return str_starts_with($name, '__') ||
            in_array($name, $this->ignoredMethods());
    }

    /**
     * Get the methods that should be ignored.
     *
     * @return array
     */
    protected function ignoredMethods()
    {
        return array_merge([
            'data',
            'render',
            'resolve',
            'resolveView',
            'shouldRender',
            'view',
            'withName',
            'withAttributes',
            'flushCache',
            'forgetComponentsResolver',
            'resolveComponentsUsing',
        ], $this->except);
    }

    /**
     * Set the component alias name.
     *
     * @param  string  $name
     * @return $this
     */
    public function withName($name)
    {
        $this->componentName = $name;

        return $this;
    }

    /**
     * Set the extra attributes that the component should make available.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function withAttributes(array $attributes)
    {
        $this->attributes = $this->attributes ?: $this->newAttributeBag();

        $this->attributes->setAttributes($attributes);

        return $this;
    }

    // Get a new attribute bag instance.
    protected function newAttributeBag(array $attributes = []): ComponentAttributeBag
    {
        return new ComponentAttributeBag($attributes);
    }

    /**
     * Determine if the component should be rendered.
     *
     * @return bool
     */
    public function shouldRender()
    {
        return true;
    }

    /**
     * Flush the component's cached state.
     *
     * @return void
     */
    public static function flushCache()
    {
        static::$bladeViewCache = [];
        static::$constructorParametersCache = [];
        static::$methodCache = [];
        static::$propertyCache = [];
    }

    /**
     * Forget the component's resolver callback.
     *
     * @return void
     *
     * @internal
     */
    public static function forgetComponentsResolver()
    {
        static::$componentsResolver = null;
    }

    /**
     * Set the callback that should be used to resolve components within views.
     *
     * @param  \Closure(string $component, array $data): Component  $resolver
     * @return void
     *
     * @internal
     */
    public static function resolveComponentsUsing($resolver)
    {
        static::$componentsResolver = $resolver;
    }
}
