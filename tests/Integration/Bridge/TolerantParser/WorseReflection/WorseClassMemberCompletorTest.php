<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use PHPUnit\Framework\TestCase;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\WorseReflection\Core\Logger\ArrayLogger;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Core\ChainCompletor;
use Phpactor\Completion\Core\Response;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseClassMemberCompletor;
use Phpactor\Completion\Tests\Integration\CompletorTestCase;
use Generator;
use Phpactor\Completion\Core\Completor;
use Phpactor\TestUtils\ExtractOffset;
use PhpBench\Benchmark\Metadata\Annotations\Subject;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;

class WorseClassMemberCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();

        return new WorseClassMemberCompletor($reflector, $this->formatter());
    }

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        $this->assertComplete($source, $expected);
    }

    public function provideComplete(): Generator
    {
        yield 'Public property' => [
            <<<'EOT'
<?php

class Foobar
{
    public $foo;
}

$foobar = new Foobar();
$foobar-><>

EOT
        , [
            [
                'type' => Suggestion::TYPE_PROPERTY,
                'name' => 'foo',
                'short_description' => 'pub $foo',
            ]
        ]
    ];

        yield 'Private property' => [
            <<<'EOT'
<?php

class Foobar
{
    private $foo;
}

$foobar = new Foobar();
$foobar-><>

EOT
        ,
            [ ]
        ];

        yield 'Public property access' => [
            <<<'EOT'
<?php

class Barar
{
    public $bar;
}

class Foobar
{
    /**
     * @var Barar
     */
    public $foo;
}

$foobar = new Foobar();
$foobar->foo-><>

EOT
        , [
            [
                'type' => Suggestion::TYPE_PROPERTY,
                'name' => 'bar',
                'short_description' => 'pub $bar',
            ]
        ]
    ];

        yield 'Public method with parameters' => [
            <<<'EOT'
<?php

class Foobar
{
    public function foo(string $zzzbar = 'bar', $def): Barbar
    {
    }
}

$foobar = new Foobar();
$foobar-><>

EOT
        , [
            [
                'type' => Suggestion::TYPE_METHOD,
                'name' => 'foo',
                'short_description' => 'pub foo(string $zzzbar = \'bar\', $def): Barbar',
            ]
        ]
    ];

        yield 'Public method multiple return types' => [
            <<<'EOT'
<?php

class Foobar
{
    /**
     * @return Foobar|Barbar
     */
    public function foo()
    {
    }
}

$foobar = new Foobar();
$foobar-><>

EOT
        , [
            [
                'type' => Suggestion::TYPE_METHOD,
                'name' => 'foo',
                'short_description' => 'pub foo(): Foobar|Barbar',
            ]
        ]
    ];

        yield 'Private method' => [
            <<<'EOT'
<?php

class Foobar
{
    private function foo(): Barbar
    {
    }
}

$foobar = new Foobar();
$foobar-><>

EOT
        , [
        ]
    ];

        yield 'Static property' => [
            <<<'EOT'
<?php

class Foobar
{
    public static $foo;
}

$foobar = new Foobar();
$foobar::<>

EOT
        , [
            [
                'type' => Suggestion::TYPE_PROPERTY,
                'name' => 'foo',
                'short_description' => 'pub static $foo',
            ]
        ]
    ];

        yield 'Static property with previous arrow accessor' => [
            <<<'EOT'
<?php

class Foobar
{
    public static $foo;

    /**
     * @var Foobar
     */
    public $me;
}

$foobar = new Foobar();
$foobar->me::<>

EOT
        , [
            [
                'type' => Suggestion::TYPE_PROPERTY,
                'name' => 'foo',
                'short_description' => 'pub static $foo',
            ],
            [
                'type' => Suggestion::TYPE_PROPERTY,
                'name' => 'me',
                'short_description' => 'pub $me: Foobar',
            ]
        ]
    ];

        yield 'Partially completed' => [
            <<<'EOT'
<?php

class Foobar
{
    public static $foobar;
    public static $barfoo;
}

$foobar = new Foobar();
$foobar::f<>

EOT
        , [
            [
                'type' => Suggestion::TYPE_METHOD,
                'name' => 'foobar',
                'short_description' => 'pub static $foobar',
            ]
        ]
    ];

        yield 'Partially completed 2' => [
            <<<'EOT'
<?php

class Foobar
{
    public function aaa()
    {
        $this->bb<>
    }

    public function bbb() {}
    public function ccc() {}
}

EOT
        , [
            [
                'type' => Suggestion::TYPE_METHOD,
                'name' => 'bbb',
                'short_description' => 'pub bbb()',
            ]
        ]
    ];
        yield 'Partially completed' => [
            <<<'EOT'
<?php

class Foobar
{
    const FOOBAR = 'foobar';
    const BARFOO = 'barfoo';
}

$foobar = new Foobar();
$foobar::<>

EOT
        , [
            [
                'type' => Suggestion::TYPE_CONSTANT,
                'name' => 'FOOBAR',
                'short_description' => 'const FOOBAR',
            ],
            [
                'type' => Suggestion::TYPE_CONSTANT,
                'name' => 'BARFOO',
                'short_description' => 'const BARFOO',
            ],
        ],
    ];

        yield 'Accessor on new line' => [
            <<<'EOT'
<?php

class Foobar
{
    public $foobar;
}

$foobar = new Foobar();
$foobar
    ->    <>

EOT
        , [
            [
                'type' => Suggestion::TYPE_PROPERTY,
                'name' => 'foobar',
                'short_description' => 'pub $foobar',
            ],
        ],
    ];

        yield 'Completion on collection' => [
            <<<'EOT'
<?php

class Collection
{
    public function heyho() {}
}

class Foobar
{
    /**
     * @return Collection<Foobar>
     */
    public function collection() {}
}

$foobar = new Foobar();
$collection = $foobar->collection();
$collection-><>

EOT
        , [
            [
                'type' => Suggestion::TYPE_METHOD,
                'name' => 'heyho',
                'short_description' => 'pub heyho()',
            ],
        ],
    ];

        yield 'Completion on assignment' => [
            <<<'EOT'
<?php

class Foobar
{
    public function method1() {}
}

$foobar = new Foobar();
$foobar = $foobar->meth<>

EOT
        , [
            [
                'type' => Suggestion::TYPE_METHOD,
                'name' => 'method1',
                'short_description' => 'pub method1()',
            ],
        ],
    ];

        yield 'member is variable name' => [
            <<<'EOT'
<?php

class BarBar
{
    public $barbar;
}

class Foobar
{
    /**
     * @var BarBar
     */
    public $foobar;
}

$barbar = 'foobar';
$foobar = new Foobar();
$foobar->$bar<>;
EOT
        , [
        ]];
    }

    /**
     * @dataProvider provideErrors
     */
    public function testErrors(string $source, array $expected)
    {
        $this->assertCompletionErrors($source, $expected);
    }

    public function provideErrors()
    {
        yield [
            <<<'EOT'
<?php

$asd = 'asd';
$asd-><>
EOT
        ,[
                'Cannot complete members on scalar value (string)',
            ]
        ];

        yield [
            <<<'EOT'
<?php

$asd-><>
EOT
        ,
            [
                'Variable "asd" is undefined',
            ]
        ];

        yield [
            <<<'EOT'
<?php

$asd = new BooBar();
$asd-><>
EOT
        ,
            [
                'Could not find class "BooBar"',
            ]
        ];

        yield 'non existing property' => [
            <<<'EOT'
<?php

class Foobar
{
    public $foobar;
}

$foobar = new Foobar();
$foobar->barbar-><>;
EOT
        ,
            [
                'Class "Foobar" has no properties named "barbar"',
            ]
        ];

    }

    /**
     * @dataProvider provideCouldNotComplete
     */
    public function testCouldNotComplete(string $source)
    {
        $this->assertCouldNotComplete($source);
    }

    public function provideCouldNotComplete(): Generator
    {
        yield 'non member access' => [ '<?php $hello<>' ];
        yield 'variable with previous accessor' => [ '<?php $foobar->hello; $hello<>' ];
        yield 'variable with previous accessor' => [ '<?php $foobar->hello; $hello<>' ];
        yield 'statement with previous member access' => [ '<?php if ($foobar && $this->foobar) { echo<>' ];
        yield 'variable with previous static member access' => [ '<?php Hello::hello(); $foo<>' ];
    }
}
