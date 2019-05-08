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

class StringAggMultiDistinctFunction extends FunctionNode
{
    /**
     * @var Node
     */
    private $field1;

    /**
     * @var Node
     */
    private $field2;

    /**
     * @var Node
     */
    private $separator;

    /**
     * @var Node
     */
    private $delimiter;

    /**
     * @return string
     *
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            'string_agg(DISTINCT NULLIF(CONCAT(%s::character varying, %s, %s::character varying), %s), %s ORDER BY NULLIF(CONCAT(%s::character varying, %s, %s::character varying), %s))',
            $this->field1->dispatch($sqlWalker),
            $this->separator->dispatch($sqlWalker),
            $this->field2->dispatch($sqlWalker),
            $this->separator->dispatch($sqlWalker),
            $this->delimiter->dispatch($sqlWalker),
            $this->field1->dispatch($sqlWalker),
            $this->separator->dispatch($sqlWalker),
            $this->field2->dispatch($sqlWalker),
            $this->separator->dispatch($sqlWalker)
        );
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->field1 = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->field2 = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->separator = $parser->StringExpression();
        $parser->match(Lexer::T_COMMA);
        $this->delimiter = $parser->StringExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
