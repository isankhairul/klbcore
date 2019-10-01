<?php namespace Klb\Core\Model;
/**
 * Class SoftDelete
 * @package Klb\Core\Model
 */
trait SoftDelete {
    /**
     *
     */
    public function initialize(){
        $this->addBehavior(new \Phalcon\Mvc\Model\Behavior\SoftDelete([
            'field' => 'deleted_at',
            'value' => date('Y-m-d H:i:s')
        ]));
    }
}
