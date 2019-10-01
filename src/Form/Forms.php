<?php
namespace Klb\Core\Form;

class Forms
{
	public $inputs = [];
	public $cancel_action = '';

	/*
		set required texts
	 */
	private function _checkRequired($required)
	{
		if($required) {
			return [
				'required_span' => '<span class="required"> * </span>',
				'required_tag' => 'required="required"'
			];
		} else {
			return [
				'required_span' => '',
				'required_tag' => ''
			];
		}
	}

	/*
		Set basic form information
	 */
	public function setFormData($id, $class, $method, $action, $cancelAction, $multipart=false)
	{
		$this->cancel_action = $cancelAction;

		return [
			'íd' => $id,
			'class' => $class,
            'method' => $method,
            'action' => $action,
            'multipart' => $multipart
		];
	}

	/*
		Set input type : text
	 */
	public function addTextInput($id, $class, $name, $label, $value, $required = false)
	{
		$required = $this->_checkRequired($required);

		$this->inputs[] = [
            'id' => $id,
            'name' => $name,
            'type' => 'text',
            'class' => $class,
            'label' => $label,
            'value' => $value,
            'required_span' => $required['required_span'],
            'required' => $required['required_tag']
        ];
	}

	/*
		Set input type : text disabled
	 */
	public function addDisabledTextInput($id, $class, $name, $label, $value)
	{
		$required = $this->_checkRequired($required);

		$this->inputs[] = [
            'id' => $id,
            'name' => $name,
            'type' => 'textdisabled',
            'class' => $class,
            'label' => $label,
            'value' => $value
        ];
	}

	/*
		Set input type : daterange
	 */
	public function addDateInput($id, $class, $name, $label, $value, $required = false)
	{
		$required = $this->_checkRequired($required);

		$this->inputs[] = [
            'id' => $id,
            'name' => $name,
            'type' => 'daterange',
            'class' => $class,
            'label' => $label,
            'value' => $value,
            'required_span' => $required['required_span'],
            'required' => $required['required_tag']
        ];
	}

	/*
		Set input type : hidden
	 */
	public function addHiddenInput($id, $class, $name, $label, $value)
	{
		$this->inputs[] = [
            'id' => $id,
            'name' => $name,
            'type' => 'hidden',
            'class' => $class,
            'label' => $label,
            'value' => $value
        ];
	}

	/*
		Set input type : select
		$optionsArray = [
			'val' => '',
			'text' => ''
		]
	 */
	public function addSelectInput($id, $class, $name, $label, $optionsArray, $selectedVal, $required = false)
	{
		$required = $this->_checkRequired($required);

		$this->inputs[] = [
			'id' => $id,
            'name' => $name,
            'type' => 'select',
            'class' => $class,
            'label' => $label,
            'options' => $optionsArray,
            'selected_val' => $selectedVal,
            'required_span' => $required['required_span'],
            'required' => $required['required_tag']
		];
	}

	/*
		Set input type : Textarea
	 */
	public function addTextareaInput($id, $class, $name, $label, $value, $rows, $required = false)
	{
		$required = $this->_checkRequired($required);

		$this->inputs[] = [
			'id' => $id,
			'class' => $class,
			'type' => 'textarea',
			'name' => $name,
			'label' => $label,
			'value' => $value,
			'rows' => $rows,
            'required_span' => $required['required_span'],
            'required' => $required['required_tag']
		];
	}

	/*
		Set input type : Radio Button
		$radioArray = [
			'id' => '',
			'class' => '',
			'val' => '',
			'text' => '',
			'checked' => 'checked' // or ''
		]
	 */
	public function addRadioInput($name, $label, $radioArray)
	{
		$this->inputs[] = [
			'type' => 'radio',
			'name' => $name,
			'label' => $label,
			'radios' => $radioArray
		];
	}

	/*
		Set input type : CheckBox
		$checkboxArray = [
			'id' => 'id',
			'class' => '',
			'val' => '',
			'text' => '',
			'checked' => 'checked' // or ''
		]
	 */
	public function addCheckboxInput($name, $label, $checkboxArray)
	{
		$this->inputs[] = [
			'type' => 'checkbox',
			'name' => $name,
			'label' => $label,
			'checkboxes' => $checkboxArray
		];
	}

	/*
		Set input type : File
	 */
	public function addFileInput($id, $class, $name, $label, $uploadInfo)
	{
		$this->inputs[] = [
			'id' => $id,
			'class' => $class,
			'type' => 'file',
			'name' => $name,
			'label' => $label,
			'upload_info' => $uploadInfo
		];
	}

	/*
		Set input type : Multiple
		$optionsArray = [
			'val' => '',
			'text' => '',
			'selected' => true // or false
		]
	 */
	public function addMultipleSelectInput($id, $class, $name, $label, $optionsArray)
	{
		$this->inputs[] = [
			'id' => $id,
            'name' => $name,
            'type' => 'multiple',
            'class' => $class,
            'label' => $label,
            'options' => $optionsArray
		];
	}
}

?>
