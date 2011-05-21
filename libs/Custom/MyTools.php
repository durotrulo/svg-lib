<?php

class MyTools extends Object
{
	
	/**
	 * fix for callbacks with referenced parameters - to avoid
	 * 	Call-time pass-by-reference has been deprecated
	 * 	vs.
	 * 	expected to be a reference warning
	 *
	 * @param string|array $cb - vid koment v kode
	 * @param mixed $params
	 * 
	 * @author Matus Matula
	 * @see http://forum.nette.org/cs/4046-object-closures-a-predavani-pole-odkazem
	 */
	public static function fixCallback($cbs, &$params)
	{
		// $cbs = 'functionName' OR $cbs == closure
		if (is_string($cbs) or $cbs instanceof Closure) {
			call_user_func_array($cbs, array(&$params));
		} elseif (is_array($cbs)) {
			// $cbs = array($obj, "methodName") and NOT Closure
			if (is_object($cbs[0]) and !$cbs[0] instanceof Closure) {
				call_user_func_array($cbs, array(&$params));
			// $cbs = array( array($obj, "methodName"), 'functionName', array($obj, "methodName"), closure, ... )
			} else {
				foreach ($cbs as $cb) {
					call_user_func_array($cb, array(&$params));
				}
			}
		}
	}
}