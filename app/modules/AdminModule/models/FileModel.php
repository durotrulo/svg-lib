<?php

/**
 * Handle files processing (or strings representing filenames)
 * 
 * @author Matus Matula
 */
class FileModel extends BaseModel 
{
	
	/**
	 * get suffix of string (fragment after last '.')
	 *
	 * @param string
	 * @return string|NULL
	 */
	public static function getSuffix($str)
	{
		$ret = null;
		$dot = strrpos($str, '.');
	    if ($dot !== false) {
		    $ret = strtolower(substr($str, $dot+1)); 
	    }
	    
	    return $ret;
	}

	
	/**
	 * get basename (string without fragment after last '.')
	 *
	 * @param string
	 * @return string
	 */
	public static function removeSuffix($str)
	{
		$dot = strrpos($str, '.');
	    if ($dot !== false) {
		    $str = substr($str, 0, $dot);
	    }
	    
	    return $str;
	}
	
	
	/**
	 * has filename required suffix?
	 *
	 * @param string
	 * @param string
	 * @return bool
	 */
	public static function hasSuffix($filename, $suffix)
	{
//		if (preg_match("/.*\.$suffix$/", $filename)) {
//			return TRUE;
//		}
//		return FALSE;
		
		return String::endsWith(".$suffix");
	}

	
	/**
	 * delete file(s)
	 * accepts various number of args as string - paths to files to be deleted
	 */
	public static function unlink()
	{
		$files = func_get_args();
		foreach ($files as $filename) {
			if (is_file($filename)) {
				@unlink($filename);
			}
		}
	}

	
	/**
	 * treats filename for potentionally dangerous characters ensuring safe saving to filesystem
	 * enables '.' except the very first and very last character for security reasons
	 * @param string
	 * @return string
	 * @throws InvalidFilenameException
	 */
	public static function handleFilename($filename)
	{
	 	$filename = String::webalize($filename, '.');
        
	 	//	check if '.' is the very first or very last character - should be enough to check that
        if (strpos($filename, '.') == 0 or strrpos($filename, '.') == strlen($filename) - 1) {
//        	throw new InvalidFilenameException('Zadajte platný názov súboru! Nie sú povolené bodky na začiatku ani na konci názvu súboru.');
        	throw new InvalidFilenameException('Enter valid filename! Dots are not allowed at the very start nor the very end of filename.');
        }
        
        return $filename;
	}

	
	/**
	 * return paths to all images within given $searchPath
	 *
	 * @param string path to search for images
	 * @param string base dirname path of given model used to construct relative path
	 * @param bool use relative|"filesystem absolute" paths to images ?
	 * @return array
	 */
	public static function getImages($searchPath, $baseDir = null, $useRelativePath = true)
	{
		$images = Finder::findFiles('*.jpg')->in($searchPath);
		if ($useRelativePath) {
			if (!$baseDir) {
				throw new ArgumentOutOfRangeException('$baseDir MUST be set if $useRelativePath==true');
			}
			$retImages = array();
			$relativePath = self::getRelativePath($baseDir);
			foreach ($images as $img) {
				array_push($retImages, str_replace(array($baseDir, '\\'), array($relativePath, '/'), $img->getFilename()));
			}
			
			return $retImages;
		} else {
			return $images;
		}
	}
	
	
	/**
	 * return unique filename having $suffix within $dirname
	 *
	 * @param string path within which filename must be unique
	 * @param string suffix of filename
	 * @param string basename of file [without suffix]
	 * @return string
	 */
	public static function getUniqueFilename($dirname, $suffix, $filename = null)
	{
		$dirname = Basic::addLastSlash($dirname);
		while (is_null($filename) or file_exists($dirname . $filename)) {
			$filename = uniqid() . '.' . $suffix;
		}
		
		return $filename;
	}
	
}
