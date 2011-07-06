<?php

class CarouselModel extends ImageUploadModel
{
	
	/* carousel thumbs */
	const IMAGE_CAROUSEL_THUMB_W = 79;
	const IMAGE_CAROUSEL_THUMB_H = 80;

	/* to fit the player to full */
	const IMAGE_CAROUSEL_BIG_W = NULL;
	const IMAGE_CAROUSEL_BIG_H = 281;

	
	/**
	 * return absolute path to large-scaled image of carousel
	 *
	 * @param string
	 * @param string
	 * @return string
	 */
	public static function getCarouselImageUri($dirname, $filename = '1.jpg')
	{
		$dirname = Basic::getArray($dirname);
		array_push($dirname, 'carousel');
		return parent::getImageUri($dirname, $filename);
	}
	
	
	/**
	 * return absolute path to thumb-scaled image of carousel
	 *
	 * @param string
	 * @param string
	 * @return string
	 */
	public static function getCarouselThumbnailUri($dirname, $filename = '1.jpg')
	{
		$dirname = Basic::getArray($dirname);
		array_push($dirname, 'carousel/thumb');
		return parent::getImageUri($dirname, $filename);
	}
	
	
	/**
	 * store image together with thumb in $path as .jpg
	 *
	 * @param HttpUploadedFile
	 * @param string path to store file to
	 * @param string base filename [without suffix]
	 * @param int 0..100 quality of images (for PNG or JPEG)
	 * @return void
	 */
	protected static function saveImage($img, $path, $filename, $quality = NULL)
	{
		if (!is_null($quality)) {
			ImageModel::$quality = $quality;
		}
		ImageModel::savePreviewWithThumb($img, $path . '/carousel', self::IMAGE_CAROUSEL_THUMB_W, self::IMAGE_CAROUSEL_THUMB_H, self::IMAGE_CAROUSEL_BIG_W, self::IMAGE_CAROUSEL_BIG_H, false, "$filename.jpg"); // ?$suffix?
	}

}