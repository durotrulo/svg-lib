<?php

class MyTextInput extends TextInput
{
	
	/**
	 * Adds a validation rule.
	 * Adds default error messages
	 * @param  mixed      rule type
	 * @param  string     message to display for invalid data
	 * @param  mixed      optional rule arguments
	 * @return FormControl  provides a fluent interface
	 */
	public function addRule($operation, $message = NULL, $arg = NULL)
	{
		switch ($operation) {
			case Form::FILLED:
				if (is_null($message)) {
					$message = 'Enter %label!';
				}
				break;

			case Form::EMAIL:
				if (is_null($message)) {
					$message = 'Enter valid email address!';
				}
				break;
				
			case Form::MIN_LENGTH:
				// shift args
				if (is_null($arg)) {
					$arg = $message;
					$message = 'Minimum length of %label is %d chars!';
				}
				break;
				
			case Form::MAX_LENGTH:
				// shift args
				if (is_null($arg)) {
					$arg = $message;
					$message = 'Maximum length of %label is %d chars!';
				}
				break;
				
			default:
				break;
		}
		
		return parent::addRule($operation, $message, $arg);
	}
}
