<?php

namespace Diana\Rendering;

use Diana\Support\Helpers\Arr;
use Diana\Support\Helpers\Str;
use Diana\Rendering\Components\AnonymousComponent;
use Diana\Rendering\Components\DynamicComponent;
use InvalidArgumentException;
use ReflectionClass;

/**
 * @author Spatie bvba <info@spatie.be>
 * @author Taylor Otwell <taylor@laravel.com>
 */
class ComponentTagCompiler
{
    const HINT_PATH_DELIMITER = "::";

    /**
     * The Blade compiler instance.
     *
     * @var Compiler
     */
    protected Compiler $blade;

    /**
     * The component class aliases.
     *
     * @var array
     */
    protected array $aliases = [];

    /**
     * The component class namespaces.
     *
     * @var array
     */
    protected array $namespaces = [];

    /**
     * The "bind:" attributes that have been compiled for the current component.
     *
     * @var array
     */
    protected array $boundAttributes = [];

    /**
     * Create a new component tag compiler.
     *
     * @param  array  $aliases
     * @param  array  $namespaces
     * @param  Compiler|null  $blade
     * @return void
     */
    public function __construct(array $aliases = [], array $namespaces = [], ?Compiler $blade = null)
    {
        $this->aliases = $aliases;
        $this->namespaces = $namespaces;

        $this->blade = $blade ?: new Compiler(sys_get_temp_dir());
    }

    /**
     * Compile the component and slot tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    public function compile(string $value): string
    {
        $value = $this->compileSlots($value);

        return $this->compileTags($value);
    }

    /**
     * Compile the tags within the given string.
     *
     * @param  string  $value
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function compileTags(string $value): string
    {
        $value = $this->compileSelfClosingTags($value);
        $value = $this->compileOpeningTags($value);
        $value = $this->compileClosingTags($value);

        return $value;
    }

    /**
     * Compile the opening tags within the given string.
     *
     * @param  string  $value
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileOpeningTags(string $value): string
    {
        $pattern = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $this->boundAttributes = [];

            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches[1], $attributes);
        }, $value);
    }

    /**
     * Compile the self-closing tags within the given string.
     *
     * @param  string  $value
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function compileSelfClosingTags(string $value): string
    {
        $pattern = "/
            <
                \s*
                x[-\:]([\w\-\:\.]*)
                \s*
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
            \/>
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $this->boundAttributes = [];

            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches[1], $attributes) . "\n@endComponentClass##END-COMPONENT-CLASS##";
        }, $value);
    }

    /**
     * Compile the Blade component string for the given component and attributes.
     *
     * @param  string  $component
     * @param  array  $attributes
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function componentString(string $component, array $attributes): string
    {
        if (isset($this->aliases[$component])) {
            if (!class_exists($this->aliases[$component])) {
                throw new InvalidArgumentException("Unable to locate a class or view for component [{$component}].");
            }

            $class = $this->aliases[$component];
        } else {
            $class = '\\' . Str::formatClass($component);
        }

        [$data, $attributes] = self::partitionDataAndAttributes($class, $attributes);

        $data = Arr::mapWithKeys($data, function ($value, $key) {
            return [Str::camel($key) => $value];
        });

        // If the component doesn't exist as a class, we'll assume it's a class-less
        // component and pass the component as a view parameter to the data so it
        // can be accessed within the component, and we can render out the view.
        if (!class_exists($class)) {
            $view = str_replace(':', '/', $component);
            if (file_exists($view)) {
                $view = "'$view'";
            } else {
                throw new InvalidArgumentException("Unable to locate a class or view for component [{$component}].");
            }

            $parameters = [
                'view' => $view,
                'data' => '[' . $this->attributesToString($data, $escapeBound = false) . ']',
            ];

            $class = AnonymousComponent::class;
        } else {
            $parameters = $data;
        }

        return "##BEGIN-COMPONENT-CLASS##@component('{$class}', '{$component}', [" . $this->attributesToString($parameters, $escapeBound = false) . '])
<?php if (isset($attributes) && $attributes instanceof Diana\Rendering\ComponentAttributeBag && $constructor = (new ReflectionClass(' . $class . '::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(array_map(fn($param) => $param->getName(), $constructor->getParameters())); ?>
<?php endif; ?>
<?php $component->withAttributes([' . $this->attributesToString($attributes, $escapeAttributes = $class !== DynamicComponent::class) . ']); ?>';
    }

    /**
     * Partition the data and extra attributes from the given array of attributes.
     *
     * @param string $class
     * @param  array  $attributes
     * @return array
     */
    public static function partitionDataAndAttributes(string $class, array $attributes): array
    {
        // If the class doesn't exist, we'll assume it is a class-less component and
        // return all the attributes as both data and attributes since we have
        // now way to partition them. The user can exclude attributes manually.
        if (!class_exists($class)) {
            return [$attributes, $attributes];
        }

        $constructor = (new ReflectionClass($class))->getConstructor();

        $parameterNames = $constructor
            ? array_map(fn($param) => $param->getName(), $constructor->getParameters())
            : [];

        $partition = [true => [], false => []];
        foreach ($attributes as $key => $value) {
            $partition[in_array(Str::camel($key), $parameterNames)][$key] = $value;
        }

        return array_values($partition);
    }

    /**
     * Compile the closing tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileClosingTags(string $value): string
    {
        return preg_replace("/<\/\s*x[-\:][\w\-\:\.]*\s*>/", ' @endComponentClass##END-COMPONENT-CLASS##', $value);
    }

    /**
     * Compile the slot tags within the given string.
     *
     * @param  string  $value
     * @return string
     */
    public function compileSlots(string $value): string
    {
        $pattern = "/
            <
                \s*
                x[\-\:]slot
                (?:\:(?<inlineName>\w+(?:-\w+)*))?
                (?:\s+name=(?<name>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+)))?
                (?:\s+\:name=(?<boundName>(\"[^\"]+\"|\\\'[^\\\']+\\\'|[^\s>]+)))?
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        $value = preg_replace_callback($pattern, function ($matches) {
            $name = $this->stripQuotes($matches['inlineName'] ?: $matches['name'] ?: $matches['boundName']);

            if (Str::contains($name, '-') && !empty($matches['inlineName'])) {
                $name = Str::camel($name);
            }

            // If the name was given as a simple string, we will wrap it in quotes as if it was bound for convenience...
            if (!empty($matches['inlineName']) || !empty($matches['name'])) {
                $name = "'{$name}'";
            }

            $this->boundAttributes = [];

            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            // If an inline name was provided and a name or bound name was *also* provided, we will assume the name should be an attribute...
            if (!empty($matches['inlineName']) && (!empty($matches['name']) || !empty($matches['boundName']))) {
                $attributes = !empty($matches['name'])
                    ? array_merge($attributes, $this->getAttributesFromAttributeString('name=' . $matches['name']))
                    : array_merge($attributes, $this->getAttributesFromAttributeString(':name=' . $matches['boundName']));
            }

            return " @slot({$name}, null, [" . $this->attributesToString($attributes) . ']) ';
        }, $value);

        return preg_replace('/<\/\s*x[\-\:]slot[^>]*>/', ' @endslot', $value);
    }

    /**
     * Get an array of attributes from the given attribute string.
     *
     * @param  string  $attributeString
     * @return array
     */
    protected function getAttributesFromAttributeString(string $attributeString)
    {
        $attributeString = $this->parseShortAttributeSyntax($attributeString);
        $attributeString = $this->parseAttributeBag($attributeString);
        $attributeString = $this->parseComponentTagClassStatements($attributeString);
        $attributeString = $this->parseComponentTagStyleStatements($attributeString);
        $attributeString = $this->parseBindAttributes($attributeString);

        $pattern = '/
            (?<attribute>[\w\-:.@%]+)
            (
                =
                (?<value>
                    (
                        \"[^\"]+\"
                        |
                        \\\'[^\\\']+\\\'
                        |
                        [^\s>]+
                    )
                )
            )?
        /x';

        if (!preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return Arr::mapWithKeys($matches, function ($match) {
            $attribute = $match['attribute'];
            $value = $match['value'] ?? null;

            if (is_null($value)) {
                $value = 'true';

                $attribute = Str::start($attribute, 'bind:');
            }

            $value = $this->stripQuotes($value);

            if (str_starts_with($attribute, 'bind:')) {
                $attribute = Str::after($attribute, 'bind:');

                $this->boundAttributes[$attribute] = true;
            } else {
                $value = "'" . $this->compileAttributeEchos($value) . "'";
            }

            if (str_starts_with($attribute, '::')) {
                $attribute = substr($attribute, 1);
            }

            return [$attribute => $value];
        });
    }

    /**
     * Parses a short attribute syntax like :$foo into a fully-qualified syntax like :foo="$foo".
     *
     * @param  string  $value
     * @return string
     */
    protected function parseShortAttributeSyntax(string $value): string
    {
        $pattern = "/\s\:\\\$(\w+)/x";

        return preg_replace_callback($pattern, function (array $matches) {
            return " :{$matches[1]}=\"\${$matches[1]}\"";
        }, $value);
    }

    /**
     * Parse the attribute bag in a given attribute string into its fully-qualified syntax.
     *
     * @param  string  $attributeString
     * @return string
     */
    protected function parseAttributeBag(string $attributeString): string
    {
        $pattern = "/
            (?:^|\s+)                                        # start of the string or whitespace between attributes
            \{\{\s*(\\\$attributes(?:[^}]+?(?<!\s))?)\s*\}\} # exact match of attributes variable being echoed
        /x";

        return preg_replace($pattern, ' :attributes="$1"', $attributeString);
    }

    /**
     * Parse @class statements in a given attribute string into their fully-qualified syntax.
     *
     * @param  string  $attributeString
     * @return string
     */
    protected function parseComponentTagClassStatements(string $attributeString): string
    {
        return preg_replace_callback(
            '/@(class)(\( ( (?>[^()]+) | (?2) )* \))/x',
            function ($match) {
                if ($match[1] === 'class') {
                    $match[2] = str_replace('"', "'", $match[2]);

                    return ":class=\"\Diana\Support\Helpers\Arr::toCssClasses{$match[2]}\"";
                }

                return $match[0];
            },
            $attributeString
        );
    }

    /**
     * Parse @style statements in a given attribute string into their fully-qualified syntax.
     *
     * @param  string  $attributeString
     * @return string
     */
    protected function parseComponentTagStyleStatements(string $attributeString): string
    {
        return preg_replace_callback(
            '/@(style)(\( ( (?>[^()]+) | (?2) )* \))/x',
            function ($match) {
                if ($match[1] === 'style') {
                    $match[2] = str_replace('"', "'", $match[2]);

                    return ":style=\"\Diana\Support\Helpers\Arr::toCssStyles{$match[2]}\"";
                }

                return $match[0];
            },
            $attributeString
        );
    }

    /**
     * Parse the "bind" attributes in a given attribute string into their fully-qualified syntax.
     *
     * @param  string  $attributeString
     * @return string
     */
    protected function parseBindAttributes(string $attributeString): string
    {
        $pattern = "/
            (?:^|\s+)     # start of the string or whitespace between attributes
            :(?!:)        # attribute needs to start with a single colon
            ([\w\-:.@]+)  # match the actual attribute name
            =             # only match attributes that have a value
        /xm";

        return preg_replace($pattern, ' bind:$1=', $attributeString);
    }

    /**
     * Compile any Blade echo statements that are present in the attribute string.
     *
     * These echo statements need to be converted to string concatenation statements.
     *
     * @param  string  $attributeString
     * @return string
     */
    protected function compileAttributeEchos(string $attributeString): string
    {
        $value = $this->blade->compileEchos($attributeString);

        $value = $this->escapeSingleQuotesOutsideOfPhpBlocks($value);

        $value = str_replace('<?php echo ', '\'.', $value);
        $value = str_replace('; ?>', '.\'', $value);

        return $value;
    }

    /**
     * Escape the single quotes in the given string that are outside of PHP blocks.
     *
     * @param  string  $value
     * @return string
     */
    protected function escapeSingleQuotesOutsideOfPhpBlocks(string $value): string
    {
        return implode('', array_map(function ($token) {
            if (!is_array($token)) {
                return $token;
            }

            return $token[0] === T_INLINE_HTML
                ? str_replace("'", "\\'", $token[1])
                : $token[1];
        }, token_get_all($value)));
    }

    /**
     * Convert an array of attributes to a string.
     *
     * @param  array  $attributes
     * @param bool $escapeBound
     * @return string
     */
    protected function attributesToString(array $attributes, bool $escapeBound = true): string
    {
        return implode(',', Arr::map($attributes, function (string $value, string $attribute) use ($escapeBound) {
            return $escapeBound && isset ($this->boundAttributes[$attribute]) && $value !== 'true' && !is_numeric($value)
                ? "'{$attribute}' => \Diana\Rendering\Compiler::sanitizeComponentAttribute({$value})"
                : "'{$attribute}' => {$value}";
        }));
    }

    /**
     * Strip any quotes from the given string.
     *
     * @param  string  $value
     * @return string
     */
    public function stripQuotes(string $value): string
    {
        return Str::startsWith($value, ['"', '\''])
            ? substr($value, 1, -1)
            : $value;
    }
}
