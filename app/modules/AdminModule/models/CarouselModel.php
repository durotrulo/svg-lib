<?php

class CarouselModel extends ImageUploadModel
{
	
	// todo: move to config.ini?
	
	/* to fit the player */
//	const IMAGE_CAROUSEL_THUMB_W = 375;
//	const IMAGE_CAROUSEL_THUMB_H = 200;

	/* carousel thumbs */
	const IMAGE_CAROUSEL_THUMB_W = 79;
	const IMAGE_CAROUSEL_THUMB_H = 80;

	/* to fit the player to full */
//	const IMAGE_CAROUSEL_BIG_W = 465;
	const IMAGE_CAROUSEL_BIG_W = NULL;
	const IMAGE_CAROUSEL_BIG_H = 281;
	
	/**
	 * vrati absolutnu cestu k velkemu obrazku carouselu
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @return string
	 */
	public static function getCarouselImageUri($dirname, $filename = '1.jpg')
	{
		$dirname = Basic::getArray($dirname);
		array_push($dirname, 'carousel');
		return parent::getImageUri($dirname, $filename);
	}
	
	
	/**
	 * vrati absolutnu cestu k thumbnailu carouselu
	 *
	 * @param string $dirname
	 * @param string $filename
	 * @return string
	 */
	public static function getCarouselThumbnailUri($dirname, $filename = '1.jpg')
	{
		$dirname = Basic::getArray($dirname);
		array_push($dirname, 'carousel/thumb');
		return parent::getImageUri($dirname, $filename);
	}
	
	
	/**
	 * uklada obrazok aj s nahladom
	 *
	 * @param HttpUploadedFile $file
	 * @param StdClass $fileinfo
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