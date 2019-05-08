<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

declare(strict_types=1);

namespace AppBundle\Exception;

class TemplateRenderException extends \RuntimeException
{
    public function getMessageForView(): string
    {
        $twigError = $this->getPrevious();
        if ($twigError instanceof \Twig_Error) {
            return implode(
                PHP_EOL,
                [
                    sprintf('Message: %s', $twigError->getRawMessage()),
                    sprintf('Line: %s', $twigError->getTemplateLine()),
                ]
            );
        }

        return $this->getMessage();
    }
}
