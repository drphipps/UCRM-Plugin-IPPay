<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class UcrmKernelTestCase extends KernelTestCase
{
    protected static function getKernelClass()
    {
        return \AppKernel::class;
    }
}
