<?php

class MySelectBox extends SelectBox
{
	/** @var bool used in getControl() */
	private $selected = FALSE;
	
	
	/**
	 * Adds a validation rule.
	 * Adds default error messages
	 * 
	 * @param  mixed      rule type
	 * @param  string     message to display for invalid data
	 * @param  mixed      optional rule arguments
	 * @return FormControl  provides a fluent interface
	 */
	public function addRule($operation, $message = NULL, $arg = NULL)
	{
		if ($operation === ':filled' and is_null($message)) {
			$message = 'Choose %label';
		}
		
		return parent::addRule($operation, $message, $arg);
	}
	
	
	/**
	 * Generates control's HTML element.
	 * @return Html
	 */
	public function getControl()
	{
		$control = FormControl::getControl();
		
		// skopirovany kod z SelectBox.php
		if ($this->isFirstSkipped()) {
			$items = $this->items;
			reset($items);
			$control->data['nette-empty-value'] = $this->areKeysUsed() ? key($items) : current($items);
		}
		$selected = $this->getValue();
		$this->selected = is_array($selected) ? array_flip($selected) : array($selected => TRUE);

		$this->formatOptions($this->items, $control);
		return $control;
	}
	
	
	/**
	 * allows 'nested options' with only 1 html optgroup (valid)
	 * options are indented by 4 spaces per level
	 *
	 * @param array
	 * @param FormControl
	 * @param int nesting level
	 */
	private function formatOptions($options, &$control, $level = 1)
	{
		$option = Html::el('option');
		$indentation = str_repeat("&nbsp;", ($level-1)*4);
		
		foreach ($options as $key => $value) {
			if (!is_array($value)) {
				$value = array($key => $value);
				$dest = $control;

			} else {
				// no nested optgroups allowed -> using disabled options indented by space
				if ($level > 1) {
					$dest = $control;
					// simulate optgroup
					if (is_array($value)) {
						$dest->add((string) $option->disabled(true)->setHtml($this->translate($key)));
					}
				} else {
					$dest = $control->create('optgroup')->label($key);
				}
			}

			foreach ($value as $key2 => $value2) {
				if ($value2 instanceof Html) {
					$dest->add((string) $value2->selected(isset($this->selected[$key2])));

				// multidimensional array -> nested optgroups - must by styled with CSS to see hierarchy
				} elseif (is_array($value2)) {
					$this->formatOptions(array($key2 => $value2), $dest, $level+1);
					
				} elseif ($this->areKeysUsed()) {
					$dest->add((string) $option->disabled(false)
												->value($key2)
												->selected(isset($this->selected[$key2]))
												->setText($indentation . $this->translate($value2))
					);

				} else {
					$dest->add((string) $option->disabled(false)
												->selected(isset($this->selected[$value2]))
												->setText($indentation . $this->translate($value2))
					);
				}
			}
		}
	}
}
