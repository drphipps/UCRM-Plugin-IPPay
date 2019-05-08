<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Database;

use AppBundle\Entity\Client;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class ClientFullNameFunction extends FunctionNode
{
    /**
     * @var string
     */
    private $clientAlias;

    /**
     * @var string
     */
    private $userAlias;

    /**
     * @return string
     *
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return strtr(
            '
                CASE WHEN :clientAlias.client_type = :typeResidential THEN
                    concat(:userAlias.first_name, \' \', :userAlias.last_name)
                ELSE
                    :clientAlias.company_name
                END
            ',
            [
                ':clientAlias' => $sqlWalker->walkIdentificationVariable($this->clientAlias),
                ':userAlias' => $sqlWalker->walkIdentificationVariable($this->userAlias),
                ':typeResidential' => Client::TYPE_RESIDENTIAL,
            ]
        );
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->clientAlias = $parser->AliasIdentificationVariable();

        $parser->match(Lexer::T_COMMA);

        $this->userAlias = $parser->AliasIdentificationVariable();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
