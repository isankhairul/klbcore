<?php namespace KlbV2\Core\Dialect\Contract;
use Closure;

/**
 * Interface DialectFunctionContract
 *
 * @package KlbV2\Core\Dialect\Contract
 */
interface DialectFunctionContract
{
    /**
     * @return Closure
     */
    public function getFunction();
}
