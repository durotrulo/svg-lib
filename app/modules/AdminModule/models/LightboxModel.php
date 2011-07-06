<?php
class LightboxModel extends Object
{
	/* max dimensions */
	const IMAGE_BIG_W = 800;
	const IMAGE_BIG_H = 700;


	/**
	 * saves image for usage in lightbox as .jpg cropped to max dimensions
	 *
	 * @param Image
	 * @param string
	 * @param string
	 * @param int|NULL 0..100
	 */
	public static function saveImage($img, $path, $filename, $quality = NULL)
	{
		if (!is_null($quality)) {
			ImageModel::$quality = $quality;
		}

		ImageModel::savePreview($img, $path, "$filename.jpg", self::IMAGE_BIG_W, self::IMAGE_BIG_H, true);
	}
		
}