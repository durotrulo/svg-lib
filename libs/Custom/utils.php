<?php



/**
 * vytvori stromovu strukturu
 * 
 * array(5) {
	   1 => array(3) {
	      "id_parent" => NULL
	      "id" => "1"
	      "name" => "All levels" (10)
	   }
	   2 => array(4) {
	      "id_parent" => NULL
	      "id" => "2"
	      "name" => "Icon" (4)
	      "children" => array(2) {
	         3 => array(3) { ... }
	         4 => array(3) { ... }
	      }
	   }
   }
 *
 * @param DibiResult $obj
 * @param string $id
 * @param string $parent_id
 * @return array
 * 
 * @see http://forum.dibiphp.com/cs/634-fetchtree
 */
function DibiResult_prototype_fetchTree(DibiResult $obj, $id, $parent_id) {

  $obj->seek(0);
  $row = $obj->fetch(FALSE);
  if (!$row) return array();  // empty resultset

  $refs = array();
  $data = NULL;

  if(!array_key_exists($id,$row) ||
       !array_key_exists($parent_id,$row)) {
    throw new InvalidArgumentException("Column '$id' or '$parent_id' is not in record");
  }

  do {
    $ref = &$refs[$row[$id]];

    $ref[$parent_id] = $row[$parent_id];
    $ref = array_merge($ref, (array) $row);

    if ($row[$parent_id] == NULL) {
       $data[$row[$id]] = &$ref;
    } else {
       $refs[$row[$parent_id]]['children'][$row[$id]] = &$ref;
    }

  } while($row = $obj->fetch(FALSE));

  unset($ref);
  unset($refs);
  return $data;
}