<?php

namespace UsageFinder;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileManipulation;
use Psalm\IssueBuffer;
use Psalm\Issue\PluginIssue;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Union;
use UsageFinder\ClassMethodReference;
use UsageFinder\Issue\ClassMethodUsageFound;

final class FindClassMethodUsagesPlugin implements AfterMethodCallAnalysisInterface
{
    public static function afterMethodCallAnalysis(
        Expr $expr,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        Union &$return_type_candidate = null
    ) {
        if (!self::isMethodWeWant($declaring_method_id, $codebase)) {
            return;
        }

        $message = sprintf("Found reference to %s\n",
            self::getFindName()
        );

        if (IssueBuffer::accepts(
            new ClassMethodUsageFound(
                $message,
                new CodeLocation($statements_source, $expr->name)
            ),
            $statements_source->getSuppressedIssues()
        )) {
            // fall through
        }
    }

    private static function isMethodWeWant(string $declaring_method_id, Codebase $codebase) : bool
    {
        list($className, $methodName) = explode('::', $declaring_method_id);

        if (strtolower($declaring_method_id) === strtolower(self::getFindName())) {
            return true;
        }

        if (strtolower($methodName) === strtolower(self::getFindMethodName())
            && $codebase->classImplements($className, self::getFindClassName())) {
            return true;
        }

        return false;
    }

    private static function getFindName() : string
    {
        return getenv('USAGE_FINDER_NAME');
    }

    private static function getFindClassName() : string
    {
        return getenv('USAGE_FINDER_CLASS_NAME');
    }

    private static function getFindMethodName() : string
    {
        return getenv('USAGE_FINDER_METHOD_NAME');
    }
}
