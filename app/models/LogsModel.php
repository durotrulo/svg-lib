<?php

/**
 * LogsModel
 *
 * @author	Matus Matula
 */
class LogsModel extends BaseModel
{
	const TABLE = self::ACTIVITY_LOG_TABLE;
	
	/** @dbsync */
	const ACTION_CREATE = 'create'; //file, project, tag, lightbox
	const ACTION_ASSOCIATE = 'associate'; // tag 2 file, file 2 lightbox, ...
	const ACTION_UNBIND = 'unbind'; // tag 2 file, file 2 lightbox, ...
	const ACTION_SHARE = 'share'; // lightbox
	const ACTION_DOWNLOAD = 'download'; // file, lightbox
	const ACTION_DELETE = 'deleted'; // file, lightbox, project, tag
	
	
	/**
	 * insert
	 *
	 * @param array $data
	 * @return int insertedId()
	 */
	public function insert(array $data)
	{
		$data['dt'] = dibi::datetime();
		return parent::insert($data);
	}

	
}