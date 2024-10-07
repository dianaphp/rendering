<?php

namespace Diana\Rendering\Engines;

use Diana\Rendering\Compiler;
use Diana\Rendering\Contracts\Engine;
use Diana\Rendering\Exceptions\ViewException;
use Throwable;
use Diana\Support\Helpers\Str;

class CompilerEngine extends PhpEngine implements Engine
{
    /**
     * A stack of the last compiled templates.
     *
     * @var array
     */
    protected $lastCompiled = [];

    /**
     * The view paths that were compiled or are not expired, keyed by the path.
     *
     * @var array<string, true>
     */
    protected $compiledOrNotExpired = [];

    /**
     * Create a new compiler engine instance.
     *
     * @param  Compiler  $compiler
     * @return void
     */
    public function __construct(protected Compiler $compiler)
    {
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function run(string $path, array $data = []): string
    {
        $this->lastCompiled[] = $path;

        // If this given view has expired, which means it has simply been edited since
        // it was last compiled, we will re-compile the views so we can evaluate a
        // fresh copy of the view. We'll pass the compiler the path of the view.
        if (!isset($this->compiledOrNotExpired[$path]) && $this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        // Once we have the path to the compiled file, we will evaluate the paths with
        // typical PHP just like any other templates. We also keep a stack of views
        // which have been rendered for right exception messages to be generated.

        try {
            $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
        } catch (ViewException $e) {
            if (!Str::contains($e->getMessage(), ['No such file or directory', 'File does not exist at path'])) {
                throw $e;
            }

            if (!isset($this->compiledOrNotExpired[$path])) {
                throw $e;
            }

            $this->compiler->compile($path);

            $results = $this->evaluatePath($this->compiler->getCompiledPath($path), $data);
        }

        $this->compiledOrNotExpired[$path] = true;

        array_pop($this->lastCompiled);

        return $results;
    }

    /**
     * Handle a view exception.
     *
     * @throws Throwable
     */
    protected function handleViewException(Throwable $e, int $obLevel): void
    {
        parent::handleViewException(new ViewException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e), $obLevel);
    }

    /**
     * Get the exception message for an exception.
     *
     * @param  \Throwable  $e
     * @return string
     */
    protected function getMessage(Throwable $e)
    {
        return $e->getMessage() . ' (View: ' . realpath(end($this->lastCompiled)) . ')';
    }

    /**
     * Get the compiler implementation.
     */
    public function getCompiler()
    {
        return $this->compiler;
    }

    /**
     * Clear the cache of views that were compiled or not expired.
     *
     * @return void
     */
    public function forgetCompiledOrNotExpired()
    {
        $this->compiledOrNotExpired = [];
    }
}
