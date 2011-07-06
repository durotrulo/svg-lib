<?php

/**
 * Image handling model
 * 
 * @author Matus Matula
 */
class ImageModel extends FileModel
{
	/** 
	 * @var int percentage of final quality for image processing
	 * 0..100 (for JPEG and PNG)
	 */
	protected static $quality = 85;


	/**
	 * make thumbnail of $img, store in $dirname as $filename
	 *
	 * @param Image $file
	 * @param string $dirname
	 * @param string
	 * @param int $dest_w width to resize image
	 * @param int $dest_h height to resize image
	 * @param bool $useThumbnail [thumbnail : resize]
	 * @return string filename as file was stored (potential dangerous chars replaced)
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
	
	
	/**
	 * save $img in $dirname named $filename
	 *
	 * @param Image
	 * @param string destination to store image to
	 * @param string file name
	 * @return string filename as file was stored (potential dangerous chars replaced)
	 * @throws NotImageException
	 */
	public static function save($img, $dirname, $filename = null)
	{
		if (!$img instanceof Image) {
			throw new NotImageException('Argument "$img" must be instance of Image, "' . get_class($img) . '" given.');
		}

		$dirname = Basic::addLastSlash($dirname);
		Basic::mkdir($dirname);
		
		if (is_null($filename)) {
			$filename = $img->name;
		}
		$filename = self::handleFilename($filename);
		
		$dest = $dirname . $filename;
		
		// PNG does not save alpha channel (transparency) by default, needs to be turned on explicitly
		$img->saveAlpha(true);
		
		$img->save($dest, self::$quality);
		
   		return $filename;
	}
	
	
	/**
	 * make thumbnail and resize big image storing them in $dirname (and $dirname/thumb) as $filename
	 *
	 * @param Image
	 * @param string
	 * @param int
	 * @param int
	 * @param int
	 * @param int
	 * @param bool makeThumbnail for big image or just resize proportionally?
	 * @param string
	 * @return void
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
		
        self::makeThumbnail($img, $thumb_w, $thumb_h);

        // PNG does not save alpha channel (transparency) by default, needs to be turned on explicitly
		$img->saveAlpha(true);
		$img->save($dest_thumb, self::$quality);
	}
	
	
	/**
	 * make thumbnail of given image
	 *
	 * @param Image
	 * @param int
	 * @param int
	 * @param mixed  x-offset in pixels or percent
	 * @param mixed  y-offset in pixels or percent
	 * @return void
	 * @throws NotImageException
	 */
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

}