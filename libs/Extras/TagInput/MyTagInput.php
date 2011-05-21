<?php

/**
 * doplnene o 
 * 		podporu asociativnych poli pre nastavenie nasepkavaca - setSuggest a getValue
 * 		validaciu minimalneho poctu pridanych novych tagov
 * 		validaciu maximalneho poctu pridanych novych tagov
 * 
 *  kedze pracuje s polom namiesto s retazcami, menia sa aj validacne pravidla co sa tyka dlzky (length, minlength, maxlength) na [size, minsize, maxsize]
 * 	-> aby sa nenastavoval html atribut maxlength, pretoze bez povoleneho JS by browser nepovolil viac zadat do inputu
 * 
 *  prepisuje sanitize(), lebo v povodnom to rozmrda pri getValue() - vzdy sa vrati pole, @see http://forum.nette.org/cs/6117-addon-tagcontrol-tagcontrol#p54596
 * 
 *  private vars 
 *	 		$renderName
 * 			$payloadLimit
 * 			$delimiter
 *	 		$suggest
 * 		changed to protected
 * 
 * 		$form->addTag('tags', 'Tagy', $this->getTagsModel()->fetchPairs())
		 	->addRule(Form::FILLED, 'Zadajte tag!')
		 	->addRule(MyTagInput::UNIQUE, 'Tagy sa nesmú opakovať!')
		 	->addRule(MyTagInput::MIN_SIZE, 'K vtipu priraďte aspoň %d tag!', $minTagsCount)
		 	->addRule(MyTagInput::MAX_SIZE, 'K vtipu môžete priradiť maximálne %d tagy', $maxTagsCount)
		 	->addRule(MyTagInput::CREATED_MAX_COUNT, 'Môžete pridať maximálne %d nový tag.', $maxCreatedTagsCount)
		 	->setDelimiter('[,]')
		 	->setOption('description', 
//		 		Html::el('ul')
//		 			->create('li')
		 				->setHtml(
		 				sprintf('K vtipu priraďte aspoň %d a maximálne %d tagy. Taktiež môžete priradiť %d nový tag. Jednotlivé tagy oddeľujte čiarkou.', 
		 					$minTagsCount,
		 					$maxTagsCount,
		 					$maxCreatedTagsCount
						)
			)
 * 
 */

/*
namespace Nette\Forms;

use Nette\Forms\Form;
use Nette\Forms\TextInput;
use Nette\String;
*/

class MyTagInput extends TagInput
{
	/** @var string rule */
	const CREATED_MIN_COUNT = ':createdMinCount';

	/** @var string rule */
	const CREATED_MAX_COUNT = ':createdMaxCount';

	/** @var string rule */
	const MIN_SIZE = ':minSize';

	/** @var string rule */
	const MAX_SIZE = ':maxSize';

	/** @var string rule */
	const SIZE = ':size';


	/** @var string param name for actionTagInputSuggestControlname($paramName) */
	private $paramName = 'tagFilter';

	
	public function setParamName($v)
	{
		$this->paramName = (string) $v;
	}
	
	
	/**
	 * overrides TagInput::sanitize(), @see http://forum.nette.org/cs/6117-addon-tagcontrol-tagcontrol#p54596
	 * @return string
	 */
	public function sanitize($value)
	{
		return $value;
	}
	
	
	/**
	 * @return array
	 */
	public function getValue()
	{	
		// unset html attribute 'maxlength' as TextInput::sanitize would handle it as String and truncate it to maxlength
		unset($this->control->maxlength);
		
		// omit TagInput::getValue(), use TextInput instead
		$value = String::split(TextInput::getValue(), "\x01" . $this->delimiter . "\x01");

		// trim spaces - if spaces are allowed inside tag it is NOT handled by delimiter
		foreach ($value as &$v) {
			$v = trim($v);
		}
		
		if ($value[0] == '' && count($value) === 1) {
			$value = array();
		} else {
			// get associated array of suggested tags
			$suggestedValue = array_intersect($this->suggest, $value);
			$newTags = array_diff($value, $suggestedValue);
			
			// append newTags at the end of $suggestedValue
			$value = MyArrayTools::mergeArrays($suggestedValue, $newTags);
		}
		return $value;
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = parent::getControl();
		if (count($this->suggest) !== 0) {
			$control->attrs['data-tag-suggest'] = Environment::getApplication()->getPresenter()->link(self::$renderName, array($this->paramName => '%__filter%'));
		}
	
		return $control;
	}


	/**
	 * @param array $suggest
	 * @return TagInput provides fluent interface
	 */
	public function setSuggest(array $suggest)
	{
		$this->suggest = array();
		foreach ($suggest as $k => $tag) {
			if (!is_scalar($tag) && !method_exists($tag, '__toString')) {
				throw new InvalidArgumentException(__CLASS__ . ' can only use autocomplete with scalar values or objects with defined conversion to string.');
			}
			if (!array_search($tag, $this->suggest)) {
				$this->suggest[$k] = $tag;
			}
		}
		return $this;
	}


	public function renderResponse($presenter, $filter)
	{
		$data = array();
		foreach ($this->suggest as $tag) {
			if (String::startsWith(String::lower($tag), String::lower($filter))) {
				$data[] = $tag;
			}
			if (count($data) >= $this->payloadLimit) {
				break;
			}
		}
//		$presenter->sendResponse(new JsonResponse($data));
		$presenter->terminate(new JsonResponse($data));
	}


	/**
	 * get new-added tags [not suggested]
	 *
	 * @return array
	 */
	public function getNewTags()
	{
		return array_diff($this->getValue(), $this->suggest);
	}

	/********************* registration *******************/



	/**
	 * Adds addTag() method to \Nette\Forms\Form
	 */
	public static function register()
	{
		Form::extensionMethod('addTag', callback(__CLASS__, 'addTag'));
	}



	/**
	 * @param Form $form
	 * @param string $name
	 * @param string $label
	 * @param array $suggest
	 * @return TagInput provides fluent interface
	 */
	public static function addTag(Form $form, $name, $label = NULL, $suggest = NULL)
	{
		self::$renderName = 'tagInputSuggest' . ucfirst($name);

		$form[$name] = new self($label);
		$form[$name]->setSuggest($suggest === NULL ? array() : $suggest);
		return $form[$name];
	}



	/********************* validation *********************/


	/**
	 * Min-length validator: has control's value minimal length?
	 * @param  TextBase
	 * @param  int  length
	 * @return bool
	 */
	public static function validateMinLength(TextBase $control, $length)
	{
		throw new LogicException(':MIN_LENGTH validator is not applicable to TagInput.');
	}


	/**
	 * Max-length validator: is control's value length in limit?
	 * @param  TextBase
	 * @param  int  length
	 * @return bool
	 */
	public static function validateMaxLength(TextBase $control, $length)
	{
		throw new LogicException(':MAX_LENGTH validator is not applicable to TagInput.');
	}


	/**
	 * Length validator: is control's value length in range?
	 * @param  TextBase
	 * @param  array  min and max length pair
	 * @return bool
	 */
	public static function validateLength(TextBase $control, $range)
	{
		throw new LogicException(':LENGTH validator is not applicable to TagInput.');
	}

	
	/**
	 * Min-size validator: has control's value minimal items count?
	 * @param  TextBase
	 * @param  int  size
	 * @return bool
	 */
	public static function validateMinSize(TextBase $control, $size)
	{
		return count($control->getValue()) >= $size;
	}


	/**
	 * Max-size validator: is control's value items count in limit?
	 * @param  TextBase
	 * @param  int  size
	 * @return bool
	 */
	public static function validateMaxSize(TextBase $control, $size)
	{
		return count($control->getValue()) <= $size;
	}


	/**
	 * Length validator: is control's value length in range?
	 * @param  TextBase
	 * @param  array  min and max length pair
	 * @return bool
	 */
	public static function validateSize(TextBase $control, $range)
	{
		if (!is_array($range)) {
			$range = array($range, $range);
		}
		$size = count($control->getValue());
		return ($range[0] === NULL || $size >= $range[0]) && ($range[1] === NULL || $size <= $range[1]);
	}

	
	/**
	 * validates minimal count of new created tags
	 * @param  TagInput
	 * @param  int
	 * @return bool
	 */
	public static function validateCreatedMinCount(TagInput $control, $count)
	{
		return count($control->getNewTags()) >= $count;
	}
	
	
	/**
	 * validates maximal count of new created tags
	 * @param  TagInput
	 * @param  int
	 * @return bool
	 */
	public static function validateCreatedMaxCount(TagInput $control, $count)
	{
		return count($control->getNewTags()) <= $count;
	}
	
}
