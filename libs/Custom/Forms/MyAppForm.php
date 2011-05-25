<?php

/**
 * rozsiruje AppForm o
 * 	kontrolu velkosti odoslanych dat .. ak su vacsie ako post_max_size, hodi chybu
 * 	moznost zobrazit chyby formularov ako flash spravy presenteru
 * 	RichTextArea
 * 	FileInput - kontroluje spravny prijem suboru a dovoluje kontrolu pripony cez REGEXP
 *  moznost klientskej validacie formularov poslanych cez AJAX [metody render a __toString]
 *
 */
class MyAppForm extends AppForm {

	// allows check for required suffix of uploaded file 
	// todo: change to EXTENSION or FILE_EXT or sth. similar?
	const SUFFIX = ':hasSuffix';

	const BLOCK_RENDER_MODE = 1;
	
	const AJAX_CLASS = 'ajax';
	
	
	public static $ajaxFileUploadEnabled = false;
	
	
	/**
	 * Application form constructor.
	 */
	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
		Rules::$defaultMessages = array(
			self::MIN_LENGTH => 'Minimum length of %label is %d chars!',
			self::MAX_LENGTH => 'Maximum length of %label is %d chars!',
			self::FILLED => 'Enter %label!',
			self::EMAIL => 'Enter valid email address!',
			
		);
		
	}

	
	/**
	 * Enables client validation for forms loaded via AJAX
	 * appends script to each form, required for Nette 1.0
	 * appends script to forms with class="useUICss" calling applyUIForm()
	 * 
	 * todo: temporary? David should fix it natively
	 * @return void
	 */
	public function render()
	{
		$args = func_get_args();
		call_user_func_array('parent::render', $args);

		$js = '';
		if ($this->getPresenter()->isAjax()) {
			$js .= 'Nette.initForm(document.getElementById("' . $this->getElementPrototype()->id . '"));';
		}
		
		if (strchr($this->getElementPrototype()->class, 'useUICss')) {
			$js .= '$(function(){applyUItoForm("' . $this->getElementPrototype()->id . '")});';
		}
		
		if (!empty($js)) {
			echo '
				<script type="text/javascript">
					' . $js . '
				</script>
			';
		}
		
		if (self::$ajaxFileUploadEnabled) {
			$basePath = Environment::getApplication()->getPresenter()->getTemplate()->basePath;
			echo '
			<!--	FILE UPLOAD-->

				<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/themes/base/jquery-ui.css" id="theme">
			<!--	<link rel="stylesheet" href="' . $basePath . '/modules/file-upload/jquery.fileupload-ui.css">-->
				<link rel="stylesheet" href="' . $basePath . '/modules/file-upload/jquery.fileupload-ui-admin.css">
			
			<!--	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>-->
				<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/jquery-ui.min.js"></script>
				<script src="' . $basePath . '/modules/file-upload/jquery.fileupload.js"></script>
				<script src="' . $basePath . '/modules/file-upload/jquery.fileupload-ui.js"></script>
				<script src="' . $basePath . '/modules/file-upload/jquery.fileupload-uix.js"></script>
				<script src="' . $basePath . '/modules/file-upload/admin-inline.js"></script>
				
			<!--	FILE UPLOAD END-->
			';
		}
	}
	
	/**
	 * Renders form to string.
	 * @return bool  can throw exceptions? (hidden parameter)
	 * @return string
	 */
	public function __toString()
	{
		ob_start();
		$this->render();
		return ob_get_clean();
	}
	
	
	/**
	 * enable/disable ajax mode by adding HTML class 'ajax'
	 *
	 */
	public function enableAjax($enable = true)
	{
		if ($enable) {
			$this->addClass(self::AJAX_CLASS);
		} else {
			$this->removeClass(self::AJAX_CLASS);
		}
	}
	
	
	/**
	 * enable ajax file upload using www_module
	 *
	 * @param bool
	 */
	public function enableAjaxFileUpload($enable = true)
	{
		// form can't be handled automatically by jquery with class='ajax'
		if ($enable && !self::$ajaxFileUploadEnabled) {
//			$this->enableAjax(false);
			self::$ajaxFileUploadEnabled = true;
//		} elseif ($enable === false) {
//			self::$ajaxFileUploadEnabled = false;
		}
	}
	
	
	public function resetValues()
	{
		// reset form values
		foreach ($this->getControls() as $v) {
			$v->value = '';
		}
	}
	
	
	/**
	 * enables concatenation of form HTML classes
	 *
	 * @param string
	 */
	public function addClass($class)
	{
		$origClass = $this->getElementPrototype()->class;
		$this->getElementPrototype()
			->class(empty($origClass) ? $class : ($origClass . ' ' . $class));
	}
	
	
	/**
	 * removes form HTML class
	 *
	 * @param string
	 */
	public function removeClass($class)
	{
		$this->getElementPrototype()->class(preg_replace("/\s*$class\s*/", '', $this->getElementPrototype()->class));
	}
	
	
	/**
	 * spam protection inspired by Jakub Vrana
	 * creates text input, fills it in by javascript and hides it from users .. based on robot's javascript handling disability
	 * @see http://php.vrana.cz/ochrana-formularu-proti-spamu.php
	 */
	public function addSpamProtection($name = 'nospam', $label = 'Fill in „nospam“')
	{
		$noSpam = $this->addText($name, $label)
                        ->addRule(Form::FILLED, 'You are a spambot!')
                        ->addRule(Form::REGEXP, 'You are a spambot!', '/^nospam$/i');

        $noSpam->getLabelPrototype()->class('nospam');
        $noSpam->getControlPrototype()->class('nospam');
        
        return $noSpam;
	}

	
	
	
	public function setErrorsAsFlashMessages()
	{
		$this->onInvalidSubmit[] = array(__CLASS__, 'errorsAsFlashMessages');
	}
	
	
	/**
	 * chyby vo formulari zobrazi ako flash a chyby zmaze
	 *
	 * @param AppForm $form
	 */
	public static function errorsAsFlashMessages(MyAppForm $form)
	{
		$errors = $form->getErrors();
		foreach ($errors as $error) {
			Environment::getApplication()->getPresenter()->flashMessage($error, 'error');
		}
		$form->cleanErrors();
	}
	
	
	protected function receiveHttpData()
	{
		//	kontrolujem odoslane data tu,
		//	lebo sa mi to nedostane do spracovania,
		//	ked odoslane data presiahnu max. limit [napr. odosle prilis velky subor]
		$maxSize = MyFileUpload::convertToBytes(ini_get('post_max_size'));

		if ( isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > $maxSize) {

			//TODO translate
			$errMsg = 'Sent data were larger than '
				. ini_get('post_max_size')
				. 'B, you have probably tried to upload too large file';

			$this->addError($errMsg);

		}

		return parent::receiveHttpData();
	}

	/**
	 * This method is called by presenter.
	 * Enables AJAX validation
	 * @param  string
	 * @return void
	 */
	public function signalReceived($signal)
	{
        if ($signal === 'submit') {
                $this->fireEvents();
        } else if ($signal == 'validate') {
                $post = $this->presenter->request->post;
                if (!isset($post['name']) || !isset($post['value'])) {
                    throw new BadSignalException('Bad POST data format.');
                }
                $control = $this[$post['name']];
                $control->value = $post['value'];
                $control->rules->validate();
                $this->presenter->terminate(new JsonResponse(array('errors' => $control->errors)));
        } else {
            throw new BadSignalException("There is no handler for signal '$signal' in {$this->reflection->name}.");
        }
	}
	
	public function addRichTextArea($name, $label = NULL, $cols = 40, $rows = 10)
	{
		$this[$name] = new RichTextArea($label, $cols, $rows);
		$this->getElementPrototype()->onsubmit .= 'CKEDITOR.instances["'.$this[$name]->getHtmlId().'"].updateElement();';
		return $this[$name];
	}

	
//	public function addFile($name, $label = NULL, $maxFileSize = NULL) {
	public function addFile($name, $label = NULL)
	{
		return $this[$name] = new MyFileUpload($label);
	}
	
	
	public function addCaptcha($name, $question = NULL, $ruleMessage = NULL)
	{
		return $this[$name] = new CaptchaInput($question, $ruleMessage);
	}
	
	
	/**
	 * Adds naming container to the form.
	 * @param  string  name
	 * @return FormContainer
	 */
	public function addContainer($name)
	{
		$control = new MyFormContainer;
		$control->currentGroup = $this->currentGroup;
		return $this[$name] = $control;
	}


	public function setCustomRenderer($type, $className = NULL)
	{
		if ($type == self::BLOCK_RENDER_MODE) {
			$renderer = $this->renderer;
			$renderer->wrappers['form']['container'] = Html::el('div')->class('section-padding' . ($className ? " $className" : ''));
			$renderer->wrappers['pair']['container'] = NULL;
			$renderer->wrappers['controls']['container'] = 'dl';
			$renderer->wrappers['control']['container'] = 'dd';
			$renderer->wrappers['control']['.odd'] = 'odd';
			$renderer->wrappers['label']['container'] = 'dt';
//			$renderer->wrappers['label']['suffix'] = ':';
//			$renderer->wrappers['label']['requiredsuffix'] = " *";
		}
	}

	
	
	/****************	podedenie kvoli custom :filled pravidlu	*********************/
	

	/**
	 * Adds check box control to the form.
	 * @param  string  control name
	 * @param  string  caption
	 * @return Checkbox
	 */
	public function addCheckbox($name, $caption = NULL)
	{
		return $this[$name] = new MyCheckbox($caption);
	}


	/**
	 * Adds select box control that allows single item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   items from which to choose
	 * @param  int     number of rows that should be visible
	 * @return SelectBox
	 */
	public function addSelect($name, $label = NULL, array $items = NULL, $size = NULL)
	{
		return $this[$name] = new MySelectBox($label, $items, $size);
	}


	/**
	 * Adds select box control that allows multiple item selection.
	 * @param  string  control name
	 * @param  string  label
	 * @param  array   options from which to choose
	 * @param  int     number of rows that should be visible
	 * @return MultiSelectBox
	 */
	public function addMultiSelect($name, $label = NULL, array $items = NULL, $size = NULL)
	{
		return $this[$name] = new MyMultiSelectBox($label, $items, $size);
	}
}