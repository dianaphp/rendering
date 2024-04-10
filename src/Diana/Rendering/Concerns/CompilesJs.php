<?php

namespace Diana\Rendering\Concerns;

use Diana\Rendering\Js;

trait CompilesJs
{
    /**
     * Compile the "@js" directive into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJs(string $expression)
    {
        return sprintf(
            "<?php echo \%s::from(%s); ?>",
            Js::class,
            $this->stripParentheses($expression)
        );
    }
}
