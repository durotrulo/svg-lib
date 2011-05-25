<?php

/**
 *
 * @copyright  Copyright (c) 2011 Matus Matula
 * @package    GUI Acl
 */



/**
 * Assertions
 *
 */
class Acl_Admin_AssertionsPresenter extends Acl_Admin_BasePresenter
{
	const TABLE = TABLE_ASSERTIONS;
   
    /******************
     * Default
     ******************/
    public function renderDefault() {
        $this->template->assertions = dibi::query('SELECT id, class, comment FROM ['. self::TABLE . '] ORDER BY class;')->fetchAll();
    }

    /******************
     * Add and Edit
     ******************/
    public function actionAdd() {}
    
    
    public function actionEdit($id)
    {
        $sql = dibi::query('SELECT class, comment FROM ['. self::TABLE . '] WHERE id=%i;', $id);
        $form = $this->getComponent('addEdit');
        if (count($sql)) {
            $form->setDefaults($sql->fetch());
        }
        else
            $form->addError('This assertion does not exist.');
    }
    
    
    protected function createComponentAddEdit($class)
    {
        $form = new AppForm($this, $class);
        $renderer = $form->getRenderer();
        $renderer->wrappers['label']['suffix'] = ':';

        $form->addText('class', 'class', 30)
           	->addRule(Form::FILLED, 'You have to fill class.');
        //$form->addGroup('Edit');
        $form->addTextArea('comment', 'Comment', 40, 4)
            ->addRule(Form::MAX_LENGTH, 'Comment must be at least %d characters.', 250);
        if ($this->getAction()=='add')
            $form->addSubmit('add', 'Add');
        else
            $form->addSubmit('edit', 'Edit');
        $form->onSubmit[] = array($this, 'addEditOnFormSubmitted');
    }
    
    
    public function addEditOnFormSubmitted(AppForm $form)
    {
        // add
        if ($this->getAction()=='add') {
            try {
                $values = $form->getValues();
                dibi::query('INSERT INTO ['. self::TABLE . '] %v;', $values);
                $this->flashMessage('The assertion has been added.', 'ok');
                if (ACL_CACHING) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Assertions:');
            } catch (Exception $e) {
                $form->addError('The assertion has not been added.');
                throw $e;
            }
        }
        else { // edit
            try {
                $id = $this->getParam('id');
                $values = $form->getValues();
                dibi::query('UPDATE ['. self::TABLE . '] SET %a WHERE id=%i;', $values, $id);
                $this->flashMessage('The privileg has been edited.', 'ok');
                if (ACL_CACHING AND ACL_PROG_MODE) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Assertions:');
            } catch (Exception $e) {
                $form->addError('The assertion has not been edited.');
                throw $e;
            }
        }
    }

    /******************
     * Delete
     ******************/
    public function actionDelete($id) {
        $sql = dibi::query('SELECT class FROM ['. self::TABLE . '] WHERE id=%i;', $id);
        if (count($sql)) {
            $this->template->assertion = $sql->fetchSingle();
        }
        else {
            $this->flashMessage('This assertion does not exist.');
            $this->redirect('Assertions:');
        }
    }
    protected function createComponentDelete($class) {
        $form = new AppForm($this, $class);
        $form->addSubmit('delete', 'Delete');
        $form->addSubmit('cancel', 'Cancel');
        $form->onSubmit[] = array($this, 'deleteOnFormSubmitted');
    }
    public function deleteOnFormSubmitted(AppForm $form) {
        if ($form['delete']->isSubmittedBy()) {
            try {
                $id = $this->getParam('id');
                dibi::query('DELETE FROM ['. self::TABLE . '] WHERE id=%i;', $id);
                $this->flashMessage('The assertion has been deleted.', 'ok');
                if (ACL_CACHING) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Assertions:');
            } catch (Exception $e) {
                $form->addError('The assertion has not been deleted.');
                throw $e;
            }
        }
        else
            $this->redirect('Assertions:');
    }
}
