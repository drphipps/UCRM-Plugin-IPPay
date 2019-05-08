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

class StringAggDistinctFunction extends FunctionNode
{
    /**
     * @var Node
     */
    private $field;

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
            'string_agg(DISTINCT %s::character varying, %s ORDER BY %s::character varying)',
            $this->field->dispatch($sqlWalker),
            $this->delimiter->dispatch($sqlWalker),
            $this->field->dispatch($sqlWalker)
        );
        // Pakket Communications - Quitman - Primary Tower - PK-TWR1-RTR-CORE
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->field = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->delimiter = $parser->StringExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
