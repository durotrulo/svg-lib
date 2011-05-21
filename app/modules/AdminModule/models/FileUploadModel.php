<?php

/**
 * obstarava pracu s nahravanymi subormi
 * 
 * @author Matus Matula
 */
class FileUploadModel extends FileModel 
{
	
	/**
	 * ulozi subor do $dirname a vrati nazov suboru, pod ktorym sme ho ulozili na ftp
	 * 
	 * @param string $dirname
	 * @param HttpUploadedFile $file
	 * @param string $file meno suboru, zvycajne id z db
	 * @return string
	 */
	public static function saveFile($dirname, $file, $filename = NULL)
	{
		//	ak nezvoli foto, tak sa vratime..iba pri edite, pri add sa to kontroluje po odoslani formu
		if (!$file instanceof HttpUploadedFile or empty($file->name)) {
			return null;
		}
		
		$dirname = Basic::AddLastSlash($dirname);
		Basic::mkdir($dirname);

		if (is_null($filename)) {
			$filename = $file->name;
		}

		$filename = self::handleFilename($filename);
		
		$dest = $dirname . $filename;
		
		$file->move($dest);
		
		return $filename;
	}
}