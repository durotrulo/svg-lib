<?php
class LightboxModel extends Object
{
	/* max dimensions */
	const IMAGE_BIG_W = 800;
	const IMAGE_BIG_H = 700;
//	const IMAGE_BIG_W = 700;
//	const IMAGE_BIG_H = NULL;


	/**
	 * saves image for usage in lightbox
	 *
	 * @param Image $img
	 * @param string $path
	 * @param string $filename
	 * @param int|NULL $quality
	 */
	public static function saveImage($img, $path, $filename, $quality = NULL)
	{
		if (!is_null($quality)) {
			ImageModel::$quality = $quality;
		}

		ImageModel::savePreview($img, $path, "$filename.jpg", self::IMAGE_BIG_W, self::IMAGE_BIG_H, true);
	}
		
}