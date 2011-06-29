<?php

/**
 * 
 *
 * @author Matus Matula
 */
interface ISortableModel
{
	
	/**
	 * @param  array of sorted indexes
	 * @return void
	 */
	public function saveOrder($sortedItems);

}
