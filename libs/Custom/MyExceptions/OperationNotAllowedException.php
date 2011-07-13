<?php


/**
 * The exception that is thrown when trying to perform operation with insufficient rights.
 *
 */
class OperationNotAllowedException extends Exception
{
	public function __construct($msg = 'Operation not allowed!', $code = 0, Exception $previous = NULL)
	{
		parent::__construct($msg, $code, $previous);
	}
}
