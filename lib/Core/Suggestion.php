<?php

namespace Phpactor\Completion\Core;

use RuntimeException;

class Suggestion
{
    /**
     * Completion types based on the language server protocol:
     * https://github.com/Microsoft/language-server-protocol/blob/gh-pages/specification.md#completion-request-leftwards_arrow_with_hook
     */

    const TYPE_METHOD = 'method';
    const TYPE_FUNCTION = 'function';
    const TYPE_CONSTRUCTOR = 'constructor';
    const TYPE_FIELD = 'field';
    const TYPE_VARIABLE = 'variable';
    const TYPE_CLASS = 'class';
    const TYPE_INTERFACE = 'interface';
    const TYPE_MODULE = 'module';
    const TYPE_PROPERTY = 'property';
    const TYPE_UNIT = 'unit';
    const TYPE_VALUE = 'value';
    const TYPE_ENUM = 'enum';
    const TYPE_KEYWORD = 'keyword';
    const TYPE_SNIPPET = 'snippet';
    const TYPE_COLOR = 'color';
    const TYPE_FILE = 'file';
    const TYPE_REFERENCE = 'reference';
    const TYPE_CONSTANT = 'constant';

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $shortDescription;

    /**
     * @var string|null
     */
    private $classImport;

    private function __construct(
        string $name,
        string $type = null,
        string $shortDescription = null,
        string $classImport = null
    )
    {
        $this->type = $type;
        $this->name = $name;
        $this->shortDescription = $shortDescription;
        $this->classImport = $classImport;
    }

    public static function create(string $name)
    {
        return new self($name);
    }

    public static function createWithOptions(string $name, array $options): self
    {
        $defaults = [
            'short_description' => '',
            'type' => null,
            'class_import' => null,
        ];

        if ($diff = array_diff(array_keys($options), array_keys($defaults))) {
            throw new RuntimeException(sprintf(
                'Invalid options for suggestion: "%s" valid options: "%s"',
                implode('", "', $diff), implode('", "', array_keys($defaults))
            ));
        }

        $options = array_merge($defaults, $options);

        return new self(
            $name,
            $options['type'],
            $options['short_description'],
            $options['class_import']
        );
    }

    /**
     * @return string|null
     */
    public function type()
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function shortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * @return string|null
     */
    public function classImport()
    {
        return $this->classImport;
    }
}
