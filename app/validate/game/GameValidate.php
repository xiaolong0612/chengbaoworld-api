<?php

namespace app\validate\game;

use think\Validate;

class GameValidate  extends Validate
{
    protected $rule = [
        'id' => 'require|number',
        'game_name' => ['require'],
        'image' => 'require',
        'status' =>  'require|number',
        'game_key'=> 'require',
    ];
    protected $message = [
        'id.require' => ['code' => -1, 'msg' => 'id不能为空'],
        'id.number' => ['code' => -1, 'msg' => 'id只能是数字'],
        'game_name.require' => ['code' => -1, 'msg' => '游戏名称不能为空'],
        'gamename.require' => ['code' => -1, 'msg' => '游戏别名不能为空'],
        'image.require' => ['code' => -1, 'msg' => '封面图不能为空'],
        'status.require' => ['code' => -1, 'msg' => '状态不能为空'],
        'status.number' => ['code' => -1, 'msg' => '状态只能是数字'],
        'game_key.require' => ['code' => -1, 'msg' => 'key不能为空']
    ];

    public function sceneAdd()
    {
        return $this->remove('id', 'require');
    }

}