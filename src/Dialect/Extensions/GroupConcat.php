<?php namespace Klb\Core\Dialect\Extensions;

use Phalcon\Db\Dialect;
use Klb\Core\Dialect\Contract\DialectFunctionContract;

class GroupConcat implements DialectFunctionContract
{
    /**
     * @inheritDoc
     */
    public function getFunction()
    {
        return function (Dialect $dialect, $expression) {
            $arguments = $expression['arguments'];
            if (!empty($arguments[2])) {
                return sprintf(
                    " GROUP_CONCAT(%s ORDER BY %s SEPARATOR %s)",
                    $dialect->getSqlExpression($arguments[0]),
                    $dialect->getSqlExpression($arguments[1]),
                    $dialect->getSqlExpression($arguments[2])
                );
            }

            if (!empty($arguments[1])) {
                return sprintf(
                    " GROUP_CONCAT(%s SEPARATOR %s)",
                    $dialect->getSqlExpression($arguments[0]),
                    $dialect->getSqlExpression($arguments[1])
                );
            }

            return sprintf(
                " GROUP_CONCAT(%s)",
                $dialect->getSqlExpression($arguments[0])
            );
        };
    }
}
