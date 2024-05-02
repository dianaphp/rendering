<?php

namespace Diana\Rendering\Drivers;

use Diana\Rendering\Contracts\Renderer;
use Diana\Rendering\Contracts\Engine;
use Diana\Support\Helpers\Arr;
use Diana\Support\Helpers\Data;
use Diana\Support\Helpers\Filesystem;

use Diana\Rendering\Compiler;
use Diana\Rendering\Concerns;
use Diana\Rendering\View;

use InvalidArgumentException;

class BladeRenderer implements Renderer
{
    use Concerns\ManagesComponents,
        Concerns\ManagesFragments,
        Concerns\ManagesLayouts,
        Concerns\ManagesLoops,
        Concerns\ManagesStacks,
        Concerns\ManagesTranslations;

    public array $extensions = [];

    public array $instances = [];

    public function registerEngine(array|string $extensions, string|Engine|callable $engine)
    {
        $engine = Data::valueOf($engine);
        $class = is_string($engine) ? $engine : $engine::class;
        foreach (Arr::wrap($extensions) as $extension)
            $this->extensions[$extension] = $class;

        $this->instances[$class] = $engine instanceof Engine ? $engine : new $engine;

        return $this;
    }

    public function make(string $path, array $data = []): View
    {
        return new View($this, Filesystem::absPath($path), $data);
    }

    /**
     * Evaluate and render a Blade string to HTML.
     *
     * @param  string  $string
     * @param  array  $data
     * @param  bool  $deleteCachedView
     * @return string
     */
    // public function renderString($string, $data = [], $deleteCachedView = false)
    // {
    //     $component = new class ($string) extends Component {
    //         public function __construct(protected View|Closure|string $template)
    //         {
    //         }

    //         public function render(): View|Closure|string
    //         {
    //             return $this->template;
    //         }
    //     };

    //     $view = $this->make($component->resolveView(), $data);

    //     $render = $view->render();

    //     if ($deleteCachedView) {
    //         @unlink($view->getPath());
    //     }

    //     return $render;
    // }

    // /**
    //  * Render a component instance to HTML.
    //  *
    //  * @param  Component  $component
    //  * @return string
    //  */
    // public function renderComponent(Component $component)
    // {
    //     $data = $component->data();

    //     $view = Data::valueOf($component->resolveView(), $data);

    //     if (!$view instanceof View)
    //         $view = $this->make($view);

    //     return $view->with($data)->render();
    // }

    /**
     * Data that should be available to all templates.
     *
     * @var array
     */
    protected $shared = [];

    /**
     * The number of active rendering operations.
     *
     * @var int
     */
    protected $renderCount = 0;

    /**
     * The "once" block IDs that have been rendered.
     *
     * @var array
     */
    protected $renderedOnce = [];

    /**
     * Create a new view factory instance.
     *
     * @return void
     */
    public function __construct(Compiler $compiler)
    {
        $this->share('__env', $this);
        $this->share('__compiler', $compiler);
    }

    /**
     * Get the appropriate view engine for the given path.
     *
     * @param  string  $path
     * @return Engine
     *
     * @throws \InvalidArgumentException
     */
    public function getEngineFromPath($path): Engine
    {
        if (!$extension = $this->getExtension($path))
            throw new InvalidArgumentException("Unrecognized extension in file: {$path}.");

        return $this->instances[$this->extensions[$extension]];
    }

    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);

        return Arr::first($extensions, function ($value) use ($path) {
            return str_ends_with($path, '.' . $value);
        });
    }

    /**
     * Add a piece of shared data to the environment.
     *
     * @param  array|string  $key
     * @param  mixed|null  $value
     * @return mixed
     */
    public function share($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            $this->shared[$key] = $value;
        }

        return $value;
    }


    /**
     * Check if there are no active render operations.
     *
     * @return bool
     */
    public function doneRendering(): bool
    {
        return $this->renderCount == 0;
    }

    public function incrementRenderCount(): void
    {
        $this->renderCount++;
    }

    public function decrementRenderCount(): void
    {
        $this->renderCount--;
    }

    /**
     * Determine if the given once token has been rendered.
     *
     * @param  string  $id
     * @return bool
     */
    public function hasRenderedOnce(string $id)
    {
        return isset($this->renderedOnce[$id]);
    }

    /**
     * Mark the given once token as having been rendered.
     *
     * @param  string  $id
     * @return void
     */
    public function markAsRenderedOnce(string $id)
    {
        $this->renderedOnce[$id] = true;
    }

    /**
     * Flush all of the factory state like sections and stacks.
     *
     * @return void
     */
    public function flushState(): void
    {
        $this->renderCount = 0;
        $this->renderedOnce = [];

        $this->flushSections();
        $this->flushStacks();
        $this->flushComponents();
        $this->flushFragments();
    }

    /**
     * Get the extension to engine bindings.
     *
     * @return array
     */
    public function getExtensions()
    {
        return array_keys($this->extensions);
    }

    public function getShared(): array
    {
        return $this->shared;
    }
}