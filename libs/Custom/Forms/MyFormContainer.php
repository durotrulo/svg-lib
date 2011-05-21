<?php

/**
 * pridava funkcionalitu MyFileUpload aj ku containeru
 */
class MyFormContainer extends FormContainer
{
	
	public function addFile($name, $label = NULL, $maxFileSize = NULL) {
		
		return $this[$name] = new MyFileUpload($label);

	}

	
	/**
	 * Adds naming container to the form.
	 * @param  string  name
	 * @return FormContainer
	 */
	public function addContainer($name)
	{
		$control = new self;
		$control->currentGroup = $this->currentGroup;
		return $this[$name] = $control;
	}
}
