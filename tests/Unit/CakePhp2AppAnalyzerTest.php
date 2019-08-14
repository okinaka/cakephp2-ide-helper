<?php
declare(strict_types=1);

namespace Test\Unit\CakePhp2IdeHelper;

use CakePhp2IdeHelper\CakePhp2Analyzer\CakePhp2AppAnalyzer;
use CakePhp2IdeHelper\CakePhp2Analyzer\Readers\BehaviorReader;
use CakePhp2IdeHelper\CakePhp2Analyzer\Readers\FixtureReader;
use CakePhp2IdeHelper\CakePhp2Analyzer\Readers\ModelReader;
use CakePhp2IdeHelper\CakePhp2Analyzer\StructuralElements\CakePhp2App;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use Tests\TestCase;

class CakePhp2AppAnalyzerTest extends TestCase
{
    public function testGetModelReaders()
    {
        $analyzer = new CakePhp2AppAnalyzer(new CakePhp2App($this->fixtureAppPath()));

        $modelReaders = $analyzer->getModelReaders();

        $this->assertCount(5, $modelReaders);
        $this->assertSame(['AppModel', 'SomeModel1', 'SomeModel2', 'SomeModel3', 'SomeModel4'], array_map(function (ModelReader $reader) {
            return $reader->getModelName();
        }, $modelReaders));
    }

    public function testGetBehaviorReaders()
    {
        $analyzer = new CakePhp2AppAnalyzer(new CakePhp2App($this->fixtureAppPath()));

        $behaviorReaders = $analyzer->getBehaviorReaders();

        $this->assertCount(3, $behaviorReaders);
        $this->assertSame(['SomeBehavior1Behavior', 'SomeBehavior2Behavior', 'SomeBehavior3Behavior'], array_map(function (BehaviorReader $reader) {
            return $reader->getBehaviorName();
        }, $behaviorReaders));
    }

    public function testGetFixtureReaders()
    {
        $analyzer = new CakePhp2AppAnalyzer(new CakePhp2App($this->fixtureAppPath()));

        $fixtureReaders = $analyzer->getFixtureReaders();

        $this->assertCount(2, $fixtureReaders);
        $this->assertSame([['SomeModel1', 'SomeModel1_Extends'], ['SomeModel2', 'SomeModel2_extends']], array_map(function (FixtureReader $reader) {
            return $reader->getFabricateDefineNames();
        }, $fixtureReaders));
    }

    public function testSearchUnloadMethod()
    {
        $app = new CakePhp2App($this->fixtureAppPath());
        $app->addModelDir($this->fixtureAppPath('AdditionalModel'));
        $analyzer = new CakePhp2AppAnalyzer($app);

        $methodCalls = $analyzer->searchUnloadMethod();

        $this->assertCount(1, $methodCalls);
        $this->assertSame(['SomeBehavior2'], array_map(function (MethodCall $methodCall) {
            if (isset($methodCall->args[0])) {
                if ($methodCall->args[0]->value instanceof String_) {
                    $behaviorName = $methodCall->args[0]->value->value;
                    return $behaviorName;
                }
            }
            return '';
        }, $methodCalls));
    }
}
