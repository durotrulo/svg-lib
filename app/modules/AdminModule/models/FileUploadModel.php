<?php

/**
 * Process files being uploaded
 * 
 * @author Matus Matula
 */
class FileUploadModel extends FileModel 
{
	
	/**
	 * save file to $dirname and return filename as stored on ftp
	 * 
	 * @param string
	 * @param HttpUploadedFile
	 * @param string filename with suffix (usually id from DB)
	 * @return string
	 */
	public static function saveFile($dirname, $file, $filename = NULL)
	{
		// return if no file given [may occur only when editing item otherwise it's controlled when submitting form]
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