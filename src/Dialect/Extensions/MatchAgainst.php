<?php namespace Klb\Core\Dialect\Extensions;

use Phalcon\Db\Dialect;
use Klb\Core\Dialect\Contract\DialectFunctionContract;

class MatchAgainst implements DialectFunctionContract
{
    /**
     * @inheritDoc
     */
    public function getFunction()
    {
        return function (Dialect $dialect, $expression) {
            $arguments = $expression['arguments'];
            return sprintf(
                " MATCH (%s) AGAINST (%)",
                $dialect->getSqlExpression($arguments[0]),
                $dialect->getSqlExpression($arguments[1])
            );
        };
    }
}
