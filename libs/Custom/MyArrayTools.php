<?php

class MyArrayTools extends Object
{

	/**
	 * append $arr2 at the end of $arr1
	 * keep indexes intact
	 *
	 * @param unknown_type $arr1
	 * @param unknown_type $arr2
	 */
	public static function mergeArrays($arr1, $arr2)
	{
		foreach ($arr2 as $v) {
			$arr1[] = $v;
		}
		
		return $arr1;
	}
	
	
	/**
	 * multiplies each element of $arr by scalar $multiplier
	 *
	 * @param array $arr
	 * @param float $multiplier
	 * @return array
	 */
	public static function arrayScalarMultiply($arr, $multiplier)
	{
		
		/**
		 * ak vratim hned $arr, tak je spotreba pre 16 userov 7.05MB
		 * prazdny cyklus cez referenciu sam o sebe zozerie 354MB (foreach ($arr as $k => &$v))
		 * ked idem cez cyklus normalny a nasobim multiplierom, tak to tiez zozerie 354MB - ked iba cisto priradujem hodnotu, tak to zozerie 226MB
		 */
		
		foreach ($arr as $k => $v) {
			$arr[$k] = $v * $multiplier;
		}
		return $arr;
	}
	

	/**
	 * multiplies 2 matrixes MxN with NxL, results in MxL matrix
	 *
	 * @param array MxN
	 * @param array NxL
	 * @return array MxL
	 */
	public static function multiplyMatrixes($a, $b)
	{
		$nrA = count($a);
		$ncA = count($a[1]);
		
		$nrB = count($b);
		$ncB = count($b[1]);

		if ($ncA != $nrB) {
            throw new InvalidArgumentException('Incompatible matrix dimensions, number of columns in matrix A must be the same as number of rows in matrix B');
        }
        
		$data = array();
        for ($i=1; $i <= $nrA; $i++) {
            $data[$i] = array();
            for ($j=1; $j <= $ncB; $j++) {
                $rctot = 0;
                for ($k=1; $k <= $ncA; $k++) {
                    $rctot += $a[$i][$k] * $b[$k][$j];
                }
                // take care of some round-off errors
//                if (abs($rctot) <= $this->_epsilon) {
//                    $rctot = 0.0;
//                }
                $data[$i][$j] = $rctot;
            }
        }
        
        return $data;
	}
	
	
	

	/**
	 * changes array's indices - not starting from 0 but from 1
	 *
	 * @param array $arr
	 */
	public static function shiftArrIndex(&$arr)
	{
		array_unshift($arr, 'mock');
		unset($arr[0]);
	}

	
	/**
	 * formats array using $glue and $seps
	 *
	 * @param array multidimensional
	 * @param string glue for array items at lowest level
	 * @param array of separators for various levels
	 * @param int
	 * @return string formatted
	 */
	public static function format($arr, $glue = ', ', $seps = array('<br><br>', '<br>'), $level = 0)
	{
		$ret = '';
		foreach ($arr as $v) {
			// multidimensional
			if (is_array($v[1])) {
				$ret .= self::format($v, $glue, $seps, ++$level);
			} elseif (is_array($v)) {
				$ret .= join($glue, $v);
			} else {
				throw new LogicException('nejaka chyba');
			}
			$sep = isset($seps[$level]) ? $seps[$level] : end($seps);
			$ret .= $sep;
		}
		
		return $ret;
	}
}