<?php

class RichTextArea extends MyTextArea
{
	/** @var string */
	public $className = 'wysiwyg';

	/** @var string */
	public $encoding = 'UTF-8';

	/** @var string */
//	public $docType = 'XHTML 1.0 Transitional';
//	public $docType = 'XHTML 1.0 Strict';
	public $docType = NULL; // html 5 not supported yet


	/**
	 * Generates control's HTML element.
	 * @return Html
	 */
	public function getControl()
	{
        $control = parent::getControl();
        $control->class = $this->className;

        return $control;
	}


	/**
	 * Prebehneme data HTML purifierom
	 * @param  array
	 * @return void
	 */
	public function loadHttpData()
	{
        $data = $this->getForm()->getHttpData();
        $name = $this->getName();
        $value = isset($data[$name]) && is_scalar($data[$name]) ? $data[$name] : NULL;

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', $this->encoding);
        if (!is_null($this->docType)) {
	        $config->set('HTML.Doctype', $this->docType);
        }
        
        $config->set('HTML.Allowed', 'p,a[href],strong,em,b,i,ul,ol,li,h1,h2,h3,h4,h5,div[class],span[class],br,sup,table[border],tr,td,th,thead,tbody');
//        $config->set('HTML.Allowed', 'p,a[href],strong,em,ul,ol,li,h1,h2,div[class],span[class],br,sup');
//        $config->set('HTML.Allowed', 'p,a[href],strong,em,ul,ol,li,h2,h3,h4,h5');

 
		// povoli lubovolny obsah pre href atribut odkazu - aby sa dali vyuzit latte links
		$config->set('HTML.DefinitionID', 'enduser-customize.html tutorial');
//        $config->set('HTML.DefinitionRev', 1);
//        $config->set('Cache.DefinitionImpl', null); // remove this later!
		$def = $config->getHTMLDefinition(true);
                $def->addAttribute('a', 'href*', 'Text');

                
        $purifier = new HTMLPurifier($config);

//        var_dump($value);

//		 kedze CKEDITOR to escapuje a neviem ho prinutit aby to nerobil, tak to tu dam naspat, Purifier to nasledne aj tak spravne zescapuje 
//        $value = html_entity_decode($value);
//        var_dump($value);
        
        $this->setValue($purifier->purify($value));
	}


	/**
	 * Filled validator: is control filled?
	 * @param  IFormControl
	 * @return bool
	 */
//	public static function validateFilled(IFormControl $control)
//	{
////		die('dsa');
//		return false; // NULL, FALSE, '' ==> FALSE
//		return (string) $control->getValue() !== ''; // NULL, FALSE, '' ==> FALSE
//	}
}