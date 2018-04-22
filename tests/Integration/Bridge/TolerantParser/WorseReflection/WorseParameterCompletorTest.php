<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseParameterCompletor;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\WorseReflection\ReflectorBuilder;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection\WorseParameterCompletorTest;

class WorseParameterCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseParameterCompletor($reflector, $this->formatter());
    }

    public function provideComplete(): Generator
    {
        yield 'parameter' => [
            <<<'EOT'
<?php 
class Foobar { public function barbar(string $foo) {} }

$foobar = new Foobar();
$foobar->barbar($<>
EOT
        , [
            [
                'type' => 'v',
                'name' => 'foobar',
                'info' => 'Foobar # pub barbar(string $foo)',
            ]
        ]
        ];
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
