<?php

namespace Phpactor\Completion\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Core\Suggestion;
use RuntimeException;

class SuggestionTest extends TestCase
{
    public function testThrowsExceptionWithInvalidOptions()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid options for suggestion: "foobar" valid options: "short_description", "type"');

        Suggestion::createWithOptions('foobar', ['foobar' => 'barfoo']);
    }

    public function testCanBeCreatedWithOptionsAndProvidesAccessors()
    {
        $suggestion = Suggestion::createWithOptions('hello', [
            'type' => 'c',
            'short_description' => 'Foobar',
            'class_import' => 'Namespace\\Foobar',
        ]);

        $this->assertEquals('c', $suggestion->type());
        $this->assertEquals('hello', $suggestion->name());
        $this->assertEquals('Foobar', $suggestion->shortDescription());
        $this->assertEquals('Namespace\\Foobar', $suggestion->classImport());
    }
}
