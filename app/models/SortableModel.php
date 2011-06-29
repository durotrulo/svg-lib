<?php

class SortableModel extends BaseModel implements ISortableModel {

	/** @var string table name in database */
	private $table;

	/** @var string primary key of table in database */
	public $primaryKey;

	/** @var string column name that holds sorting position */
	public $sortingKey;
	
	
	public function __construct($table, $primaryKey = 'id', $sortingKey = 'sort')
	{
		$this->table = $table;
		$this->primaryKey = $primaryKey;
		$this->sortingKey = $sortingKey;
	}
	
	
  	public function saveOrder($sortedItems)
  	{
  		if (!is_array($sortedItems) || empty($sortedItems)) {
  			throw new ArgumentOutOfRangeException("Parameter 'sortedItems' must be non-empty array!");
  		}
  		
		if (!Basic::isPlusNumeric($sortedItems)) {
			throw new ArgumentOutOfRangeException('id must be positive integer!');
		}
		
		try {
			dibi::begin();
			foreach ($sortedItems as $k=>$id) {
				dibi::update($this->table, array($this->sortingKey => $k+1))
					->where('%n = %i', $this->primaryKey, $id)
					->execute();
			}
			dibi::commit();
		// rollback a vyhodim pre dalsie spracovanie vyssou vrstvou
		} catch (DibiException $e) {
	        dibi::rollback();
	        throw $e;
		}
		
		return true;
  	}

}
