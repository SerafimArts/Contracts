<?php

/**
 * This file is part of Contracts package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Serafim\Contracts\Compiler;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeAbstract;
use PhpParser\NodeTraverser;
use PhpParser\Parser as ParserInterface;
use Serafim\Contracts\Attribute\Ensure;
use Serafim\Contracts\Attribute\Invariant;
use Serafim\Contracts\Attribute\Verify;
use Serafim\Contracts\Compiler\Visitor\ConstReplaceVisitor;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\EnsureStatement;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\InvariantStatement;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\Statement;
use Serafim\Contracts\Compiler\Visitor\ContractsApplicatorVisitor\VerifyStatement;
use Serafim\Contracts\Exception\SpecificationException;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Serafim\Contracts
 */
final class ContractsParser
{
    /**
     * @var non-empty-string
     */
    private const ERROR_EMPTY_EXPRESSION = '%s contract expression cannot be empty';

    /**
     * @var non-empty-string
     */
    private const ERROR_MULTIPLE_EXPRESSIONS = 'Using more than 1 expression '
    . 'in the %s contract definition is not allowed';

    /**
     * @var non-empty-string
     */
    private const ERROR_NOT_EXPRESSION = '%s contract must contain a valid PHP '
    . 'expression, but non-expression %s code "%s" is specified';

    /**
     * @param ParserInterface $parser
     */
    public function __construct(
        private readonly ParserInterface $parser,
    ) {
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @return list<InvariantStatement>
     */
    public function invariant(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Invariant::class, InvariantStatement::class);
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @param class-string $attr
     * @param class-string $stmt
     * @return list<Statement>
     */
    private function statements(string $file, Attribute $node, string $attr, string $stmt): iterable
    {
        if (\count($node->args) < 1) {
            throw SpecificationException::invalidExpressionType($attr, $file, $node->getLine());
        }

        yield $this->extractContractExpression($file, $node->args[0], $node->args[1] ?? null, $attr, $stmt);
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Node\Arg $expressionArgument
     * @param Node\Arg|null $reasonArgument
     * @param class-string $attr
     * @param class-string $stmt
     * @return Statement
     */
    private function extractContractExpression(
        string $file,
        Node\Arg $expressionArgument,
        ?Node\Arg $reasonArgument,
        string $attr,
        string $stmt
    ): Statement {
        $exprValue = $expressionArgument->value;
        if (!$exprValue instanceof Node\Scalar\String_) {
            throw SpecificationException::invalidExpressionType($attr, $file, $exprValue->getStartLine());
        }

        $reasonValue = $reasonArgument?->value;
        if ($reasonValue !== null && !$reasonValue instanceof Node\Scalar\String_) {
            throw SpecificationException::invalidReasonType($attr, $file, $exprValue->getStartLine());
        }

        $expression = $this->parse($file, $exprValue->value, $attr, $expressionArgument);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ConstReplaceVisitor($file, $exprValue->getStartLine()));
        $traverser->traverse([$expression]);

        return new $stmt($expression, $exprValue->value, $reasonValue?->value, $file, $expressionArgument->getLine());
    }

    /**
     * @psalm-taint-sink file $file
     * @psalm-taint-sink eval $expression
     * @param non-empty-string $file
     * @param non-empty-string $expression
     * @param class-string $type
     * @param NodeAbstract $node
     * @return Expr
     */
    private function parse(string $file, string $expression, string $type, NodeAbstract $node): Expr
    {
        try {
            /** @var list<Expression> $expressions */
            $expressions = $this->parser->parse("<?php $expression;");

            if (\count($expressions) === 0) {
                throw new \LogicException(\sprintf(self::ERROR_EMPTY_EXPRESSION, $type));
            }

            if (\count($expressions) > 1) {
                throw new \LogicException(\sprintf(self::ERROR_MULTIPLE_EXPRESSIONS, $type));
            }

            if ($expressions[0] instanceof Expression
                && $expressions[0]->expr instanceof Expr) {
                return $expressions[0]->expr;
            }

            $message = \sprintf(self::ERROR_NOT_EXPRESSION, $type, $this->typeOfNode($expressions[0]), $expression);
            throw new \LogicException($message);
        } catch (Error $e) {
            $line = $node->getLine() + $e->getStartLine() - 1;
            throw SpecificationException::create($e->getRawMessage(), $file, $line);
        } catch (\Throwable $e) {
            throw SpecificationException::create($e->getMessage(), $file, $node->getLine());
        }
    }

    /**
     * @param Node $node
     * @return non-empty-string
     */
    private function typeOfNode(Node $node): string
    {
        $segments = \explode('\\', $node::class);
        $segment = \end($segments);
        $segment = @\preg_replace('/[A-Z]+/u', '-$0', $segment) ?: $segment;

        return \strtolower(\trim($segment, '_-'));
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @return list<EnsureStatement>
     */
    public function ensure(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Ensure::class, EnsureStatement::class);
    }

    /**
     * @psalm-taint-sink file $file
     * @param non-empty-string $file
     * @param Attribute $node
     * @return list<VerifyStatement>
     */
    public function verify(string $file, Attribute $node): iterable
    {
        return $this->statements($file, $node, Verify::class, VerifyStatement::class);
    }
}
