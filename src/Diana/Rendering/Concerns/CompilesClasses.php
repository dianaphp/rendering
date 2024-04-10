<?php

namespace Diana\Rendering\Concerns;

trait CompilesClasses
{
    /**
     * Compile the conditional class statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileClass($expression)
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "class=\"<?php echo \Diana\Support\Helpers\Arr::toCssClasses{$expression}; ?>\"";
    }
}
