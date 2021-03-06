<?php

class Front_ForgottenPassPresenter extends BasePresenter
{
	/** @persistent */
	public $resetToken;

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->setLayout('../../../LoginModule/templates/@layout');
	}
	
	
	protected function createComponentSendPassForm()
	{
		$input_length = 35;
		
		$form = new MyAppForm();
		
		if (isset($this->getPresenter()->translator)) {
		    $form->setTranslator($this->getPresenter()->translator);
	    }
		
		$form->addText('email', 'E-mail', $input_length)
			->setEmptyValue('@')
			->addRule(Form::FILLED)
			->addRule(Form::EMAIL);
			
		$form->addSubmit('send', 'Send me password')
			->getControlPrototype()->class[] = 'ok-button';

		$form->addProtection('Form validity time expired. Please send the form again.');

		$form->onSubmit[] = array($this, 'sendPassFormSubmitted');

		return $form;
	}
	
	// todo: opravit
	public function sendPassFormSubmitted($form)
	{
		$values = $form->getValues();
		
		//	vytiahnem id podla emailu .. ak neexistuje->bulshit
		$user = UsersModel::findByEmail($values['email']);

		if (!$user->id) {
			$this->flashMessage('Účet so zadaným e-mailom neexistuje', 'error');
			$this->redirect('this');
		} else {
			// poslem mail ze ziadal vyresetovat heslo spolu s linkom na reset
			
			// poslem vygenerovane heslo na mail a ulozim do db
			$new_pass = Basic::randomizer(15);
//			$pass = sha1($new_pass . $username);

			try {
				$res = UsersModel::update($user->id, array(
					'password' => $new_pass,
					'username' => $user->username,
				));
				
				$email_template = $this->createTemplate();
				$email_template->setFile(APP_DIR . '/FrontModule/ForgottenPass/templates/email.phtml');
				$email_template->login = $user->username;
				$email_template->pass = $new_pass;
				$email_template->id_user = $user->id;
				
				/* todo: zmenit hlavicky emailu */
				$mail = new Mail;
			    $mail->setFrom(Environment::getConfig("contact")->forgottenPassEmail);
				$mail->addTo($values['email']);
				$mail->setSubject('Password Assistance');
				$mail->setHtmlBody($email_template);
				$mail->send();
			} catch (DibiDriverException $e) {
				$this->flashMessage('Operáciu sa nepodarilo vykonať, skúste znova o pár sekúnd', 'error');
				$this->redirect('this');
			} catch (InvalidStateException $e) {
				$this->flashMessage('Email sa nepodarilo odoslať, skúste znova o pár sekúnd', 'error');
				$this->redirect('this');
			}
			
			$this->flashMessage('Na zadaný e-mail boli odoslané prihlasovacie údaje');
			$this->redirect('this');
		}
	}
	
	
	public function actionResetPass($email)
	{
		// porovnaj s db pre dany email $resetToken
		// ak sedia, prihlas usera a presmeruj na settings s flashMessage aby si zmenil heslo, ze je to jednorazove prihlasenie teraz
		$this->getUser()->login($values['username'], $values['password']);
	}
}