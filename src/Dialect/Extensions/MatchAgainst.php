<?php namespace KlbV2\Core\Dialect\Extensions;

use KlbV2\Core\Dialect\Contract\DialectFunctionContract;
use Phalcon\Db\Dialect;

class MatchAgainst implements DialectFunctionContract
{
    /**
     * @inheritDoc
     */
    public function getFunction()
    {
        return function ( Dialect $dialect, $expression ) {
            $arguments = $expression['arguments'];
            return sprintf(
                " MATCH (%s) AGAINST (%)",
                $dialect->getSqlExpression( $arguments[0] ),
                $dialect->getSqlExpression( $arguments[1] )
            );
        };
    }
}
