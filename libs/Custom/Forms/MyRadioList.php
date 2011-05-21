<?php

class MyRadioList extends RadioList
{
	
	/**
	 * Adds a validation rule.
	 * @param  mixed      rule type
	 * @param  string     message to display for invalid data
	 * @param  mixed      optional rule arguments
	 * @return FormControl  provides a fluent interface
	 */
	public function addRule($operation, $message = NULL, $arg = NULL)
	{
		if ($operation === ':filled' and is_null($message)) {
			$message = 'Enter %label';
		}
		
		return parent::addRule($operation, $message, $arg);
	}
}
