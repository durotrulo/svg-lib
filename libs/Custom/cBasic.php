<?php

abstract class Basic extends Object {

	public static function copyr($source, $dest)
	{
		// Simple copy for a file
		if (is_file($source)) {
			$c = copy($source, $dest);
			chmod($dest, 0777);
			return $c;
		} elseif (!is_dir($source)) {
  			throw new DirectoryNotFoundException("Source directory '$source' not found");
		}
		
		// Make destination directory
		if (!is_dir($dest)) {
			$oldumask = umask(0);
			mkdir($dest, 0777, true);
			umask($oldumask);
		}

		// Loop through the folder
		$dir = dir($source);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == "." || $entry == "..") {
				continue;
			}

			// Deep copy directories
			if ($dest !== "$source/$entry") {
				self::copyr("$source/$entry", "$dest/$entry");
			}
		}

		// Clean up
		$dir->close();
		return true;
	}
	
	
	public static function isPlusNumeric($id)
	{
		if (is_array($id)) {
			foreach ($id as $v) {
				if (!is_numeric($v) or $v < 0) {
					return false;
				}
			}
			
			return true;
		} else {
			return (is_numeric($id) AND $id>0) ? true : false;
		}
	}
	
	
	/**
	 * prida na koniec stringu '/', ak tam este nie je
	 */
  	public static function addLastSlash($str, $slash = '/')
  	{
  		$lastIndex = strlen($str) - 1;
	    if ($str[$lastIndex] != $slash) {
      		$str .= $slash;
	    }
    
    	return $str;
  	}

  	
  	/**
  	 * vytvori adresare, ak este cesta neexistuje
  	 */
  	public static function mkdir($path, $mode = 0777)
  	{
  		if (!file_exists($path)) {
			mkdir($path, $mode, true);
  		}
  	}

  	
  	public static function makeDirs($path, $mode = 0777)
  	{
  		throw new DeprecatedException('makeDirs() is deprecated. Use mkdir() instead');
  	}
  	
  	
	/**
	 * vymaze cely adresar so subormi, moznost rekurzie
	 *
	 * @param str $dir	cela cesta k adresaru
	 * @param bool $recursive mazat rekurzivne vsetko?
	 * @throws DirectoryNotFoundException
	 * @return void
	 */
	public static function rmdir($dir, $recursive = false)
	{
 		if (file_exists($dir) and is_dir($dir)) {
	  		$iterator = new RecursiveDirectoryIterator($dir);
	   		foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) 
	   		{
	      		if ($file->isDir() && $recursive) {
	         		rmdir($file->getPathname(), $recursive);
	      		} elseif ($file->isReadable() and $file->isFile()) {
	         		unlink($file->getPathname());
		      	}
	   		}
	   
	   		rmdir($dir);
 		} else {
 			throw new DirectoryNotFoundException("Directory '$dir' not found!");
 		}
	}
	
	
	public static function getSuffix($str)
	{
		$dot = strrpos($str, '.');
	    if ($dot !== false) {
		    $ret = strtolower(substr($str, $dot+1)); 
	    }
	    
	    return $ret;
	}
	
}