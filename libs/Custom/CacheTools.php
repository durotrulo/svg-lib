<?php

class CacheTools extends Object 
{
	public static function invalidate($type=false, $tags=false, $section = 'data') {
		
		if ($type) {
			$cache = Environment::getCache($type);
			unset($cache[$section]);
		}
		
		// vyexpirujeme všechny položky s tagem 'komentare#10':
		if ($tags) {
			$cache = Environment::getCache();
			$cache->clean(array(
			    'tags' => array($tags),
			));
		}

	}
}