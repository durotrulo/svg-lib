<?php
define('FILES_PATH', PUBLIC_DATA_DIR . "/files");
define('FILES_ORIG_PATH', DATA_DIR . "/files");

//class FILEInvalidFilenameException extends Exception {};
class NotSupportedFileType extends Exception {};

/**
 * Files model
 *
 * @author	Matus Matula
 */
class FilesModel extends BaseModel
{
	/** @var string db table name */
	const TABLE = self::FILES_TABLE;

	/** @var string absolute path (public) where files are stored */
	const PATH = FILES_PATH;

	/** @var string absolute path (private) where files are stored */
	const ORIG_PATH = FILES_ORIG_PATH;

	
//	/** @var int width of detailed (largest-sized) preview image */
//	const DETAIL_W = 400;
//	
//	/** @var int height of detailed (largest-sized) preview image */
//	const DETAIL_H = 400;
	
	/** @var int width of large-sized preview image */
	const LARGE_W = 380;
	
	/** @var int height of large-sized preview image */
	const LARGE_H = 380;
	
	/** @var int width of medium-sized preview image */
	const MEDIUM_W = 170;
	
	/** @var int height of medium-sized preview image */
	const MEDIUM_H = 170;
	
	/** @var int width of small-sized preview image */
	const SMALL_W = 85;

	/** @var int height of small-sized preview image */
	const SMALL_H = 85;
	
	/** @var int width of site-wide image (top level) */
	const SITEWIDE_W = 380;

	/** @var int height of site-wide image (top level) */
	const SITEWIDE_H = null;
	
	/** @var int width of .png alternative to .svg for use in older SW */
	const BITMAP_ALTERNATIVE_W = 380;

	/** @var int height of .png alternative to .svg for use in older SW */
	const BITMAP_ALTERNATIVE_H = null;
	
	/** @var format type of bitmap alternative to .svg files */
	const BITMAP_ALTERNATIVE_FORMAT = 'png';
	
	const FILTER_BY_VECTOR = 'vector';
	const FILTER_BY_BITMAP = 'bitmap';
	const FILTER_BY_INSPIRATION = 'inspiration';

	const FILTER_BY_PROJECT = 'project';
	const FILTER_BY_LIGHTBOX = 'lightbox';
	
	const ORDER_BY_NAME = 'name';
	const ORDER_BY_DATE = 'date';
	const ORDER_BY_SIZE = 'size';
	
	const SIZE_SMALL = 'small';
	const SIZE_MEDIUM = 'medium';
	const SIZE_LARGE = 'large';
	const SIZE_SITEWIDE = 'sitewide'; // top level file
	
	/** @dbsync #complexity.id */
	const COMPLEXITY_INSPIRATION_ID = 9;
	const COMPLEXITY_ALL_LEVELS_ID = 1;
	
	/** @var array of allowed file types */
	private $allowedSuffix = array('jpg', 'jpeg', 'gif', 'png', 'pdf', 'ai', 'svg', 'eps');

	/** @var array of allowed sizenames for files (used for FS naming and rendering images) */
	private static $sizes = array(
		self::SIZE_SMALL,
		self::SIZE_MEDIUM,
		self::SIZE_LARGE,
	);

	
	/**
	 * @return array
	 */
	public static function getSizes()
	{
		return self::$sizes;
	}
	
	
	/**
	 * @return array
	 */
	public function getAllowedSuffix()
	{
		return $this->allowedSuffix;
	}
	

	public function findAll()
	{
		return dibi::select('*')
					->from(self::TABLE);
	}
	
	
//	public function find($id)
//	{
//		return $this->findAll()->where('id = %i', $id);
//	}
	
	
	/**
	 * filter out items that do NOT meet $filter
	 *
	 * @param DibiFluent
	 * @param string|null
	 * @param mixed [optional] filter value
	 * @return $this
	 */
	public function filter(&$items, $filter, $filterVal = null)
	{
		switch ($filter) {
			case null:
				break;
			case self::FILTER_BY_BITMAP: // intentionally no break
			case self::FILTER_BY_VECTOR:
				$items->where('type = %s', $filter);
				break;
		
			case self::FILTER_BY_INSPIRATION:
				$items->where('complexity_id = %i', self::COMPLEXITY_INSPIRATION_ID);
				break;
				
			case self::FILTER_BY_PROJECT:
				$this->filterByProject($items, $filterVal);
				break;
				
			case self::FILTER_BY_LIGHTBOX:
				$this->filterByLightbox($items, $filterVal);
				break;
				
			default:
				throw new ArgumentOutOfRangeException("Unknown \$filter $filter.");
				break;
		}
		
		return $this;
	}
	
	
	/**
	 * filter out items with complexity other than given
	 *
	 * @param DibiFluent
	 * @param int
	 * @return $this
	 */
	public function filterByComplexity(&$items, $complexityId)
	{
		if ($complexityId !== self::COMPLEXITY_ALL_LEVELS_ID) {
			$items->where('complexity_id = %i', $complexityId);
		}
		
		return $this;
	}
		
		
	/**
	 * filter items with given project
	 *
	 * @param DibiFluent
	 * @param int
	 * @return $this
	 */
	public function filterByProject(&$items, $projectId)
	{
		$items->where('projects_id = %i', $projectId);
		
		return $this;
	}
	
	
	/**
	 * filter items folded in given lightbox
	 *
	 * @param DibiFluent
	 * @param int
	 * @return $this
	 */
	public function filterByLightbox(&$items, $lightboxId)
	{
		$items->where('id IN (%sql)', 
			dibi::select('files_id')
				->from(self::FILES_2_LIGHTBOXES_TABLE)
					->as('f2l')
				->where('files.id = f2l.files_id AND f2l.lightboxes_id = %i', $lightboxId)
				->__toString()
		);
		
		return $this;
	}
	
	
	/**
	 * filters items with required tags
	 * @param DibiFluent
	 * @param string space-separated tags
	 */
	public function filterByTag(&$items, $tags)
	{
		if (!empty($tags)) {
			$tags = preg_split('/\s+/', $tags);
			
			/* search is logical AND - select files tagged with ALL required tags */
			/**
			 * EXAMPLE:
			  	select f1.files_id
				from files_2_tags f1 
				join files_2_tags f2
					on f2.files_id=f1.files_id AND f2.tags_id=5
				join files_2_tags f3
					on f3.files_id=f1.files_id AND f3.tags_id=2
				WHERE f1.tags_id = 3
			 */
			$tagIds = dibi::select('id')
				->from(self::TAGS_TABLE)
				->where('name IN (%sql)', $this->formatInString($tags))
				->fetchPairs();
			
			if (empty($tagIds)) {
				throw new TagNotFound('Provided tags do not exist!');
			}
			
			// build condition for only 1 query using inner joins
			$cond = dibi::select('f2t.files_id')
					->from(self::FILES_2_TAGS_TABLE)
					->as('f2t')
					->where('f2t.tags_id = %i', $tagIds[0]);
					
			array_shift($tagIds);
			foreach ($tagIds as $k => $tagId) {
				$cond->join(self::FILES_2_TAGS_TABLE)
					->as("f2t$k")
					->on("f2t$k.files_id = f2t.files_id AND f2t$k.tags_id = %i", $tagId);
			}
			
			$items->where('id IN(%sql)', $cond->__toString());
		}
		
		return $this;
	}
	

	/**
	 * order items
	 *
	 * @param DibiFluent
	 * @param string
	 * @param string
	 * @return $this
	 */
	public function order(&$items, $orderBy, $sorting)
	{
		BaseModel::validateSorting($sorting, dibi::DESC);
		switch ($orderBy) {
			case self::ORDER_BY_DATE:
				$items->orderBy('uploaded', $sorting);
				break;

			case self::ORDER_BY_SIZE:
				$items->orderBy('size', $sorting);
				break;
				
			case self::ORDER_BY_NAME:
				$items->orderBy('filename', $sorting);
				break;
				
			default:
				throw new ArgumentOutOfRangeException("Unknown \$orderby value $orderBy.");
				break;
		}
		
		return $this;
	}
	
	
	/**
	 * gets path to preview image
	 *
	 * @param int
	 * @param string one of self::sizes
	 * @param string
	 * @param bool use relative path?
	 * @return string
	 */
	public function getPreviewPath($projectId, $size, $filename = null, $relative = true)
	{
		$path = $relative ? self::getRelativePath(self::PATH) : self::PATH;
		return "$path/$projectId/$size/$filename";
	}
	
	
	/**
	 * get path to original file (for download)
	 *
	 * @param int|DibiRow file.id or file object
	 * @return string
	 */
	public function getOriginalPath($file)
	{
		// id
		if (is_numeric($file)) {
			$file = $this->find($file);
		}
		$filename = FileModel::removeSuffix($file->filename);
		return self::ORIG_PATH . "/{$file->projects_id}/{$filename}.{$file->suffix}";
	}
	
	
	/**
	 * prepare file for download - increment downloadCount and log action
	 * @todo overit, ze user moze subor stiahnut
	 *
	 * @param int #files.id
	 * @return string filepath to original file
	 */
	private function prepare4download($id)
	{
		$this->update($id, array(
			'downloads%sql' => 'downloads + 1',
		));
		
		$this->logsModel->insert(
			array(
				'users_id' => $this->userId,
				'files_id' => $id,
				'action' => LogsModel::ACTION_DOWNLOAD,
			)
		);
		
		return $this->getOriginalPath($id);
	}
	
	
	/**
	 * get file path to bitmap alternative to .svg
	 * if not SVG file return original path
	 *
	 * @param string
	 * @return string
	 */
	protected function getBitmapAlternative($filepath)
	{
		if (strtolower(FileModel::getSuffix($filepath)) === 'svg') {
			$baseFilePath = FileModel::removeSuffix($filepath);
			$filepath = $baseFilePath . '.' . self::BITMAP_ALTERNATIVE_FORMAT;
		}
		return $filepath;
	}
	
	
	/**
	 * output file for download
	 *
	 * @param array|int #files.id
	 * @param bool download bitmap alternative to svg files?
	 */
	public function download($ids, $useBitmap = false)
	{
		// output as .zip
		if (is_array($ids)) {
			$path = array();
			
			// create ZIP if not exists
			$uniqueId = md5(join('-', $ids));
			$zipPath = PUBLIC_DATA_DIR . '/files-zip/' . $uniqueId . '.zip';
			if (!file_exists($zipPath)) {
				Basic::mkdir(dirname($zipPath));
				$zipper = new ZipArchive();
				if ($zipper->open($zipPath, ZIPARCHIVE::CREATE) !== true) {
				    exit("cannot open <$zipPath>\n");
				}
				
				foreach ($ids as $id) {
					$path = $this->prepare4download($id);
					if ($useBitmap) {
						$path = $this->getBitmapAlternative($path);
					}
					$zipper->addFile($path, basename($path));
				}
	
				$zipper->close();
			}

			parent::downloadFile($zipPath, $uniqueId . '.zip', false);

		// output single file
		} else {
			$path = $this->prepare4download($ids);
			if ($useBitmap) {
				$path = $this->getBitmapAlternative($path);
			}
			parent::downloadFile($path);
		}
	}
	
	
	
	/**
	 * bind tags to file
	 *
	 * @param int
	 * @param array of tag ids
	 */
	public function bindTags($fileId, $tagIds)
	{
		$data = array(
			'files_id' => $fileId,
			'tagged_by' => $this->userId,
		);
		
		foreach ($tagIds as $tagId) {
			$data['tags_id'] = $tagId;
			$id = dibi::insert(self::FILES_2_TAGS_TABLE, $data)->execute(dibi::IDENTIFIER);
			$this->logsModel->insert(
				array(
					'users_id' => $this->userId,
					'files_id' => $fileId,
					'tags_id' => $tagId,
					'action' => LogsModel::ACTION_ASSOCIATE,
				)
			);
		}
	}
	
	/**
	 * unbind tag from file
	 *
	 * @param int file id
	 * @param int tag id
	 */
	public function unbindTag($fileId, $tagId)
	{
		dibi::delete(self::FILES_2_TAGS_TABLE)
			->where('files_id = %i', $fileId)
			->where('tags_id = %i', $tagId)
			->execute();
			
		$this->logsModel->insert(
			array(
				'users_id' => $this->userId,
				'files_id' => $fileId,
				'tags_id' => $tagId,
				'action' => LogsModel::ACTION_UNBIND,
			)
		);
	}
	
	
	/**
	 * get file description
	 *
	 * @param int
	 * @return string
	 */
	public function getDesc($fileId)
	{
		return dibi::select('description')
					->from(self::TABLE)
					->where('id = %i', $fileId)
					->fetchSingle();
	}
	
	
	
	/**
	 * get tags bound to file
	 *@todo: clean
	 * @param int
	 * @return DibiRow array
	 */
	public function getTags($fileId)
	{
//dump(
//			dibi::query(
//			"
//			SELECT key_name
//			FROM %n
//			WHERE id IN (
//				SELECT role_id
//				FROM %n
//				WHERE user_id IN (
//					SELECT tagged_by
//					FROM %n
//					WHERE files_id = %i
//				)
//			)
//			ORDER BY id DESC
//			", self::ACL_ROLES_TABLE,
//				self::ACL_USERS_2_ROLES_TABLE,
//				self::FILES_2_TAGS_TABLE,
//				$fileId
//			)->fetchAll()
//		);
//		die();

//		dump(
		return dibi::select(
				'f2t.tags_id AS id, 
				t.name, 
				r.key_name AS userLevel,
				r.id as role_id
				'
				)
				->from(self::FILES_2_TAGS_TABLE)
					->as('f2t')
				->leftJoin(self::ACL_ROLES_TABLE)	// roles of user who tagged given file
					->as('r')
					->on('
					r.id IN (
						SELECT role_id
						FROM %n
						WHERE user_id IN (
							SELECT tagged_by
							FROM %n
							WHERE files_id = %i AND tags_id = t.id
						)
					)
				',	self::ACL_USERS_2_ROLES_TABLE,
					self::FILES_2_TAGS_TABLE,
					$fileId
				)
				->innerJoin(self::TAGS_TABLE)
					->as('t')
					->on('t.id = f2t.tags_id')
				->where('f2t.files_id = %i', $fileId)
				->orderBy('role_id DESC') #zaujima ma rola s vyssim id - teda napr. designer viac ako superadmin, kvoli ofarbeniu tagov
//				->test()
				->fetchAssoc('id');
//				->fetchAll()
//		);die();
/*
		dump(
			dibi::select('f2t.tags_id AS id, t.name, r.key_name
				 AS userLevel'
				
				)
				->from(self::FILES_2_TAGS_TABLE)
					->as('f2t')
				->crossJoin(self::ACL_ROLES_TABLE)
					->as('r')
				->innerJoin(self::TAGS_TABLE)
					->as('t')
					->on('t.id = f2t.tags_id')
				->where('f2t.files_id = %i', $fileId)
				// roles of user who tagged given file
				->where('
					r.id IN (
						SELECT role_id
						FROM %n
						WHERE user_id IN (
							SELECT tagged_by
							FROM %n
							WHERE files_id = %i AND tags_id = t.id
						)
					)
					ORDER BY id DESC
				',	self::ACL_USERS_2_ROLES_TABLE,
					self::FILES_2_TAGS_TABLE,
					$fileId
				)
//				->test()
				->fetchAll()
		);die();
		return dibi::select('f2t.tags_id AS id, t.name,
				(
					SELECT key_name
					FROM %n
					WHERE id IN (
						SELECT role_id
						FROM %n
						WHERE user_id IN (
							SELECT tagged_by
							FROM %n
							WHERE files_id = %i
						)
					)
					ORDER BY id DESC
					LIMIT 1
				) AS userLevel'
				, self::ACL_ROLES_TABLE,
						self::ACL_USERS_2_ROLES_TABLE,
						self::FILES_2_TAGS_TABLE,
						$fileId
				)
				->from(self::FILES_2_TAGS_TABLE)
					->as('f2t')
				->innerJoin(self::TAGS_TABLE)
					->as('t')
					->on('t.id = f2t.tags_id')
				->where('f2t.files_id = %i', $fileId)
				->fetchAll();
				*/
	}
	
	
	public function add2lightbox($fileId, $lightboxId)
	{
		dibi::insert(self::FILES_2_LIGHTBOXES_TABLE, array(
			'files_id' => $fileId,
			'lightboxes_id' => $lightboxId,
			'added_by' => $this->userId,
			'added' => dibi::datetime(),
		))->execute();
		
		$this->logsModel->insert(
			array(
				'users_id' => $this->userId,
				'files_id' => $fileId,
				'lightboxes_id' => $lightboxId,
				'action' => LogsModel::ACTION_ASSOCIATE,
			)
		);
	}
	
	
	/**
	 * remove files from given lightbox
	 *
	 * @param array of ids
	 * @param int
	 */
	public function removeFromLightbox(array $fileIds, $lightboxId)
	{
		dibi::delete(self::FILES_2_LIGHTBOXES_TABLE)
			->where('files_id IN (%iN)', $fileIds)
			->where('lightboxes_id = %i', $lightboxId)
			->execute();

		foreach ($fileIds as $fileId) {
			$this->logsModel->insert(
				array(
					'users_id' => $this->userId,
					'files_id' => $fileId,
					'lightboxes_id' => $lightboxId,
					'action' => LogsModel::ACTION_UNBIND,
				)
			);
		}
	}
	
	
	
	/**
	 * get user's own lightboxes for given fileId (=>LB in which file is not yet)
	 *
	 * @return unknown
	 */
	public function fetchOwnUnusedLightboxes($fileId)
	{
		return dibi::select('id, name')
			->from(self::LIGHTBOXES_TABLE)
			->where('owner_id = %i', $this->userId)
			->where('id NOT IN(%sql)', 
				dibi::select('lightboxes_id')
					->from(self::FILES_2_LIGHTBOXES_TABLE)
					->where('files_id = %i', $fileId)
					->__toString()
			)->fetchPairs('id', 'name');
	}
	
	
	
	/**
	 * permanently delete file from DB and file system
	 *
	 * @param int fileId
	 */
	public function delete($id)
	{
		$file = $this->find($id);
		parent::delete($id);
		

		// DELETE FROM FILESYSTEM
		$filepath = $this->getFilePaths($file, true);
		foreach ($filepath as $fp) {
			FileModel::unlink($fp);
		}
		$bitmapAlternativePath = $this->getBitmapAlternative(array_pop($filepath));
		FileModel::unlink($bitmapAlternativePath);

		
		$this->logsModel->insert(
			array(
				'users_id' => $this->userId,
				'files_id' => $id,
				'action' => LogsModel::ACTION_DELETE,
			)
		);
		
	}
	
	
	/**
	 * get all paths to file (all thumbs, topfileSize, original)
	 *
	 * @param DibiRow info about file
	 * @param bool append filename to the path?
	 * @return array
	 */
	protected function getFilePaths($data, $appendFilename = false)
	{
		$dirname = self::PATH . '/' . $data['projects_id'] . '/';
		$orig_dirname = self::ORIG_PATH . '/' . $data['projects_id'] . '/';
		
		$filepath = array(
			$dirname . self::SIZE_LARGE . '/',
			$dirname . self::SIZE_MEDIUM . '/',
			$dirname . self::SIZE_SMALL . '/',
			$dirname . self::SIZE_SITEWIDE . '/',
			$orig_dirname,
//					$dirname . 'detail/',
		);
		
		if ($appendFilename) {
			foreach ($filepath as &$fp) {
				$fp .= $data['filename'];
			}
		}
		
		return $filepath;
	}
	
	
	/**
	 * saves uploaded files to FS and DB
	 *
	 * @param array
	 * @return void
	 * 
	 * @throws NotSupportedFileType
	 */
	public function insert(array $data)
	{
		$dirname = self::PATH . '/' . $data['projects_id'] . '/';
		$orig_dirname = self::ORIG_PATH . '/' . $data['projects_id'] . '/';
		$file = $data['file'];

		$data['size'] = $file->size;
		$data['suffix'] = Basic::getSuffix($file->name);
		if (!in_array($data['suffix'], $this->allowedSuffix)) {
			throw new NotSupportedFileType("{$data['suffix']} is not valid file type. Only " . join(', ', $this->allowedSuffix) . " are allowed.");
		}

		$data['type'] = $data['suffix'] === 'svg' ? 'vector' : 'bitmap';
		
		// first 4 items represents public filepaths
		$filepath = array_slice($this->getFilePaths($data, false), 0, 4);
//		$filepath = array(
//			$dirname . self::SIZE_LARGE . '/',
//			$dirname . self::SIZE_MEDIUM . '/',
//			$dirname . self::SIZE_SMALL . '/',
//			$dirname . self::SIZE_SITEWIDE . '/',
////					$dirname . 'detail/',
//		);
		
		foreach ($filepath as $path) {
			Basic::mkdir($path);
		}
		
		// process thumbnails
		switch ($data['suffix']) {
			case 'jpg': // intentionally no break
			case 'jpeg': // intentionally no break
			case 'gif': // intentionally no break
			case 'png':
				// upload original file
				$data['filename'] = ImageUploadModel::save($file, $orig_dirname);
		
				$file2 = clone $file;
				$file3 = clone $file;
				ImageUploadModel::savePreview($filepath[0], $file, self::LARGE_W, self::LARGE_H, true, $data['filename']);
				ImageUploadModel::savePreview($filepath[1], $file2, self::MEDIUM_W, self::MEDIUM_H, true, $data['filename']);
				ImageUploadModel::savePreview($filepath[2], $file3, self::SMALL_W, self::SMALL_H, true, $data['filename']);
				
				if ($data['is_top_file']) {
					$file4 = clone $file;
					ImageUploadModel::savePreview($filepath[3], $file4, self::SITEWIDE_W, self::SITEWIDE_H, false, $data['filename']);
				}
				break;
				
			case 'pdf': // intentionally no break
			case 'ai': // intentionally no break
			case 'eps':
//			case 'svg': // for older browsers that do not support SVG in HTML img http://caniuse.com/svg-img
				$suffix = 'jpg'; // suffix of thumb previews
//				$filename = FileModel::removeSuffix($data['filename']) . '.' . $data['suffix'];
				$filename = FileModel::removeSuffix($file->name);
				$data['filename'] = FileModel::getUniqueFilename($dirname . '/large/', $suffix, $filename);

				// upload original file
				FileUploadModel::saveFile($orig_dirname, $file, FileModel::removeSuffix($data['filename']) . '.' . $data['suffix']);
		
				// upload thumbnails
				$im = new imagick($file->getTemporaryFile() .'[0]');
				$im2 = clone $im;
				$im3 = clone $im;
//				$im4 = clone $im;
				
				$this->saveImagickPreview($im, $filepath[0] . $data['filename'], $suffix, self::LARGE_W, self::LARGE_H);
				$this->saveImagickPreview($im2, $filepath[1] . $data['filename'], $suffix, self::MEDIUM_W, self::MEDIUM_H);
				$this->saveImagickPreview($im3, $filepath[2] . $data['filename'], $suffix, self::SMALL_W, self::SMALL_H);
				
				
				if ($data['is_top_file']) {
					$im4 = clone $im;
					$this->saveImagickPreview($im4, $filepath[3] . $data['filename'], $suffix, self::SITEWIDE_W, self::SITEWIDE_H);
				}

				break;
				
			// just save uploaded file and copy it to all dirs - no need to resize, it is scalable ;)
			case 'svg':
				// upload original file
				$data['filename'] = FileUploadModel::saveFile($orig_dirname, $file);
				copy($orig_dirname . '/' . $data['filename'], $filepath[0] . $data['filename']);
				copy($orig_dirname . '/' . $data['filename'], $filepath[1] . $data['filename']);
				copy($orig_dirname . '/' . $data['filename'], $filepath[2] . $data['filename']);
		
				if ($data['is_top_file']) {
					copy($orig_dirname . '/' . $data['filename'], $filepath[3] . $data['filename']);
				}
				
				// todo: store png alternative
//				$im = new imagick($file->getTemporaryFile() .'[0]');
//				$suffix = 'png';
//				$this->saveImagickPreview($im, $orig_dirname . FileModel::removeSuffix($data['filename']) . ".$suffix", $suffix, self::BITMAP_ALTERNATIVE_W, self::BITMAP_ALTERNATIVE_H);
//die();				

				break;
				
			default:
				throw new NotSupportedFileType();
				break;
		}
		
		unset($data['file']);

		$data['users_id'] = $this->getUserId();
		$fileId = parent::insert($data);
		
		$this->logsModel->insert(
			array(
				'users_id' => $this->userId,
				'files_id' => $fileId,
				'action' => LogsModel::ACTION_CREATE,
			)
		);
		
		
		return $fileId;
	}
	
	
	/**
	 * saves previews of not-images (pdf, ai, svg)
	 *
	 * @param Imagick
	 * @param string absolute filepath to save preview into
	 * @param string image format 
	 * @param int width
	 * @param int height
	 * 
	 * @return void
	 */
	private function saveImagickPreview(&$im, $filepath, $format, $w, $h)
	{
		$im->setImageFormat($format);
		$im->thumbnailImage($w, $h);
		$im->writeImage($filepath);
		$im->clear();
		$im->destroy();
	}
	
	
	public static function imagickTest()
	{
		// works
//		convert -density 10440 test/reload2.svg test/1.png
		
		// resize
		$filepath = DATA_DIR . '/files/0/file-download.svg';
//		$filepath = DATA_DIR . '/files/0/reload.svg';
		$filepathDest = DATA_DIR . '/files/0/file-download2.svg';
//		$filepathDest = DATA_DIR . '/files/0/file-download2.png';
//		$filepathDest = $filepath;
//		$im = new ImageMagick2($filepath);

		$width_in_pixels = 500;
		$height_in_pixels = 200;

//		exec("convert -density 10440 $filepath $filepathDest");
//		die();


// this SHOULD work, but does NOT
		$dpi = 1440;
//		$x_ratio = 4.5;
//		$y_ratio = 4.5;
		$im = new Imagick();
		$im->setResolution($dpi, $dpi);
		$im->readImage($filepath);
//		$im->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
//		$im->setImageResolution($dpi, $dpi);
//		$im->resampleImage  (2*$dpi, 2*$dpi, imagick::FILTER_UNDEFINED, 1);
//		$im->resampleImage  ($x_ratio * $dpi, $y_ratio * $dpi, imagick::FILTER_UNDEFINED, 1);
//		$im->setImageUnits(2);

		$im->setImageFormat("png");
		header("Content-Type: image/png");
		echo $im;
		die();
		
		
		$dpi = 72;
		$x_ratio = 4.5;
		$y_ratio = 4.5;
		$im = new Imagick();
//		$im->setResolution($dpi, $dpi);
		$im->readImage($filepath);
//		$im->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
		$im->setImageResolution($dpi, $dpi);
//		$im->resampleImage  (2*$dpi, 2*$dpi, imagick::FILTER_UNDEFINED, 1);
		$im->resampleImage  ($x_ratio * $dpi, $y_ratio * $dpi, imagick::FILTER_UNDEFINED, 1);
//		$im->setImageUnits(2);
		$im->setImageFormat("png");
		header("Content-Type: image/png");
		echo $im;
		die();
		
		$im = new Imagick();
		$im->readImage($filepath);
		$im->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
		$res = $im->getImageResolution();
		$x_ratio = $res['x'] / $im->getImageWidth();
		$y_ratio = $res['y'] / $im->getImageHeight();
//		dump($x_ratio);
		$im->removeImage();
		$im->setResolution($width_in_pixels * $x_ratio, $height_in_pixels * $y_ratio);
		$im->readImage($filepath);
//		dump($im);
//		die();
		// Now you can do anything with the image, such as convert to a raster image and output it to the browser:
		$im->setImageFormat("png");
		header("Content-Type: image/png");
		echo $im;
		die();


		$im = new Imagick($filepath);
//		$im->resizeImage(100, null, Imagick::FILTER_LANCZOS, 1);
		$im->scaleImage(100, 0, false);
		$im->writeImage($filepathDest);
		$im->clear();
		$im->destroy();
		die();
//		$im = Image::fromFile($filepath);
		$im->resize(800, null, Image::ENLARGE);
		$im->save($filepathDest);
		die();
		
		$im = new imagick($filepath .'[0]');
		$suffix = 'png';
		$this->saveImagickPreview($im, $orig_dirname . FileModel::removeSuffix($data['filename']) . ".$suffix", $suffix, self::BITMAP_ALTERNATIVE_W, self::BITMAP_ALTERNATIVE_H);
		
	}
}