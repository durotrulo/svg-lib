<?php

/**
 * container class for common tasks
 *
 */
abstract class Common extends Object 
{
	/**
	 * check if requested name is available for given $type
	 * and send response in json
	 *
	 * @param string to test if available
	 * @param string type of item [project, user, package]
	 */
	public static function handleCheckAvailability($_this, $name, $type)
	{
		if ($_this->model->isAvailable($name)) {
			$msg = '<div class="available">' . ucfirst($type) . ' name <b>' . $name . '</b> is available.</div>';
		} else {
			$msg = '<div class="not-available">' . ucfirst($type) . ' name <b>' . $name . '</b> is NOT available.</div>';
		}
		
		$_this->payload->availability = $msg;
		$_this->terminate();
	}
}