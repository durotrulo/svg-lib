<?php

/**
 * obstarava pracu s obrazkami
 * 
 * @author Matus Matula
 */
class ImageModel extends FileModel
{
	protected static $quality = 85;

	/**
	 * spravi nahlad a ulozi do $dirname
	 *
	 * @param Image $file
	 * @param string $dirname
	 * @param string
	 * @param int $dest_w width to resize image
	 * @param int $dest_h height to resize image
	 * @param bool $useThumbnail [thumbnail | resize]
	 * @return string used $filename
	 * @throws NotImageException
	 */
	public static function savePreview($img, $dirname, $filename, $dest_w, $dest_h, $useThumbnail = true)
	{
		if (!$img instanceof Image) {
			throw new NotImageException('Argument "$img" must be instance of Image, "' . get_class($img) . '" given.');
		}

		$dirname = Basic::addLastSlash($dirname);
		Basic::mkdir($dirname);
		
		$filename = self::handleFilename($filename);			
		
		$dest = $dirname . $filename;
		
		// resize only if needed
		if ($img->getWidth() > $dest_w || $img->getHeight() > $dest_h) {
       		if ($useThumbnail) {
            	self::makeThumbnail($img, $dest_w, $dest_h);
	        } else {
				$img->resize($dest_w, $dest_h)->sharpen();
	        }
		}
        
		// PNG does not save alpha channel (transparency) by default, needs to be turned on explicitly
		$img->saveAlpha(true);
		
		$img->save($dest, self::$quality);
		
   		return $filename;
	}
	
	
	public static function save($img, $dirname, $filename = null)
	{
		if (!$img instanceof Image) {
			throw new NotImageException('Argument "$img" must be instance of Image, "' . get_class($img) . '" given.');
		}

		$dirname = Basic::addLastSlash($dirname);
		Basic::mkdir($dirname);
		
		if (is_null($filename)) {
			$filename = self::handleFilename($file->name);			
		}
		
		$dest = $dirname . $filename;
		
		// PNG does not save alpha channel (transparency) by default, needs to be turned on explicitly
		$img->saveAlpha(true);
		
		$img->save($dest, self::$quality);
		
   		return $filename;
	}
	
	
	protected static function makeThumbnail(&$img, $width, $height, $left = '50%', $top = '50%')
	{
		if (!$img instanceof Image) {
			throw new NotImageException('Argument "$img" must be instance of Image, "' . get_class($img) . '" given.');
		}
		
		// resize only if needed
		if ($img->getWidth() > $width || $img->getHeight() > $height) {
			$img->resize((int) $width, (int) $height, Image::FILL)
				->crop($left, $top, (int) $width, (int) $height);
		}
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
	 * @param Image
	 * @param string
	 * @param int
	 * @param int
	 * @param int
	 * @param int
	 * @param bool makeThumbnail for big image or just resize proportionally?
	 * @param string
	 * @throws NotImageException
	 */
	public static function savePreviewWithThumb($img, $dirname, $thumb_w, $thumb_h, $big_w, $big_h, $useThumbForBig = false, $filename = 'main.jpg')
	{
		if (!$img instanceof Image) {
			throw new NotImageException('Argument "$img" must be instance of Image, "' . get_class($img) . '" given.');
		}
		
		Basic::mkdir($dirname);
		Basic::mkdir($dirname . '/thumb');
		
		$dest = $dirname . '/' . $filename;
		$dest_thumb = $dirname . '/thumb/' . $filename;
		
		// big one
        $img2 = clone $img;
		// resize only if needed
		if ($img2->getWidth() > $big_w || $img2->getHeight() > $big_h) {
			if ($useThumbForBig) {
		        self::makeThumbnail($img2, $big_w, $big_h);
			} else {
				$img2->resize($big_w, $big_h)->sharpen();
			}
		}

		// PNG does not save alpha channel (transparency) by default, needs to be turned on explicitly
		$img2->saveAlpha(true);

		$img2->save($dest, self::$quality);
		
		 // nahlad
        self::makeThumbnail($img, $thumb_w, $thumb_h);

        // PNG does not save alpha channel (transparency) by default, needs to be turned on explicitly
		$img->saveAlpha(true);
		$img->save($dest_thumb, self::$quality);
		
	}

}