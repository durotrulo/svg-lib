<?php

/**
 * Description of MyFileUpload
 *
 * @author Matus Matula
 */
class MyFileUpload extends FileUpload
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
			$message = 'Select %label';
		}
		
		return parent::addRule($operation, $message, $arg);
	}
	
	
	public static function validateFileSize(FileUpload $control, $limit)
	{
		$maxUploadSize = min(
			self::convertToBytes($limit),
//			self::convertToBytes(ini_get("post_max_size"))
			self::convertToBytes(ini_get("upload_max_filesize"))
		);

		$file = $control->value;

		if (!$file instanceof HttpUploadedFile) {
			throw new InvalidStateException("File cannot be uploaded!");
		} elseif ($file->isOk()) {
			if ($file->getSize() > $maxUploadSize)
				return FALSE;

			return TRUE;
			
		} else {

		switch ($file->error) {
			case UPLOAD_ERR_INI_SIZE:
				$errMsg = 'Velikost přílohy může být nanejvýš ' . TemplateHelpers::bytes($maxFileSize) . '.';
				break;

			case UPLOAD_ERR_NO_FILE:
				$errMsg = 'Nevybrali ste žiadny súbor.';
				break;

			/* 	tieto su hlavne na debug..userovi nic nepovedia	 */
			case UPLOAD_ERR_FORM_SIZE:
				$errMsg = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;

			case UPLOAD_ERR_PARTIAL:
				$errMsg = 'The uploaded file was only partially uploaded';
				break;

			case UPLOAD_ERR_NO_FILE:
				$errMsg = 'No file was uploaded';
				break;

			case UPLOAD_ERR_NO_TMP_DIR:
				$errMsg = 'Missing a temporary folder';
				break;

			case UPLOAD_ERR_CANT_WRITE:
				$errMsg = 'Failed to write file to disk';
				break;

			case UPLOAD_ERR_EXTENSION:
				$errMsg = 'File upload stopped by extension';
				break;

			default:
				$errMsg = 'Přílohu se nepodařilo nahrát.';
				break;
		}

			$control->addError($errMsg);
			return FALSE;
		}

	}

//	protected static function getMaxUpload($param) {
//
//	}

	public static function convertToBytes($size)
	{
		$bytes = substr($size, 0, strlen($size) - 1);
		$suffix = strtolower(substr($size, -1));

		switch ($suffix) {

			case 'k':
				return $bytes * 1024;
			case 'm':
				return $bytes * 1048576;
			case 'g':
				return $bytes * 1073741824;
		}
	}
	
	public function notifyRule(Rule $rule)
	{
		if (is_string($rule->operation) && strcasecmp($rule->operation, ':hasSuffix') === 0 && !$rule->isNegative) {
			$this->control->hasSuffix = $rule->arg;
		}

		parent::notifyRule($rule);
	}
	
	/**
	 * HasSuffix validator: has file specified suffix?
	 * @param  FileUpload
	 * @param  string  suffix(es) separated by comma [comma and space]
	 * @return bool
	 */
	public static function validateHasSuffix(FileUpload $control, $allowedSuffixes)
	{
		$file = $control->getValue();
		if ($file instanceof HttpUploadedFile) {
			$allowedSuffixes = preg_split('/,\s*/', $allowedSuffixes);
			foreach ($allowedSuffixes as $v) {
				if (preg_match("/.*\.$v$/i", $file->name)) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}


}

