<?php

namespace Diana\Rendering;

use ArrayIterator;
use Closure;
use IteratorAggregate;
use Stringable;
use Traversable;

class InvokableComponentVariable implements IteratorAggregate, Stringable
{
    /**
     * The callable instance to resolve the variable value.
     *
     * @var Closure
     */
    protected Closure $callable;

    /**
     * Create a new variable instance.
     *
     * @param  Closure  $callable
     * @return void
     */
    public function __construct(Closure $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Get an iterator instance for the variable.
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        $result = $this->__invoke();

        return new ArrayIterator($result);
    }

    /**
     * Dynamically proxy attribute access to the variable.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->__invoke()->{$key};
    }

    /**
     * Dynamically proxy method access to the variable.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->__invoke()->{$method}(...$parameters);
    }

    /**
     * Resolve the variable.
     *
     * @return mixed
     */
    public function __invoke(): mixed
    {
        return call_user_func($this->callable);
    }

    public function __toString(): string
    {
        return (string) $this->__invoke();
    }
}
