<?php

class RichTextModel extends BaseModel
{
	const TABLE = 'menu';
	
  	public function find($id, $lang = NULL)
  	{
  		if (!is_null($lang)) {
  			$select = "id, data_$lang AS data, title_$lang AS title";
  		} else {
  			$select = '*';
  		}
  		
  		return dibi::select($select)
	            ->from(self::TABLE)
	            ->where('id = %i', $id)
	            ->fetch();
  	}
}
