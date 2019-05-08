<?php

namespace Tests\Functional;

use AppBundle\Util\Helpers;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class UcrmWebTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client = null;

    /**
     * @var EntityManager
     */
    protected $em = null;

    protected function assertContainsFlashMessage(string $haystack, string $type, string $message = null)
    {
        $env = $this->client->getContainer()->get('twig');

        if ($message) {
            $needle = sprintf(
                ('toastr["%s"]("%s");'),
                $type,
                \twig_escape_filter($env, Helpers::sanitizeFlashMessage($message), 'js', 'UTF-8')
            );
        } else {
            $needle = sprintf(('toastr["%s"]'), $type);
        }

        self::assertContains($needle, $haystack);
    }

    protected function assertNotContainsFlashMessage(string $haystack, string $type, string $message = null)
    {
        $env = $this->client->getContainer()->get('twig');

        if ($message) {
            $needle = sprintf(
                ('toastr["%s"]("%s");'),
                $type,
                \twig_escape_filter($env, Helpers::sanitizeFlashMessage($message), 'js', 'UTF-8')
            );
        } else {
            $needle = sprintf(('toastr["%s"]'), $type);
        }

        self::assertNotContains($needle, $haystack);
    }

    protected static function getKernelClass()
    {
        return \AppKernel::class;
    }
}
