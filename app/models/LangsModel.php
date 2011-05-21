<?php

class LangsModel extends BaseModel {

	public static $supportedLangs; // filled in BasePresenter::setSupportedLangs
	private static $langsSelect; // filled in BasePresenter::setSupportedLangs
	
  	public static function getById($id)
  	{
    	return $row = dibi::select('*')
            ->from('languages')
            ->where('id = %i', $id)
            ->fetch();
  	}

  	public static function getPairs()
  	{
	    return $rows = dibi::select('id, name')
            ->from('languages')
            ->orderBy('sort ASC')
            ->fetchPairs('id', 'name');
  	}
  	
  	public static function getAll()
  	{
	    return $rows = dibi::select('id, name, lang')
            ->from('languages')
            ->orderBy('sort ASC')
            ->fetchAll();
  	}
  	
  	public static function getAllForSelect($firstOption = 'user language')
  	{
  		if (is_array(self::$langsSelect)) {
  			$vals = self::$langsSelect;
  		} else {
  			$vals = self::getPairs();
  		}
  		
	    return self::prepareSelect($vals, $firstOption);
  	}
  	
  	public static function setLangsSelect($vals)
  	{
  		self::$langsSelect = $vals;
  	}
  	
  	public static function isAllowed($lang)
	{
		return in_array($lang, self::$supportedLangs);
	}
}
