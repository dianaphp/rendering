<?php

namespace Diana\Rendering\Concerns;

use InvalidArgumentException;

trait ManagesStacks
{
    /**
     * All of the finished, captured push sections.
     *
     * @var array
     */
    protected $pushes = [];

    /**
     * All of the finished, captured prepend sections.
     *
     * @var array
     */
    protected $prepends = [];

    /**
     * The stack of in-progress push sections.
     *
     * @var array
     */
    protected $pushStack = [];

    /**
     * Start injecting content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPush($section, $content = '')
    {
        if ($content === '') {
            if (ob_start())
                $this->pushStack[] = $section;
        } else
            $this->extendPush($section, $content);
    }

    /**
     * Stop injecting content into a push section.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopPush()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a push stack without first starting one.');
        }

        $last = array_pop($this->pushStack);

        $this->extendPush($last, ob_get_clean());

        return $last;
    }

    /**
     * Append content to a given push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPush($section, $content)
    {
        if (!isset($this->pushes[$section]))
            $this->pushes[$section] = [];

        $this->pushes[$section][$this->renderCount] = ($this->pushes[$section][$this->renderCount] ?? "") . $content;
    }

    /**
     * Start prepending content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPrepend($section, $content = '')
    {
        if ($content === '') {
            if (ob_start())
                $this->pushStack[] = $section;
        } else
            $this->extendPrepend($section, $content);
    }

    /**
     * Stop prepending content into a push section.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopPrepend()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a prepend operation without first starting one.');
        }

        $last = array_pop($this->pushStack);
        $this->extendPrepend($last, ob_get_clean());
        return $last;
    }

    /**
     * Prepend content to a given stack.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPrepend($section, $content)
    {
        if (!isset($this->prepends[$section]))
            $this->prepends[$section] = [];


        $this->prepends[$section][$this->renderCount] = ($this->prepends[$section][$this->renderCount] ?? "") . $content;
    }

    /**
     * Get the string contents of a push section.
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldPushContent($section, $default = '')
    {
        $output = '';

        if (isset($this->prepends[$section]))
            $output .= implode(array_reverse($this->prepends[$section]));

        if (isset($this->pushes[$section]))
            $output .= implode($this->pushes[$section]);

        return $output ?: $default;
    }

    /**
     * Flush all of the stacks.
     *
     * @return void
     */
    public function flushStacks()
    {
        $this->pushes = [];
        $this->prepends = [];
        $this->pushStack = [];
    }
}
