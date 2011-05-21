<?php

final class Helpers
{

	public static function html($s) {
		
		return nl2br(trim(htmlspecialchars($s, ENT_QUOTES)));
//			return nl2br(self::bbcode(trim(htmlspecialchars($s, ENT_QUOTES))));
	}
	
	// umoznuje volat nativne php funkcie ako helper
	public static function functionLoader($helper)
    {
    	if (is_callable($helper))
          	return $helper;
    }
    
    /**
	 * znova spracovava retazec s latte syntaxou s vyuzitim StringTemplate
	 *
	 * @param string $s
	 * @return string
	 */
	public static function latte($s)
	{
		// kedze HTML Purifier aj CKEditor escape-uju entity, tak by mi nefungovali nette linky -> robim replace
		$search = array('-&gt;', '=&gt;');
		$replace = array('->', '=>');
		$s = str_replace($search, $replace, $s);
		
		$tpl = new StringTemplate();
		$tpl->presenter = Environment::getApplication()->getPresenter();   // nutné např. pro rozchození linků
		$tpl->registerFilter(new LatteFilter);
		$tpl->content = $s;  // obsah šablony (řetězec)
		
		$tpl->control = $tpl->presenter;
		// vrátíme vygenerovanou šablonu
		return $tpl->__toString();
		
		// nebo ji vypíšeme na výstup
//		$tpl->render();
	}
	
	
	/**
	 * vrati osetreny email [pripadne ako link]
	 * @param string email
	 * @param bool vratit mailto link?
	 */
	public static function emailSafe($s, $getLink=false) {
		
		// nahrada @ v texte
//		$atReplaceText = '(kysla_rybka)';
//		$atReplaceText = '&#064;';
		$atReplaceText = ' (zavinac) ';
		
		$atReplaceLink = '%40'; // nahrada @ v mailto odkaze

		$search = array(
			'.', 
			'@',
		);
		$replace = array(
			' (bodka) ', 
			$atReplaceText,
		);
		
		if ($getLink) {
			$s1 = str_replace('@', $atReplaceLink, $s);
			$s2 = str_replace($search, $replace, $s);
			return '<a href="mailto:' . $s1 . '">' . $s2 . '</a>';
		} else {
			return str_replace($search, $replace, $s);
		}
	}
}