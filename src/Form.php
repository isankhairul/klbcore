<?php namespace Klb\Core;

use Phalcon\Forms\Form as BaseForm;
use Phalcon\Forms\Element;
use Phalcon\Validation\Validator\Identical;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Date as DateValidator;
use function strpos;

/**
 * Class Form
 * @package App
 */
class Form extends BaseForm {
    /**
     * @var array
     */
    protected $_collections = [];
    protected $_elementGroupName = [];
    protected $_lastKey = null;
    // Holds token key/name
    protected $_csrf;
    /**
     * @param null $entity
     * @param array|null $options
     */
    public function initialize($entity = null, array $options = null){
        $defaultValues = [];
        if(isset($options['defaultValues'])){
            $defaultValues = $options['defaultValues'];
        }
        foreach ( $this->getElements() as $el ){
            if(!$el->getLabel() && ($label = $el->getAttribute('label'))){
                $el->setLabel($label);
            }
            if(!$el->getLabel() && ($label = $el->getAttribute('placeholder'))){
                $el->setLabel($label);
            }
            if(isset($defaultValues[$el->getName()])){
                $el->setDefault($defaultValues[$el->getName()]);
            }
            $el->setAttribute('data-form-type', strtolower((new \ReflectionClass($el))->getShortName()));
            if($el->getAttribute('required')){
                $el->addValidator(new PresenceOf([
                    'message' => 'The ' . strtolower($el->getLabel()) . ' is required'
                ]));
                $el->setAttribute('data-parsley-trigger', 'change');
            }
            //multiple
            if($el->getAttribute('multiple') && !$el->getAttribute('size')){
                $el->setAttribute('size', '10');
            }
            $class = $el->getAttribute('class');
            if(strpos($class, 'input-') === false){
                $class .= ' input-large';
            }
            if($el->getAttribute('maxlength')){
                $class .= ' maxlength-handler';
            }
            $el->setAttribute('class', trim($class));
            if($el->getAttribute('date') === true) {
                if ($el->getAttribute('required')) {
                    $el->addValidator(new DateValidator([
                        'format'  => 'm/d/Y',
                        'message' => 'The ' . strtolower($el->getLabel()) . ' date is invalid'
                    ]));
                }
            }
        }

        // CSRF protection
        $csrf = new Element\Hidden($this->getCsrfName(), [
            'data-form-type' => 'hidden'
        ]);
        $csrf->setDefault($this->security->getToken())
            ->addValidator(new Identical([
                'accepted'   => $this->security->checkToken(),
                'message' => 'CSRF forgery detected!'
            ]));
        $this->add($csrf);
    }

    // Generates CSRF token key
    public function getCsrfName()
    {
        if (empty($this->_csrf)) {
            $this->_csrf = $this->security->getTokenKey();
        }

        return $this->_csrf;
    }

    /**
     * @param \Phalcon\Forms\ElementInterface[] $elements
     * @param $name
     * @return $this
     */
    protected function addCollectionElements(array $elements, $name){
        foreach ( $elements as $objElement ){
            $this->addCollectionElement($objElement, $name);
        }
        return $this;
    }

    /**
     * @param $name
     * @param $title
     * @return $this
     */
    protected function addElementGroupName($name, $title){
        $this->_elementGroupName[$name] = $title;
        return $this;
    }

    /**
     * @return array
     */
    public function getElementGroupName()
    {
        return $this->_elementGroupName;
    }

    /**
     * @param \Phalcon\Forms\ElementInterface $objElement
     * @param string $name
     * @return $this
     */
    protected function addCollectionElement($objElement, $name = 'general'){
        if(!isset($this->_collections[$name])){
            $this->_collections[$name] = [];
        }
        $this->_collections[$name][] = $objElement->getName();
        if(!isset($this->_elementGroupName[$name])){
            $this->addElementGroupName($name, ucfirst($name));
        }
        $this->add($objElement);
        $this->_lastKey = $name;
        return $this;
    }

    /**
     * @param null $name
     * @return \Phalcon\Forms\ElementInterface[]
     */
    public function getCollectionElements($name = null){
        if(null === $name){
            return $this->getElements();
        }
        $elements = array_key_exists($name, $this->_collections) ? $this->_collections[$name] : [];
        $collections = [];
        foreach ( $elements as $element) {
            $collections[] = $this->get($element);
        }
        return $collections;
    }

    /**
     * @param $type
     * @return array
     */
    protected function getSelectOptions($type){
        switch ($type){
            case 'boolean':
            case 'bool':
                return [
                    ''  => '- Select -',
                    '1' => 'Yes',
                    '0' => 'No'
                ];
            case 'active':
            case 'view':
            case 'enabled':
                return [
                    ''  => '- Select -',
                    '1' => 'Enabled',
                    '0' => 'Disabled'
                ];
            case 'publish':
                return [
                    ''  => '- Select -',
                    '1' => 'Publish',
                    '0' => 'Not Publish'
                ];
        }
        return [];
    }
//
//    public function fromCollection(array $collections){
//        foreach ( $collections as $name => $collection ){
//
//            if(!is_array($collection[0])){
//                $class = ucfirst($collection[0][0]);
//                if(isset($collection[0][1]))
//                $objElement = new $class();
//            }
//        }
//    }
}
