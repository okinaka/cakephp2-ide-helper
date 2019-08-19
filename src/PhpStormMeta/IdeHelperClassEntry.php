<?php
declare(strict_types=1);

namespace CakePhp2IdeHelper\PhpStormMeta;

use PhpParser\Builder\Class_;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;

class IdeHelperClassEntry
{
    private $className;
    private $classMethods = [];

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return ClassMethod[]
     */
    public function getMethods(): array
    {
        return $this->classMethods;
    }

    public function addMethod(ClassMethod $classMethod): void
    {
        if (count($classMethod->params) > 0) {
            $this->classMethods[] = clone $classMethod;
        }
    }

    public function createStmt(): Class_
    {
        $builderFactory = new BuilderFactory;
        $classStmt = $builderFactory->class($this->getClassName());

        foreach ($this->getMethods() as $classMethod) {
            // remove first argument
            $firstArg = array_shift($classMethod->params);

            $variable = $builderFactory->var('behavior');
            $methodCall = $builderFactory->methodCall($variable, $classMethod->name->toString(), []);
            $methodCall->args[] = $firstArg->var;
            foreach ($classMethod->params as $param) {
                $methodCall->args[] = $param->var;
            }
            $returnStmt = new Return_($methodCall);
            $returnStmt->setDocComment(new Doc("/**
            * @var \\{$this->getClassName()} \$behavior
            * @var \\Model \${$firstArg->var->name}
            */"));

            // remove method body
            $classMethod->stmts = [];

            $classMethod->stmts[] = $returnStmt;

            $classStmt->addStmt($classMethod);
        }

        return $classStmt;
    }

    /**
     * @return string[]
     */
    public function getSymbols(): array
    {
        $ret = [];
        foreach ($this->getMethods() as $classMethod) {
            $ret[] = "\\{$this->getClassName()}::{$classMethod->name->toString()}()";
        }

        return $ret;
    }
}
