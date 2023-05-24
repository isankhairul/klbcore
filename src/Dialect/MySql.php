<?php namespace KlbV2\Core\Dialect;
use KlbV2\Core\Dialect\Contract\DialectFunctionContract;

/**
 * Class MySql
 *
 * @package KlbV2\Core\Dialect
 */
class MySql extends \Phalcon\Db\Dialect\Mysql
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->registerCustomFunctions();
    }

    public function registerCustomFunctions()
    {
        $customFunctions = [
            'GROUP_CONCAT'  => 'GroupConcat',
            'MATCH_AGAINST' => 'MatchAgainst',
        ];
        foreach ( $customFunctions as $key => $value ) {
            $className = 'KlbV2\Core\\Dialect\\Extensions\\' . $value;
            /** @var DialectFunctionContract $object */
            $object = new $className;
            $this->registerCustomFunction( $key, $object->getFunction() );
        }
    }
}
