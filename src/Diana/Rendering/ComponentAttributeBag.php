<?php

namespace Diana\Rendering;

use ArrayAccess;
use ArrayIterator;
use Diana\Support\Helpers\Arr;
use Diana\Support\Helpers\Data;
use Diana\Support\Helpers\Str;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use Diana\Rendering\AppendableAttributeValue;

class ComponentAttributeBag implements ArrayAccess, IteratorAggregate, JsonSerializable
{
    /**
     * The raw array of attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new component attribute bag instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the first attribute's value.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function first($default = null)
    {
        return $this->getIterator()->current() ?? Data::valueOf($default);
    }

    /**
     * Get a given attribute from the attribute array.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->attributes[$key] ?? Data::valueOf($default);
    }

    /**
     * Determine if a given attribute exists in the attribute array.
     *
     * @param  array|string  $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if (!array_key_exists($value, $this->attributes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in the attribute array.
     *
     * @param  array|string  $key
     * @return bool
     */
    public function hasAny($key)
    {
        if (!count($this->attributes)) {
            return false;
        }

        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->has($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given attribute is missing from the attribute array.
     *
     * @param  string  $key
     * @return bool
     */
    public function missing($key)
    {
        return !$this->has($key);
    }

    /**
     * Only include the given attribute from the attribute array.
     *
     * @param  mixed  $keys
     * @return static
     */
    public function only($keys)
    {
        return new static(Arr::only($this->attributes, Arr::wrap($keys)));
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function except($keys)
    {
        return new static(Arr::except($this->attributes, Arr::wrap($keys)));
    }

    /**
     * Filter the attributes, returning a bag of attributes that pass the filter.
     *
     * @param  callable  $callback
     * @return static
     */
    public function filter($callback)
    {
        return new static(array_filter($this->attributes, $callback));
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string|string[]  $needles
     * @return static
     */
    public function whereStartsWith($needles)
    {
        return $this->filter(function ($value, $key) use ($needles) {
            return Str::startsWith($key, $needles);
        });
    }

    /**
     * Return a bag of attributes with keys that do not start with the given value / pattern.
     *
     * @param  string|string[]  $needles
     * @return static
     */
    public function whereDoesntStartWith($needles)
    {
        return $this->filter(function ($value, $key) use ($needles) {
            return !Str::startsWith($key, $needles);
        });
    }

    /**
     * Return a bag of attributes that have keys starting with the given value / pattern.
     *
     * @param  string|string[]  $needles
     * @return static
     */
    public function thatStartWith($needles)
    {
        return $this->whereStartsWith($needles);
    }

    /**
     * Only include the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function onlyProps($keys)
    {
        return $this->only($this->extractPropNames($keys));
    }

    /**
     * Exclude the given attribute from the attribute array.
     *
     * @param  mixed|array  $keys
     * @return static
     */
    public function exceptProps($keys)
    {
        return $this->except($this->extractPropNames($keys));
    }

    /**
     * Extract prop names from given keys.
     *
     * @param  mixed|array  $keys
     * @return array
     */
    protected function extractPropNames($keys)
    {
        $props = [];

        foreach ($keys as $key => $defaultValue) {
            $key = is_numeric($key) ? $defaultValue : $key;

            $props[] = $key;
            $props[] = Str::kebab($key);
        }

        return $props;
    }

    /**
     * Merge additional attributes / values into the attribute bag.
     *
     * @param  array  $attributeDefaults
     * @param  bool  $escape
     * @return static
     */
    public function merge(array $attributeDefaults = [], $escape = true)
    {
        $attributeDefaults = array_map(function ($value) use ($escape) {
            return $this->shouldEscapeAttributeValue($escape, $value)
                ? \Diana\Support\Helpers\Emit::e($value)
                : $value;
        }, $attributeDefaults);


        $attributes = $this->attributes;
        foreach ($attributes as $key => $value) {
            if ($key === 'class' || $key === 'style' || (isset($attributeDefaults[$key]) && $attributeDefaults[$key] instanceof AppendableAttributeValue))
                Arr::mapWithKeys($value, function ($value, $key) use ($attributeDefaults, $escape) {
                    $defaultsValue = isset ($attributeDefaults[$key]) && $attributeDefaults[$key] instanceof AppendableAttributeValue
                        ? $this->resolveAppendableAttributeDefault($attributeDefaults, $key, $escape)
                        : ($attributeDefaults[$key] ?? '');

                    if ($key === 'style') {
                        $value = Str::finish($value, ';');
                    }

                    return [$key => implode(' ', array_unique(array_filter([$defaultsValue, $value])))];
                });
        }

        return new static(array_merge($attributeDefaults, $attributes));
    }

    /**
     * Determine if the specific attribute value should be escaped.
     *
     * @param  bool  $escape
     * @param  mixed  $value
     * @return bool
     */
    protected function shouldEscapeAttributeValue($escape, $value)
    {
        if (!$escape) {
            return false;
        }

        return !is_object($value) &&
            !is_null($value) &&
            !is_bool($value);
    }

    /**
     * Create a new appendable attribute value.
     *
     * @param  mixed  $value
     * @return AppendableAttributeValue
     */
    public function prepends($value)
    {
        return new AppendableAttributeValue($value);
    }

    /**
     * Resolve an appendable attribute value default value.
     *
     * @param  array  $attributeDefaults
     * @param  string  $key
     * @param  bool  $escape
     * @return mixed
     */
    protected function resolveAppendableAttributeDefault($attributeDefaults, $key, $escape)
    {
        if ($this->shouldEscapeAttributeValue($escape, $value = $attributeDefaults[$key]->value)) {
            $value = \Diana\Support\Helpers\Emit::e($value);
        }

        return $value;
    }

    /**
     * Determine if the attribute bag is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return trim((string) $this) === '';
    }

    /**
     * Determine if the attribute bag is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * Get all of the raw attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the underlying attributes.
     *
     * @param  array  $attributes
     * @return void
     */
    public function setAttributes(array $attributes)
    {
        if (
            isset($attributes['attributes']) &&
            $attributes['attributes'] instanceof self
        ) {
            $parentBag = $attributes['attributes'];

            unset($attributes['attributes']);

            $attributes = $parentBag->merge($attributes, $escape = false)->getAttributes();
        }

        $this->attributes = $attributes;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Get the value at the given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * Remove the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->attributes);
    }

    /**
     * Convert the object into a JSON serializable form.
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }

    /**
     * Implode the attributes into a single HTML ready string.
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';

        foreach ($this->attributes as $key => $value) {
            if ($value === false || is_null($value)) {
                continue;
            }

            if ($value === true) {
                // Exception for Alpine...
                $value = $key === 'x-data' ? '' : $key;
            }

            $string .= ' ' . $key . '="' . str_replace('"', '\\"', trim($value)) . '"';
        }

        return trim($string);
    }
}
