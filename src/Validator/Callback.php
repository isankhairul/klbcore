<?php namespace Klb\Core\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;
use Phalcon\Validation\ValidatorInterface;

/**
 * Class Callback
 *
 * @package Klb\Core\Validator
 */
class Callback extends Validator
{
    /**
     * @param Validation $validation
     * @param string              $field
     *
     * @return bool
     * @throws Exception
     */
    public function validate( Validation $validation, $field )
    {
        $message = null;
        $label = null;
        $replacePairs = null;
        $code = null;
        $callback = $this->getOption( 'callback' );
        if ( is_callable( $callback ) ) {
            $data = $validation->getEntity();
            if ( empty( $data ) ) {
                $data = $validation->getData();
            }
            $returnedValue = call_user_func( $callback, $data );
            if ( is_bool( $returnedValue ) ) {
                if ( !$returnedValue ) {
                    $label = $this->getOption( 'label' );
                    if ( is_array( $label ) ) {
                        $label = $label[$field];
                    }
                    if ( empty( $label ) ) {
                        $label = $validation->getLabel( $field );
                    }
                    $message = $this->getOption( 'label' );
                    if ( is_array( $message ) ) {
                        $message = $message[$field];
                    }
                    if ( empty( $message ) ) {
                        $message = $validation->getDefaultMessage( "Callback" );
                    }
                    $code = $this->getOption( 'code' );
                    if ( is_array( $code ) ) {
                        $code = $code[$field];
                    }

                    $replacePairs = [ ":field" => $label ];

                    $validation->appendMessage(
                        new Message(
                            strtr( $message, $replacePairs ),
                            $field,
                            "Callback",
                            $code
                        )
                    );
                    return false;
                }
                return true;
            } else if ( is_object( $returnedValue ) && $returnedValue instanceof ValidatorInterface ) {
                return $returnedValue->validate( $validation, $field );
            }
            throw new Exception( 'Callback must return boolean or Phalcon\\Validation\\Validator object' );
        }
        return true;
    }
}
