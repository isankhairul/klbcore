<?php namespace Klb\Core\Dialect\Contract;
/**
 * Interface DialectFunctionContract
 * @package Klb\Core\Dialect\Contract
 */
interface DialectFunctionContract
{
    /**
     * @return \Closure
     */
    public function getFunction();
}
