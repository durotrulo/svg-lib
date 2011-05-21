<?php

/**
 * Complexity model
 *
 * @author	Matus Matula
 */
class ComplexityModel extends BaseModel
{
	const TABLE = 'complexity';

	public function findAll()
	{
		return dibi::select('*')
					->from(self::TABLE);
	}
	
	
	public function getTree()
	{
		return dibi::query('select * from %n', self::TABLE)
					->fetchTree('id', 'id_parent');
	}
}