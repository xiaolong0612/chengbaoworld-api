<?php

namespace app\controller\api\game;

use app\common\repositories\game\GameRepository;
use app\controller\api\Base;
use think\App;

class Game  extends Base
{
    protected $repository;

    public function __construct(App $app, GameRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    //列表
    public function list()
    {
        $where = $this->request->param([
            'keywords' => ''
        ]);
        [$page, $limit] = $this->getPage();
        $data = $this->repository->getList($where, $page, $limit);
        return $this->success($data['list']??[]);
    }

    public function recordList(){
        $url = 'https://308k356y95.picp.vip/records/getList';
        $params = $this->request->param([
            'pageNum' => '',
            'pageSize'=>'',
            'sortField'=>'',
            "sortType"=>'',
            "start"=>"",
            "frontParams.field"=>"",
            "frontParams.value"=>"",
            "frontParams.cond"=>""
        ]);
        $curl = curl_init();
        $json_string = json_encode($params);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($curl);

        curl_close($curl);
        $data = json_decode($data, true);
        return $this->success($data['data']??[]);
    }
}