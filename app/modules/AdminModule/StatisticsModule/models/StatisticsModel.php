<?php

/**
 * Statistics model
 *
 * @author	Matus Matula
 */
class StatisticsModel extends BaseModel
{
	const TABLE = 'complexity';

	public function findProjectStats()
	{
		return dibi::select('
				p.name,
				COUNT(al.id) AS total,
				COUNT(al2.id) AS month,
				COUNT(al3.id) AS quartal,
				COUNT(al4.id) AS year
			')
			->from(self::PROJECTS_TABLE)
				->as('p')
			->leftJoin(self::ACTIVITY_LOG_TABLE)
				->as('al')
				->on('p.id = al.projects_id')
			->leftJoin(self::ACTIVITY_LOG_TABLE)
				->as('al2')
				->on('al.id = al2.id AND DATEDIFF(CURDATE(), al2.dt) < 30')
			->leftJoin(self::ACTIVITY_LOG_TABLE)
				->as('al3')
				->on('al.id = al3.id AND DATEDIFF(CURDATE(), al3.dt) < 120')
			->leftJoin(self::ACTIVITY_LOG_TABLE)
				->as('al4')
				->on('al.id = al4.id AND DATEDIFF(CURDATE(), al4.dt) < 365')
			->groupBy('al.projects_id')
			->fetchAll();
	}
	
	
	
}