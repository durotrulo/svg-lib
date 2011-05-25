<?php
class ProjectManagerRole implements IRole
{
    public $id;

    public function getRoleId()
    {
        return 'projectManager';
    }
}