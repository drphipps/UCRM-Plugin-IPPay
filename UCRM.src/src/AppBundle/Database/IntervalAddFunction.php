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

class IntervalAddFunction extends FunctionNode
{
    /**
     * @var Node
     */
    private $dateColumn;

    /**
     * @var Node
     */
    private $unit;

    /**
     * @var Node
     */
    private $durationColumn;

    /**
     * @return string
     *
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            '(%s + interval \'1 %s\' * %s)',
            $this->dateColumn->dispatch($sqlWalker),
            trim($this->unit->dispatch($sqlWalker), '\''),
            $this->durationColumn->dispatch($sqlWalker)
        );
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->dateColumn = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->unit = $parser->StringExpression();
        $parser->match(Lexer::T_COMMA);
        $this->durationColumn = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
