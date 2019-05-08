<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Database;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class RoundFunction extends FunctionNode
{
    /**
     * @var Node
     */
    private $number;

    /**
     * @var Node
     */
    private $precision;

    /**
     * @return string
     *
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            'round(CAST(%s AS numeric), %s)',
            $this->number->dispatch($sqlWalker),
            $this->precision->dispatch($sqlWalker)
        );
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->number = $parser->ArithmeticExpression();
        $parser->match(Lexer::T_COMMA);
        $this->precision = $parser->ArithmeticExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
