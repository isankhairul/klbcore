<?php namespace Klb\Core\Dialect\Contract;
use Closure;

/**
 * Interface DialectFunctionContract
 *
 * @package Klb\Core\Dialect\Contract
 */
interface DialectFunctionContract
{
    /**
     * @return Closure
     */
    public function getFunction();
}
