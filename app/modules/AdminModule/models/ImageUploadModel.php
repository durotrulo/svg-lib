<?php

//class InvalidPhotoModeException extends Exception {};


/**
 * obstarava pracu s nahravanymi obrazkami
 * 
 * @author Matus Matula
 */
//class ImageUploadModel extends FileUploadModel
class ImageUploadModel extends ImageModel
{
	/**
	 * spravi nahlad a ulozi do $dirname
	 *
	 * @param string
	 * @param HttpUploadedFile
	 * @param int width to resize image
	 * @param int height to resize image
	 * @param bool [thumbnail | resize]
	 * @param string if null name is got from $file->name
	 * @return string used
	 * @throws NotImageException
	 */
	public static function savePreview($dirname, $file, $dest_w, $dest_h, $useThumbnail = true, $filename = null)
	{
		//	ak nezvoli foto, tak sa vratime..iba pri edite, pri add sa to kontroluje po odoslani formu
		if (!$file instanceof HttpUploadedFile or empty($file->name)) {
			return;
		}

		if (is_null($filename)) {
			$filename = self::handleFilename($file->name);			
		}
		
		if (!$file->isImage()) {
			throw new NotImageException('Nahrať možete iba obrázky! Poslaný súbor: ' . $file->name);
        }
        
        $img = $file->toImage();
        
        return parent::savePreview($img, $dirname, $filename, $dest_w, $dest_h, $useThumbnail);
	}
	
	
	
	public static function save($file, $dirname, $filename = null)
	{
		//	ak nezvoli foto, tak sa vratime..iba pri edite, pri add sa to kontroluje po odoslani formu
		if (!$file instanceof HttpUploadedFile or empty($file->name)) {
			return;
		}

		if (is_null($filename)) {
			$filename = self::handleFilename($file->name);			
		}
		
		if (!$file->isImage()) {
			throw new NotImageException('Nahrať možete iba obrázky! Poslaný súbor: ' . $file->name);
        }
        
        $img = $file->toImage();
        
   		return parent::save($img, $dirname, $filename);
	}
	
	
	/**
	 * makes thumbnail and resizes images storing them in $dirname
	 *
	 * @param string $dirname
	 * @param HttpUploadedFile array $files
	 * @param int
	 * @param int
	 * @param int
	 * @param int
	 * @return bool have we uploaded sth?
	 * @throws NotImageException
	 */
	public static function saveImages($dirname, $files, $thumb_w, $thumb_h, $big_w, $big_h) 
	{
		//	ak nezvoli foto, tak sa vratime
		if (count($files) == 0) {
			return false;
		}
		
		$dirname = Basic::addLastSlash($dirname);
		$dest_big = $dirname . 'big/';
		$dest_thumb = $dirname . 'thumb/';
		
		Basic::mkdir($dest_big);
		Basic::mkdir($dest_thumb);
		
		$uploaded = false;
		// Přesumene uploadované soubory .. chyby su osetrene uz pri odoslani formulara pomocou MyAppForm a MyFileInput
        foreach($files AS $file){
            //	ak nezvoli subor, tak skusime dalsi
			if (!$file instanceof HttpUploadedFile or $file->error === UPLOAD_ERR_NO_FILE) {
				continue;
			}

            $filename = $file->getName();

            if (!$file->isImage()) {
				throw new NotImageException('Nahrať možete iba obrázky! Poslaný súbor: ' . $file->name);
            }
            
			$filename = self::handleFilename($filename);
          
		  	$img = $file->toImage();
            $img2 = clone $img;
            
            //	nahlad
            self::makeThumbnail($img, $thumb_w, $thumb_h);
			$img->save($dest_thumb . $filename, self::$quality);
			
			//	big one - proportionally
			$img2->resize($big_w, $big_h);
			$img2->save($dest_big . $filename, self::$quality);
			
			//	priznak, ze sme vobec daco nahrali
			$uploaded = true;
        }
		
        return $uploaded;
	} 
	
	
	
	/* TODO : remove if not used*/
	/*
	public static function savePhoto($dirname, $file, $filename) 
	{
		//	ak nezvoli foto, tak sa vratime..iba pri edite, pri add sa to kontroluje po odoslani formu
		if (!$file instanceof HttpUploadedFile or empty($file->name)) {
			return;
		}

		$presenter = Environment::getApplication()->getPresenter();

		$dirname = Basic::addLastSlash($dirname);
	       
		Basic::mkdir($dirname);
		Basic::mkdir($dirname . 'thumb');
		
		$dest = $dirname . $filename . '.jpg';
		$dest_thumb = $dirname . 'thumb/' . $filename . '.jpg';
		
		
		if (!$file->isImage()) {
   			$presenter->flashMessage('Nahrať možete iba obrázky! Poslaný súbor: ' . $file->name, 'warning');
   			return;
        }
        
        $img = $file->toImage();
        $img2 = clone $img;
        
        //	nahlad
//		$img->thumbnail(self::NEWS_THUMB_W, self::NEWS_THUMB_H);
		$img->resize(self::PHOTO_THUMB_W, self::PHOTO_THUMB_H)->sharpen();
		$img->save($dest_thumb, self::$quality);
		
		//	big one
		$img2->resize(self::PHOTO_W, self::PHOTO_H)->sharpen();
		$img2->save($dest, self::$quality);
	}
	
	*/
	
	/**
	 * makes thumbnail and resizes big image storing them in $dirname as 'main.jpg'
	 *
	 * @param string
	 * @param HttpUploadedFile
	 * @param int
	 * @param int
	 * @param int
	 * @param int
	 * @param bool makeThumbnail for big image or just resize proportionally?
	 * @return bool have we uploaded sth?
	 * @throws NotImageException
	 */
	public static function savePreviewWithThumb($dirname, $file, $thumb_w, $thumb_h, $big_w, $big_h, $useThumbForBig = false, $filename = 'main.jpg')
	{
		//	ak nezvoli foto, tak sa vratime..iba pri edite, pri add sa to kontroluje po odoslani formu
		if (!$file instanceof HttpUploadedFile or empty($file->name)) {
			return;
		}

   		if (!$file->isImage()) {
			throw new NotImageException('Nahrať možete iba obrázky! Poslaný súbor: ' . $file->name);
        }
        
        $img = $file->toImage();
        
        parent::savePreviewWithThumb($img, $dirname, $thumb_w, $thumb_h, $big_w, $big_h, $useThumbForBig, $filename);
	}

	
	/**
	 * returns uri to 'big' image
	 * 
	 * @param string|array moze byt aj pole 'adresarov'
	 * @param string
	 * @return string
	 */
	public static function getImageUri($dirname, $filename = 'main.jpg')
	{
		if (is_array($dirname)) {
			$dirname = join('/', $dirname);
		}
		return Environment::getVariable('baseUri') . static::getRelativePath() . (is_null($dirname) ? '' : ($dirname . '/')) . $filename;
	}
	
	
	/**
	 * returns uri to 'thumbnailed' image
	 * 
	 * @param string|array moze byt aj pole 'adresarov'
	 * @param string
	 * @return string
	 */
	public static function getThumbnailUri($dirname, $filename = 'main.jpg')
	{
		if (is_array($dirname)) {
			$dirname = join('/', $dirname);
		}
		return Environment::getVariable('baseUri') . static::getRelativePath() . $dirname . '/thumb/' . $filename;
	}
	
}