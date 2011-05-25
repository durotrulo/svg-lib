<?php

/**
 * GUI for Acl
 *
 * @copyright  Copyright (c) 2010 Tomas Marcanik
 * @package    GUI for Acl
 */


/**
 * Presenter for user management
 *
 */
class Acl_Admin_UsersPresenter extends Acl_Admin_BasePresenter
{
    /** @var string */
    private $search = '';


    /******************
     * Default
     ******************/
    public function renderDefault() {
        $form = $this->getComponent('search');
        $this->template->form = $form;
        $users_roles = array();

        // paginator
        $vp = $this['paginator'];
        $paginator = $vp->getPaginator();
        $paginator->itemsPerPage = 20;

        $sql = dibi::query('SELECT id, username FROM ['.TABLE_USERS.'] '.(!empty($this->search) ? 'WHERE username LIKE %s ' : '').'ORDER BY username;', $this->search);
        $paginator->itemCount = count($sql);
        if (!empty($this->search)) { // disable paginator
            $paginator->itemsPerPage = $paginator->itemCount;
        }
        $users = $sql->fetchAll($paginator->offset, $paginator->itemsPerPage);
        foreach ($users as $user) {
            $users_roles[$user->id]['name'] = $user->username;
            $sql2 = dibi::query('SELECT r.id, r.name
                                    FROM ['.TABLE_ROLES.'] AS r
                                    JOIN ['.TABLE_USERS_ROLES.'] AS u ON r.id=u.role_id
                                    WHERE u.user_id=%i
                                    ORDER BY r.name;', $user->id);
            $roles = $sql2->fetchAll();
            $users_roles[$user->id]['roles'] = array();
            foreach ($roles as $role) {
                $users_roles[$user->id]['roles'][$role->id] = $role->name;
            }
        }
        $this->template->users = $users_roles;
    }
    protected function createComponentSearch($name) {
        $form = new AppForm($this, $name);
        //$form->addGroup('Search');
        $form->addText('username', 'Name:', 30)
            ->addRule(Form::FILLED, 'You have to fill name.');
        $form->addSubmit('search', 'Search');
        $form->onSubmit[] = array($this, 'searchOnFormSubmitted');
    }
    public function searchOnFormSubmitted(AppForm $form) {
        $values = $form->getValues();
        $this->search = strtr($values['username'], "*", "%");
    }

    /******************
     * Add and Edit
     ******************/
    public function actionAdd() {
    }
    public function actionEdit($id) {
        $sql = dibi::query('SELECT username FROM ['.TABLE_USERS.'] WHERE id=%i;', $id);
        $form = $this->getComponent('addEdit');
        if (count($sql)) {
            $name = $sql->fetchSingle();
            $sql = dibi::query('SELECT role_id AS roles FROM ['.TABLE_USERS_ROLES.'] WHERE user_id=%i;', $id);
            $roles = $sql->fetchPairs();
            $form->setDefaults(array('username' => $name, 'roles' => $roles));
        }
        else
            $form->addError('This user does not exist.');
    }
    protected function createComponentAddEdit($name) {
        $mroles = new RolesModel();
        $roles = $mroles->getTreeValues();

        $form = new AppForm($this, $name);
        $renderer = $form->getRenderer();
        $renderer->wrappers['label']['suffix'] = ':';
        //$form->addGroup('Add');
        $form->addText('username', 'Name', 30)
            ->addRule(Form::FILLED, 'You have to fill name.');
        if ($this->getAction()=='add') {
            $form->addPassword('password', 'Password', 30)
                ->addRule(Form::FILLED, 'You have to fill password.');
            $form->addPassword('password2', 'Reenter password', 30)
                ->addRule(Form::FILLED, 'Reenter your password.')
                ->addRule(Form::EQUAL, 'Passwords do not match.', $form['password']);
        }
        $form->addMultiSelect('roles', 'Roles', $roles, 15);
        if ($this->getAction()=='add')
            $form->addSubmit('add', 'Add');
        else
            $form->addSubmit('edit', 'Edit');
        $form->onSubmit[] = array($this, 'addEditOnFormSubmitted');
    }
    public function addEditOnFormSubmitted(AppForm $form) {
        $error = false;
        dibi::begin();
        // add action
        if ($this->getAction()=='add') {
            try {
                $values = $form->getValues();
                $roles = $values['roles'];
                unset($values['password2'], $values['roles']);
                $values['password'] = md5($values['password']);
                dibi::query('INSERT INTO ['.TABLE_USERS.'] %v;', $values);
                $user_id = dibi::getInsertId();
                if (count($roles)) {
                    foreach ($roles as $role) {
                        dibi::query('INSERT INTO ['.TABLE_USERS_ROLES.'] (user_id, role_id) VALUES (%i, %i);', $user_id, $role);
                    }
                }
                $this->flashMessage('The user has been added.', 'ok');
                dibi::commit();
                if (ACL_CACHING) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Users:');
            } catch (Exception $e) {
                $error = true;
                $form->addError('The user has not been added.');
                throw $e;
            }
        }
        else { // edit action
            $id = $this->getParam('id');
            try {
                $values = $form->getValues();
                $roles = $values['roles'];
                unset($values['roles']);
                dibi::query('UPDATE ['.TABLE_USERS.'] SET %a WHERE id=%i;', $values, $id);
                dibi::query('DELETE FROM ['.TABLE_USERS_ROLES.'] WHERE user_id=%i;', $id);
                if (count($roles)) {
                    foreach ($roles as $role) {
                        dibi::query('INSERT INTO ['.TABLE_USERS_ROLES.'] (user_id, role_id) VALUES (%i, %i);', $id, $role);
                    }
                }
                $this->flashMessage('The user has been edited.', 'ok');
                dibi::commit();
                if (ACL_CACHING) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Users:');
            } catch (Exception $e) {
                $error = true;
                $form->addError('The user has not been edited.');
                throw $e;
            }
        }

        if ($error)
            dibi::rollback();
    }

    /******************
     * Delete
     ******************/
    public function actionDelete($id) {
        $sql = dibi::query('SELECT username FROM ['.TABLE_USERS.'] WHERE id=%i;', $id);
        if (count($sql)) {
            $this->template->user_name = $sql->fetchSingle();
        }
        else {
            $this->flashMessage('This user does not exist.');
            $this->redirect('Users:');
        }
    }
    protected function createComponentDelete($name) {
        $form = new AppForm($this, $name);
        $form->addSubmit('delete', 'Delete');
        $form->addSubmit('cancel', 'Cancel');
        $form->onSubmit[] = array($this, 'deleteOnFormSubmitted');
    }
    public function deleteOnFormSubmitted(AppForm $form) {
        if ($form['delete']->isSubmittedBy()) {
            try {
                $id = $this->getParam('id');
                dibi::query('DELETE FROM ['.TABLE_USERS.'] WHERE id=%i;', $id);
                $this->flashMessage('The user has been deleted.', 'ok');
                if (ACL_CACHING) {
                    unset($this->cache['gui_acl']); // invalidate cache
                }
                $this->redirect('Users:');
            } catch (Exception $e) {
                $form->addError('The user has not been deleted.');
                throw $e;
            }
        }
        else
            $this->redirect('Users:');
    }

    /******************
     * Access
     ******************/
    public function actionAccess($id) {
        $nodes = new RolesModel();
        $this->template->nodes = $nodes;
        $this->template->parents = $nodes->getChildNodes(NULL);

        $user = dibi::fetchSingle('SELECT username FROM ['.TABLE_USERS.'] WHERE id=%i;', $id);
        $this->template->user_name = $user;

        $roles = dibi::fetchAll('SELECT r.key_name FROM ['.TABLE_ROLES.'] AS r
                                    RIGHT JOIN ['.TABLE_USERS_ROLES.'] AS ur ON r.id=ur.role_id
                                    WHERE ur.user_id=%i;', $id);

        $access = new AccessModel($roles);
        $this->template->access = $access->getAccess();
    }
}
