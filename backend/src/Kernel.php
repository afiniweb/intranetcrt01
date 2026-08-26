<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as KernelBase;

final class Kernel extends KernelBase
{
    use MicroKernelTrait;
}

