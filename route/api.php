<?php

use think\facade\Route;

$apiRoute = function () {
    Route::group('', function () {
        //首页
        Route::group('home',function (){

        });
        Route::group('', function () {
            Route::group('config', function () {
                Route::get('getSiteInfo', 'Config/getSiteInfo');## 获取基本信息
                Route::get('getSmsConfig', 'Config/getSmsConfig');##获取短信配置
                Route::get('getPayConfig', 'Config/getPayConfig');##获取支付配置
                Route::get('getWith', 'Config/getWith');##获取连连提现配置
                Route::get('getProgramConfig', 'Config/getProgramConfig');##获取应用参数
                Route::get('getPactInfo', 'Config/getPactInfo');##获取平台协议
                Route::get('getversionConfig', 'Config/getversionConfig');##获取APP版本信息
                Route::get('getMinePool', 'Config/getMinePool');##获取挖矿界面潮玩卡信息

                Route::rule('getbalance','Config/getbalance');
                Route::rule('addbalance','Config/addbalance');
            });

            Route::group('code', function () {
                Route::get('createVerifyCode', 'Code/createVerifyCode');##生成图形验证码
                Route::post('sendPhoneVerifyCode', 'Code/sendPhoneVerifyCode'); ##发送手机验证码
                Route::get('getToken', 'Code/getToken')->middleware(\app\http\middleware\api\CheckToken::class, true);
            });#
        })->prefix('api.system.');

        Route::group('upload',function (){
            Route::post('uploadImage', '/uploadImage')->middleware(\app\http\middleware\api\CheckToken::class, true);
        })->prefix('api.upload');

        Route::group('login', function () {
            Route::post('passwordLogin', 'Login/passwordLogin');##密码登陆
            Route::post('smsLogin', 'Login/smsLogin');##短信验证码登录
            Route::post('register', 'Login/register');##
            Route::post('forgertPassword', 'Login/forgertPassword');##
            Route::post('passwordLoginNew', 'Login/passwordLoginNew');##
            Route::post('verifiCode', 'Login/verifiCodeTrue');##
            Route::post('MobileLoginNew', 'Login/MobileLoginNew');##
            Route::post('queryUserCode', 'Login/queryUserCode');##
            Route::post('wechatLogin', 'Login/wechatLogin');##微信登陆
            Route::post('wechatOn', 'Login/wechatOn');##微信登陆

        });;##登录接口

        Route::group('poster', function () {
            Route::get('getlist', '/getlist');## 广告列表
        })->prefix('api.poster.poster');

        Route::group('wallet', function () {
            Route::group('Ebao',function (){ ## 易宝
                Route::post('open', '/open')->middleware(\app\http\middleware\api\CheckToken::class, true);
            })->prefix('api.wallet.Ebao');
            Route::group('huifu',function (){ ## 汇付
                Route::post('open', '/open')->middleware(\app\http\middleware\api\CheckToken::class, true);
            })->prefix('api.wallet.Huifu');
            Route::group('sand',function (){ ## 衫德
                Route::post('open', '/open')->middleware(\app\http\middleware\api\CheckToken::class, true);
            })->prefix('api.wallet.Sand');

        })->prefix('api.wallet'); ## 钱包管理
        Route::group('payment', function () {
            Route::get('getlist', '/getlist');##
            Route::post('payment', '/payment')->middleware(\app\http\middleware\api\CheckToken::class, true);;##
        })->prefix('api.payment');



        Route::group('article', function () {
            Route::group('news', function () {
                Route::get('getNewsList', '/getNewsList');
                Route::get('getNewsList1', '/getNewsList1')->middleware(\app\http\middleware\api\CheckToken::class, true);
                Route::get('getCate', '/getCate');
                Route::get('getDetail/:id', '/getDetail');
            })->prefix('api.article.News');## 新闻资询
            Route::group('operate', function () {
                Route::get('getOperateList', '/getOperateList');
                Route::get('getSellList', '/getSellList');
                Route::get('getCate', '/getCate');
                Route::get('getDetail/:id', '/getDetail');
            })->prefix('api.article.Operate');## 操作指南
            Route::group('faq', function () {
                Route::get('getFaqList', '/getFaqList');
                Route::get('getCate', '/getCate');
                Route::get('getDetail/:id', '/getDetail');
            })->prefix('api.article.Faq');## 常见问题
        });

        Route::group('affiche', function () {
            Route::get('getCate', '/getCate');## 公告分类
            Route::get('getList', '/getList');## 公告列表
            Route::get('getList1', '/getList1')->middleware(\app\http\middleware\api\CheckToken::class, true);## 公告列表
            Route::get('lookCheck', '/lookCheck')->middleware(\app\http\middleware\api\CheckToken::class, true);## 公告列表
            Route::get('decNums', '/decNums')->middleware(\app\http\middleware\api\CheckToken::class, true);## 公告列表
            Route::get('getnums', '/getnums')->middleware(\app\http\middleware\api\CheckToken::class, true);## 公告列表
            Route::get('getNew', '/getNew');## 最新公告
            Route::get('getTopAfficheList', '/getTopAfficheList');
            Route::get('afficheList', '/afficheList');
            Route::get('afficheDetail/:id', '/afficheDetail');
            Route::post('follow', '/follow')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getLog', '/getLog')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getDetail', '/getDetail')->middleware(\app\http\middleware\api\CheckToken::class, false);
        })->prefix('api.article.Affiche');

        Route::group('article', function () {
            Route::group('faq',function (){
                Route::get('getCate', '/getCate');##
                Route::get('getFaqList', '/getFaqList');##
                Route::get('getDetail', '/getDetail');##
            })->prefix('api.article.faq');
        })->prefix('api.article.article');


        Route::group('pool',function (){
            Route::get('getList','/getList');
            Route::get('getGroup','/getGroup');
            Route::get('getDetail','/getDetail')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::post('draw','/draw')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getHot','/getHot'); ##
            Route::post('buy','/buy')->middleware(\app\http\middleware\api\CheckToken::class, true);

            Route::post('delivery','/delivery')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::post('giv','/giv')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getTransferEnd','/getTransferEnd')->middleware(\app\http\middleware\api\CheckToken::class, true);   //转赠队列请求接口
            Route::get('getMyAirdropList','/getMyAirdropList')->middleware(\app\http\middleware\api\CheckToken::class, true); ## 我的空投卡牌
            Route::get('getMyList','/getMyList')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getMyListInfo','/getMyListInfo')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getMyInfo','/getMyInfo')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('givLog','/givLog')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('search','/search');
        })->prefix('api.pool.pool');## 卡牌

        //游戏列表
        Route::group('game',function (){
            Route::get('list', '/list')->middleware(\app\http\middleware\api\CheckToken::class, true);##
            Route::get('recordList', '/recordList')->middleware(\app\http\middleware\api\CheckToken::class, true);##
        })->prefix('api.game.game');

        //工会
        Route::group('guild',function (){
            Route::post('createGuild','/createGuild')->middleware(\app\http\middleware\api\CheckToken::class, true);//创建工会
            Route::post('addGuild','/addGuild')->middleware(\app\http\middleware\api\CheckToken::class, true);//加入工会
            Route::post('deleteGuildMember','/deleteGuildMember')->middleware(\app\http\middleware\api\CheckToken::class, true);//删除工会成员
            Route::get('guildList','/guildList')->middleware(\app\http\middleware\api\CheckToken::class, true);//工会排名
            Route::post('upgradeGuild','/upgradeGuild')->middleware(\app\http\middleware\api\CheckToken::class, true);//升级工会人数
            Route::get('getUserList','/getUserList')->middleware(\app\http\middleware\api\CheckToken::class, true);//升级工会人数
            Route::get('getInfo','/getInfo')->middleware(\app\http\middleware\api\CheckToken::class, true);//升级工会人数
            Route::get('wareHourse','/wareHourse')->middleware(\app\http\middleware\api\CheckToken::class, true);//升级工会人数
            Route::get('wareHourse','/wareHourse')->middleware(\app\http\middleware\api\CheckToken::class, true);//升级工会人数
            Route::post('send','/send')->middleware(\app\http\middleware\api\CheckToken::class, true);//升级工会人数
            Route::get('getGivLog','/getGivLog')->middleware(\app\http\middleware\api\CheckToken::class, true);//升级工会人数
        })->prefix('api.guild.guild');## 工会

        //矿场
        Route::group('mine',function (){
            Route::get('landList','/landList')->middleware(\app\http\middleware\api\CheckToken::class, true);//土地列表
            Route::post('develop','/develop')->middleware(\app\http\middleware\api\CheckToken::class, true);//土地列表
            Route::get('getMyMine','/getMyMine')->middleware(\app\http\middleware\api\CheckToken::class, true);//土地列表
            Route::get('getMyWite','/getMyWite')->middleware(\app\http\middleware\api\CheckToken::class, true);//土地列表
            Route::get('rank','/rank')->middleware(\app\http\middleware\api\CheckToken::class, true);//土地列表
            Route::get('getFriendMine','/getFriendMine')->middleware(\app\http\middleware\api\CheckToken::class, true);//土地列表
            Route::get('introduce','/introduce')->middleware(\app\http\middleware\api\CheckToken::class, true);//土地列表
            Route::get('dispatch','/dispatch')->middleware(\app\http\middleware\api\CheckToken::class, true);//派遣
            Route::post('getWitePrice','/getWitePrice')->middleware(\app\http\middleware\api\CheckToken::class, true);//派遣
        })->prefix('api.mine.mine');## 矿场

        //代理
        Route::group('agent',function (){
            Route::get('getApiList','/getApiList')->middleware(\app\http\middleware\api\CheckToken::class, true);//代理列表
        })->prefix('api.agent.agent');## 代理

        // 2024-02-02 XT add
        // 店长
        Route::group('manager',function (){
            Route::get('getMyManagerInfo','/getMyManagerInfo');//店长信息
            Route::post('addManager','/addManager')->middleware(\app\http\middleware\api\CheckToken::class, true); // 新增代理
            Route::post('addSecondManager','/addSecondManager'); // 新增二级代理
            Route::get('getNum','/getNum');//获取成为二级店长需要购买闪卡数量
            Route::post('editManager','/editManager'); // 修改店长信息


        })->prefix('api.agent.StoreManager');## 代理
        //帮助中心
        Route::group('help',function (){
            Route::get('getHelpCenter','/getHelpCenter');//帮助中心列表
            Route::post('solve','/solve');//已解未解决
        })->prefix('api.agent.HelpCenter');
        //
        Route::group('video',function (){
            Route::get('getList','/getList')->middleware(\app\http\middleware\api\CheckToken::class, true);//创建工会
        })->prefix('api.video.Operate');##

        //etc提现
        Route::group('etc',function (){
            Route::post('withdrawal','/withdrawal')->middleware(\app\http\middleware\api\CheckToken::class, true);//提现
            Route::get('getLog','/getLog')->middleware(\app\http\middleware\api\CheckToken::class, true);//提现
        })->prefix('api.currency.etc');## 代理

        Route::group('active',function (){ //
            Route::get('getList','/getList');
            Route::get('getDetail','/getDetail')->middleware(\app\http\middleware\api\CheckToken::class, true); ##
        })->prefix('api.active.active');

        Route::group('syn',function (){
            Route::get('getDetail','/getDetail')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getDetail_v1','/getDetail_v1')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('poolList','/poolList')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::post('syn','/syn')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::post('syn_v1','/syn_v1')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::post('fastSyn','/fastSyn')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::post('fastOneSyn','/fastOneSyn')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('getLog','/getLog')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('synLogInfo','/synLogInfo')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::post('synQueue','/synQueue')->middleware(\app\http\middleware\api\CheckToken::class, true);
            Route::get('synGet','/synGet')->middleware(\app\http\middleware\api\CheckToken::class, true);
        })->prefix('api.active.syn');

        // 2024-02-02 XT 宝石消耗
        Route::group('', function () {
            Route::post('updateUserGem','/updateUserGem');
        })->prefix('api.user.User');


        ## 需要登录的接品 and
        Route::group('', function () {
            Route::group('user', function () {
                Route::post('logout', '/logout');##退出登录
                Route::post('modifyAvatar', '/modifyAvatar');##修改头像
                Route::get('get_rate', '/get_rate');##获取用户信息
                Route::get('getUserInfo', '/getUserInfo');##获取用户信息
                Route::post('modifyInfo', '/modifyInfo');##修改信息
                Route::post('modifyMobile', '/modifyMobile');##修改手机号
                Route::post('modifyLoginPassword', '/modifyLoginPassword');##修改登录密码
                Route::post('modifyLoginPayPassword', '/modifyLoginPayPassword');##
                Route::get('getBalanceLogList', '/getBalanceLogList');##获取余额日志
                Route::get('getIntegralLogList', '/getIntegralLogList');##获取积分日志
                Route::post('TransferIntegral', '/TransferIntegral');##转赠积分
                Route::get('queryUser', '/queryUser');##查询用户
                Route::get('getFrom', '/getFrom');##
                Route::get('getGivLog', '/getGivLog');##
                Route::get('getFollow', '/getFollow');##
                Route::get('getFans', '/getFans');##
                Route::get('getForum', '/getForum');##
                Route::get('getShareRanking', '/getShareRanking');## 排行榜显示前 100 名
                Route::post('checkIn', '/checkIn');##
                Route::post('givTokens', '/givTokens');##
                Route::get('foodLogList', '/foodLogList');##
                Route::get('frendsRawd', '/frendsRawd');##
                Route::get('mineGivLog', '/mineGivLog');##
                Route::get('getFood', '/getFood');
                // 2024-02-02 XT add
                Route::get('gameLogin','/gameLogin');
                Route::get('gameLoginOut','/gameLoginOut');
            })->prefix('api.user.User');##用户信息
            Route::group('order', function () {
                Route::get('getList', '/getList');##订单列表
                Route::post('cannel', '/cannel');##取消订单
                Route::post('finish', '/finish');##确定订单
                Route::get('getStayPayOrderInfo', '/getStayPayOrderInfo');##
                Route::get('getDetail', '/getDetail');##订单详情
            })->prefix('api.order.order');##

            Route::group('address', function () {
                Route::get('getList', '/getList');##
                Route::post('add', '/add');##
                Route::post('edit', '/edit');##
                Route::post('del', '/del');##
            })->prefix('api.user.address');#

            Route::group('user/cert', function () {
                Route::post('applyCert', '/applyCert');##申请个人认证
                Route::get('userCertInfo', '/userCertInfo');##个人认证详情
            })->prefix('api.user.UserCert');##实名认证

        })->middleware(\app\http\middleware\api\CheckToken::class, true);
    });
    // miss路由
    Route::miss(function () {
        return \think\Response::create(['code' => 404,'msg' => '接口不存在' ], 'json')->code(404);
    });
};

if (env('SINGLE_DOMAIN_MODE')) {
    Route::group(env('API_URL') ?: 'api', $apiRoute)
        ->prefix('api.')
        ->middleware(\app\http\middleware\api\AllowCrossDomain::class)
        ->middleware(\app\http\middleware\api\CheckCompany::class); // 单域名访问
} else {
    Route::domain(env('API_URL'), $apiRoute)
        ->prefix('api.')
        ->middleware(\app\http\middleware\api\AllowCrossDomain::class)
        ->middleware(\app\http\middleware\api\CheckCompany::class); // 独立域名访问
}
