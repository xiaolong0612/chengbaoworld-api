<?php

namespace app\common\repositories;

/**
 * Class BaseRepository
 * @package app\common\repositories
 */
class BaseRepository
{
    /**
     * @var $dao
     */
    protected $dao;

    public function setDao($dao)
    {
        $this->dao = $dao;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->dao, $name], $arguments);
    }
}