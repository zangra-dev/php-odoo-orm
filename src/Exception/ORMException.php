<?php

namespace Zangra\Component\Odoo\ORM\Exception;

use Zangra\Component\Odoo\ORM\Internal\ReflectorAwareTrait;

class ORMException extends RuntimeException
{
    use ReflectorAwareTrait;
}
