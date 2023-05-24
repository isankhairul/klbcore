<?php namespace KlbV2\Core\Model;
/**
 * Class SoftDelete
 *
 * @package KlbV2\Core\Model
 */
trait SoftDelete
{
    /**
     *
     */
    public function initialize()
    {
        $this->addBehavior( new \Phalcon\Mvc\Model\Behavior\SoftDelete( [
            'field' => 'deleted_at',
            'value' => date( 'Y-m-d H:i:s' )
        ] ) );
    }
}
