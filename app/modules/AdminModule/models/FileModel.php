<?php

class InvalidFilenameException extends Exception {};


/**
 * obstarava pracu so subormi [retazcami reprezentujucimi nazvy suborov?]
 * 
 * @author Matus Matula
 */
class FileModel extends BaseModel 
{
	
	/**
	 * vrati priponu, ak tam bola nejaka
	 *
	 * @param string $str	[abc.jpg]
	 * @return string|NULL	[jpg]
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
	 * vrati retazec bez pripony, ak tam bola nejaka
	 *
	 * @param string $str	[abc.jpg]
	 * @return string		[abc]
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
	 * @param string $filename	[abc.jpg]
	 * @param string $suffix	[jpg]
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
	 * deletes file(s)
	 *
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
	 * osetri nazov suboru pre bezpecne ulozenie na filesystem
	 * povoluje '.', ale z bezpecnostnych dovodov to nepovoli subory zacinajuce alebo konciace na '.'
	 * 
	 */
	public static function handleFilename($filename)
	{
	 	$filename = String::webalize($filename, '.');
        
	 	//	overim ci nie je bodka uplne na zaciatku alebo na konci .. to snad staci
        if (strpos($filename, '.') == 0 or strrpos($filename, '.') == strlen($filename) - 1) {
        	throw new InvalidFilenameException('Zadajte platný názov súboru! Nie sú povolené bodky na začiatku ani na konci názvu súboru.');
        }
        
        return $filename;
	}

	
		
	/**
	 * vrati nazvy vsetkych obrazkov z danej cesty
	 *
	 * @param string $path adresar, v kt. hladat obrazky
	 * @param string $baseDir zakladny adresar daneho modelu, podla kt. sa generuje relativna cesta
	 * @param bool $useRelativePath use relative|"filesystem absolute" paths to images ?
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
	
	
	public static function getUniqueFilename($dirname, $suffix, $filename = null)
	{
		$dirname = Basic::addLastSlash($dirname);
		while (is_null($filename) or file_exists($dirname . $filename)) {
			$filename = uniqid() . '.' . $suffix;
		}
		
		return $filename;
	}
	
}
