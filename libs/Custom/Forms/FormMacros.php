<?php

//namespace Nette\Templates;

//use Nette\Forms\Form;
//use Nette\String;

/**
 * Form macros
 * 
 * oproti povodnemu som pridal funkcionalitu pre 
 * 		labelSuffix			{labelSuffix ':'}
 * 		requiredSuffix		{requiredSuffix $reqSuffix} pricom $reqSuffix moze byt HTML element, $tpl->reqSuffix = Html::el('span')->class('red')->setHtml('*');
 * 		requiredPrefix		{requiredPrefix $reqPrefix}
 * 
 * pouzitelne aj pre prvky vo FormContaineri
 * 
 * function createComponentForm(){
 *      $form = new Form;
 *      $form->addContainer('container')
 *           ->addText('inputText','xx');
 * }
 * 
 * Sablona: {input container.inputText}
 * 
 * @see http://forum.nette.org/cs/4286-makra-pro-rucni-vykreslovani-formularu?p=2
 * @author Jan Marek
 * @license MIT
 */
class FormMacros extends /*\Nette\*/Object {

        public static $form;
        public static $requiredSuffix;
        public static $requiredPrefix;
        public static $labelSuffix;


        public function __construct() {
        throw new /*\*/InvalidStateException("Static class.");
        }


        public static function register() {
        /*      LatteMacros::$defaultMacros["form"] = '<?php %Nette\Templates\FormMacros::macroBegin% ?>';
                LatteMacros::$defaultMacros["input"] = '<?php %Nette\Templates\FormMacros::macroInput% ?>';
                LatteMacros::$defaultMacros["label"] = '<?php %Nette\Templates\FormMacros::macroLabel% ?>';
                LatteMacros::$defaultMacros["/form"] = '<?php Nette\Templates\FormMacros::end() ?>'; */

                LatteMacros::$defaultMacros["form"] = '<?php %FormMacros::macroBegin% ?>';
                LatteMacros::$defaultMacros["input"] = '<?php %FormMacros::macroInput% ?>';
                LatteMacros::$defaultMacros["label"] = '<?php %FormMacros::macroLabel% ?>';
                LatteMacros::$defaultMacros["desc"] = '<?php %FormMacros::macroDescription% ?>';
                LatteMacros::$defaultMacros["/form"] = '<?php FormMacros::end() ?>';
                LatteMacros::$defaultMacros["requiredSuffix"] = '<?php %FormMacros::macroRequiredSuffix% ?>';
                LatteMacros::$defaultMacros["requiredPrefix"] = '<?php %FormMacros::macroRequiredPrefix% ?>';
                LatteMacros::$defaultMacros["labelSuffix"] = '<?php %FormMacros::macroLabelSuffix% ?>';
        }


        public static function macroBegin($content) {
                list($name, $modifiers) = self::fetchNameAndModifiers($content);
                //return "\$formErrors = Nette\Templates\FormMacros::begin($name, \$control, $modifiers)->getErrors()";
                return "\$formErrors = FormMacros::begin($name, \$control, $modifiers)->getErrors()";
//                return "
//                	\$formErrors = FormMacros::begin($name, \$control, $modifiers)->getErrors();
//	                \$formControls = FormMacros::begin($name, \$control, $modifiers)->getControls()";
        }


        public static function begin($form, $control, $modifiers = array()) {
                if ($form instanceof Form) {
                        self::$form = $form;
                } else {
                        self::$form = $control[$form];
                }

                if (isset($modifiers["class"])) {
                        self::$form->getElementPrototype()->class[] = $modifiers["class"];
                }

                self::$form->render("begin");

                return self::$form;
        }


        public static function end() {
                self::$form->render("end");
        }


        public static function macroInput($content) {
                list($name, $modifiers) = self::fetchNameAndModifiers($content);
                //return "Nette\Templates\FormMacros::input($name, $modifiers)";
                return "FormMacros::input($name, $modifiers)";
        }


        public static function input($name, $modifiers = array()) {
//                $input = self::$form[$name]->getControl();
                $input = self::getComponentFromName($name)->getControl();
                
                if (isset($modifiers["size"])) {
                        $input->size($modifiers["size"]);
                }

                if (isset($modifiers["rows"])) {
                        $input->rows($modifiers["rows"]);
                }

                if (isset($modifiers["cols"])) {
                        $input->cols($modifiers["cols"]);
                }

                if (isset($modifiers["class"])) {
                        $input->class[] = $modifiers["class"];
                }

                if (isset($modifiers["style"])) {
                        $input->style($modifiers["style"]);
                }

                //a místo {input ok text => "Odeslat formulář"} píšu {input ok value => "Odeslat formulář"}
                if (isset($modifiers["value"])) {
				    $input->value($modifiers["value"]);
				}
                echo $input;
        }


        public static function macroLabel($content) {
                list($name, $modifiers) = self::fetchNameAndModifiers($content);
                //return "Nette\Templates\FormMacros::label($name, $modifiers)";
                return "FormMacros::label($name, $modifiers)";
        }
        

        public static function label($name, $modifiers = array()) {
                $label = self::getComponentFromName($name)->getLabel();
                
                if (isset($modifiers["text"])) {
                        $label->setText($modifiers["text"]);
                }
                
                if (isset($modifiers["html"])) {
                        $label->setHtml($modifiers["html"]);
                }

                if (isset($modifiers["class"])) {
                        $label->class[] = $modifiers["class"];
                }

                if (isset($modifiers["style"])) {
                        $label->style($modifiers["style"]);
                }

                if (self::getComponentFromName($name)->getOption('required')) {
//                	$label->setHtml(self::$requiredPrefix . $label->getText() . self::$requiredSuffix);
                	$label->setHtml(self::$requiredPrefix . $label->getHtml() . self::$requiredSuffix);
                }
                
                if (isset(self::$labelSuffix)) {
                	$label->setHtml($label->getHtml() . self::$labelSuffix);
                }
                
//                if (self::$form[$name]->getOption('description')) {
//                	$label->setHtml($label->getHtml() . '<br /><small>' . self::$form[$name]->getOption('description') . '</small>');
//                }
                
                echo $label;
        }
        
         
        public static function macroDescription($content) {
                list($name, $modifiers) = self::fetchNameAndModifiers($content);
                return "FormMacros::description($name, $modifiers)";
        }
        
        public static function description($name, $modifiers = array()) {

				$descEl = isset($modifiers['el']) ? $modifiers['el'] : 'small';
                $desc = Html::el($descEl)
                	->setHtml(
                		self::getComponentFromName($name)
                			->getOption('description')
                	);
                
                if (isset($modifiers["class"])) {
                	$desc->class[] = $modifiers["class"];
                }

                if (isset($modifiers["style"])) {
                 	$desc->style($modifiers["style"]);
                }
                
                echo $desc;
        }

        
        public static function macroRequiredSuffix($suffix) {
                return "FormMacros::\$requiredSuffix = $suffix";
        }
        
        public static function macroRequiredPrefix($prefix) {
                return "FormMacros::\$requiredPrefix = $prefix";
        }
        
        public static function macroLabelSuffix($suffix) {
                return "FormMacros::\$labelSuffix = $suffix";
        }

         
        /**
         * Returns component for name
         *
         * @param string $name component.subcomponent
         */
        private static function getComponentFromName($name){
//            $name = split('\.',$name);
            $name = preg_split('/\./', $name);
            $current = self::$form;
            foreach($name as $n){
                $current = $current->getComponent($n);
            }

            return $current;
        }
        
        // helper

        private static function fetchNameAndModifiers($code) {
                $name = LatteFilter::fetchToken($code);
                $modifiers = LatteFilter::formatArray($code);

                $name = String::startsWith($name, '$') ? $name : "'$name'";
                $modifiers = $modifiers ? $modifiers : "array()";

                return array($name, $modifiers);
        }
}

?>