<?php

use think\facade\Route;


$companyRoute = function () {
    Route::get('captcha', "\\think\\captcha\\CaptchaController@index")->prefix('');
    // 后台登录
    Route::group('login', function () {
        Route::get('index', 'Login/index')->name('companyUserLogin')->middleware(\app\http\middleware\company\CheckLogin::class, false);
        Route::post('doLogin', 'Login/doLogin')->name('companyUserDoLogin');
    })->prefix('company.');

    Route::group('', function () {
        Route::get('/', 'Index/index')->name('companyIndex');
        Route::group('index', function () {
            Route::get('welcome', 'Index/welcome')->name('companyIndexWelcome');
            Route::get('getMenu', 'Index/getMenu')->name('companyIndexGetMenu');
            Route::get('clearCache', 'Index/clearCache')->name('companyIndexClearCache');
            Route::post('signOut', 'Index/signOut')->name('companyLoginout');
            Route::get('statistics', 'Index/statistics')->name('companyIndexStatistics');
        });
        Route::group('upload', function () {
            Route::get('selectUploadFile', 'Upload/selectUploadFile')->name('companySelectUploadFile');
            Route::get('getUploadImageList', 'Upload/getUploadImageList')->name('companyGetUploadImageList');
            Route::post('addUploadFileGroup', 'Upload/addUploadFileGroup')->name('companyAddUploadFileGroup');
            Route::post('editUploadFIleGroup', 'Upload/editUploadFIleGroup')->name('companyEditUploadFileGroup');
            Route::post('deleteUploadFileGroup', 'Upload/deleteUploadFileGroup')->name('companyDeleteUploadFileGroup');
            Route::post('uploadImage', 'Upload/uploadImage')->name('companyUploadImage');
            Route::post('deleteUploadFile', 'Upload/deleteUploadFile')->name('companyDeleteSelectUploadFile');
            Route::post('moveUploadFileGroup', 'Upload/moveUploadFileGroup')->name('companyMoveUploadFileGroup');
        });

        ## 用户  add ##
        Route::group('users', function () {
            Route::group('users', function () {
                Route::get('list', '/list')->name('companyUsersList');##用户列表
                Route::rule('add', '/add')->name('companyUsersAdd');##添加用户
                Route::rule('edit', '/edit')->name('companyUsersEdit');##编辑用户
                Route::rule('delete', '/delete')->name('companyUsersDelete');##删除用户
                Route::rule('setmarkOrder', '/setmarkOrder')->name('companyUsersSetMarkOrder');##设置用户市场下单
                Route::rule('setLabel', '/setLabel')->name('companyUsersSetLabel');##设置用户标签
                Route::rule('setGroup', '/setGroup')->name('companyUsersSetGroup');##设置用户分组
                Route::rule('editPassword', '/editPassword')->name('companyUsersEditPassword');##修改用户密码
                Route::rule('editTjr', '/editTjr')->name('companyUsersEditTjr');##修改用户推荐人信息
                Route::rule('setUserCert', '/setUserCert')->name('companySetUsersCert');##设置个人认证
                Route::rule('setUserStatus', '/setUserStatus')->name('companyUsersStatus');##设置登录状态
                Route::get('userDetail', '/userDetail')->name('companyUsersDetail');##用户详情
                Route::rule('setBalance', '/setBalance')->name('companyUsersSetBalance');##设置用户余额
                Route::rule('setIntegral', '/setIntegral')->name('companyUsersSetIntegral');##设置用户积分
                Route::rule('billLog', '/billLog')->name('companyUsersBillLog');##用户日志
                Route::rule('batchSetFrozen', '/batchSetFrozen')->name('companyUsersBatchSetFrozen');##批量设置冻结金额
                Route::rule('batchSetBalance', '/batchSetBalance')->name('companyUsersBatchSetBalance');##批量设置余额
                Route::rule('batchSetGroup', '/batchSetGroup')->name('companyUsersBatchSetGroup');##批量设置分组
                Route::rule('batchSetLabel', '/batchSetLabel')->name('companyUsersBatchSetLabel');##批量设置标签
                Route::rule('batchSetIntegral', '/batchSetIntegral')->name('companyUsersBatchSetIntegral');##批量设置积分
                Route::post('setAuthor', '/setAuthor')->name('companyUsersAuthor');##设置创作者
                Route::post('setSelf', '/setSelf')->name('companyUsersSetSelf');##设置自营账户
                Route::rule('selfUserList', '/selfUserList')->name('companyUsersSelfList');##自营账户列表

                Route::rule('pushCountList', '/pushCountList')->name('companyUsersPushCountList');##团队列表
                Route::rule('balanceList', '/billLog')->name('companyUsersBalanceList');##余额日志
                Route::rule('billLog', '/billLog')->name('companyUsersBillLog');##积分日志
            })->prefix('company.users.Users');##用户列表
            Route::group('balance', function () {##余额日志
                Route::get('list', '/list')->name('companyUsersBalanceList');
                Route::rule('add', '/add')->name('companyUsersBalanceAdd');
                Route::rule('edit', '/edit')->name('companyUsersBalanceEdit');
                Route::post('del', '/del')->name('companyUsersBalanceDel');
            })->prefix('company.users.UsersBalanceLog');
            Route::group('ect', function () {##余额日志
                Route::get('list', '/list')->name('companyCurrencyEtc');
                Route::rule('status', '/status')->name('companyCurrencyStatus');
            })->prefix('company.currency.ectLog');
            Route::group('track', function () {##行为日志
                Route::get('list', '/list')->name('companyUsersTrackList');
                Route::rule('add', '/add')->name('companyUsersTrackAdd');
                Route::rule('edit', '/edit')->name('companyUsersTrackEdit');
                Route::post('del', '/del')->name('companyUsersTrackDel');
            })->prefix('company.users.UsersTrack');
            Route::group('cert', function () {##用户实名认证
                Route::get('list', '/list')->name('companyUsersCertList');##实名认证列表
                Route::rule('edit', '/edit')->name('companyUsersCertEdit');##删除用户标签
                Route::post('certDel', '/certDel')->name('companyUsersCertDel');##删除用户标签
                Route::rule('examineCert', '/examineCert')->name('companyUsersCertExamine');##认证审核
            })->prefix('company.users.UsersCert');
            Route::group('message', function () {##
                Route::get('list', '/list')->name('companyUserMessage');##
                Route::rule('reply', '/reply')->name('companyUserMessageReply');##
            })->prefix('company.users.UsersMessage');
            Route::group('synLog', function () {##
                Route::get('list', '/list')->name('companySynLog');##
            })->prefix('company.users.UsersSynLog');
            Route::group('address',function (){
                Route::get('list','/list')->name('companyAddressList');##
            })->prefix('company.users.address');

            Route::group('order',function (){
                Route::get('list','/list')->name('companyOrderList');##
                Route::rule('status','/status')->name('companyOrderStatus');##
            })->prefix('company.users.order');
        });
        ## 用户  end ##

        Route::group('', function () {##网站配置
            Route::group('system', function () {
                Route::group('upload', function () {
                    Route::rule('uploadConfig', 'Upload/uploadConfig')->name('companyUploadConfig');
                    Route::get('uploadFileList', 'Upload/uploadFileList')->name('companyUploadFileList');
                    Route::post('delUploadFile', 'Upload/delUploadFile')->name('companyDeleteUploadFile');
                });
                Route::group('sms', function () {
                    Route::rule('smsConfig', 'Sms/smsConfig')->name('companySmsConfig');
                    Route::get('templateList', 'Sms/templateList')->name('companySmsTemplateList');
                    Route::rule('addTemplate', 'Sms/addTemplate')->name('companySmsTemplateAdd');
                    Route::rule('editTemplate', 'Sms/editTemplate')->name('companySmsTemplateEdit');
                    Route::post('delTemplate', 'Sms/delTemplate')->name('companySmsTemplateDelete');
                    Route::post('testSend', 'Sms/testSend')->name('companySmsTestSend');
                    Route::get('smsLogList', 'Sms/smsLogList')->name('companySmsLogList');
                });
                Route::group('payment',function (){
                    Route::get('list','/list')->name('companyPaymentList');
                    Route::rule('add','/add')->name('companyPaymentAdd');
                    Route::rule('edit','/edit')->name('companyPaymentEdit');
                    Route::rule('del','/del')->name('companyPaymentDel');
                    Route::rule('status','/status')->name('companyPaymentSwitch');
                })->prefix('company.system.Payment');
                Route::group('website', function () {
                    Route::rule('siteInfo', 'Website/siteinfo')->name('companySiteInfo');
                    Route::rule('programInfo', 'Website/programInfo')->name('companyProgramInfo');
                    Route::rule('registerInfo', 'Website/registerInfo')->name('companyRegisterInfo');
                });
                Route::group('pact', function () {##平台协议
                    Route::get('list', 'SystemPact/list')->name('companySystemPactList');
                    Route::rule('add', 'SystemPact/add')->name('companySystemPactAdd');
                    Route::rule('edit', 'SystemPact/edit')->name('companySystemPactEdit');
                    Route::post('del', 'SystemPact/del')->name('companySystemPactDel');
                    Route::rule('details', 'SystemPact/details')->name('companySystemPactDetails');
                });
            })->prefix('company.system.');
            Route::group('auth', function () {##系统权限管理
                Route::group('menu', function () {
                    Route::get('index', 'AuthMenu/index')->name('companyAuthMenuList');
                    Route::rule('edit', 'AuthMenu/edit')->name('companyEditAuthMenu');
                })->prefix('company.auth.');
                Route::group('companyUser', function () {
                    Route::rule('editInfo', 'CompanyUser/editInfo')->name('companyEditSelfInfo');
                    Route::rule('editPassword', 'CompanyUser/editPassword')->name('companyEditSelfPassword');
                    Route::get('index', 'CompanyUser/index')->name('companyUserList');
                    Route::rule('add', 'CompanyUser/add')->name('addCompanyUser');
                    Route::rule('edit', 'CompanyUser/edit')->name('editCompanyUser');
                    Route::rule('del', 'CompanyUser/del')->name('delCompanyUser');
                    Route::rule('setAuth', 'CompanyUser/setAuth')->name('setCompanyUserAuth');
                    Route::get('companyLogList', 'CompanyUser/companyLogList')->name('companyUserLogList');
                });
            });
            Route::group('poster', function () {##广告管理 add
                Route::group('posterSite', function () {
                    Route::get('list', '/list')->name('companyPosterSiteList');
                    Route::rule('add', '/add')->name('companyPosterSiteAdd');
                    Route::rule('edit', '/edit')->name('companyPosterSiteEdit');
                    Route::rule('del', '/del')->name('companyPosterSiteDel');
                })->prefix('company.poster.PosterSite');
                Route::group('posterRecord', function () {
                    Route::get('list', '/list')->name('companyPosterRecordList');
                    Route::rule('add', '/add')->name('companyPosterRecordAdd');
                    Route::rule('edit', '/edit')->name('companyPosterRecordEdit');
                    Route::rule('del', '/del')->name('companyPosterRecordDel');
                })->prefix('company.poster.PosterRecord');
            });##广告管理 end
            Route::group('affiche', function () {##公告管理 add
                Route::group('affiche', function () {
                    Route::get('list', '/list')->name('companySystemAfficheList');##
                    Route::rule('add', '/add')->name('companySystemAfficheAdd');##
                    Route::rule('edit', '/edit')->name('companySystemAfficheEdit');##
                    Route::rule('del', '/del')->name('companySystemAfficheDel');##
                    Route::post('status', '/switchStatus')->name('companySystemAfficheSwitch');##
                    Route::post('placedTop', '/placedTop')->name('companySystemAfficheTop');##
                })->prefix('company.system.affiche.Affiche');
                Route::group('afficheCate', function () {
                    Route::get('list', '/list')->name('companySystemAfficheCateList');##
                    Route::rule('add', '/add')->name('companySystemAfficheCateAdd');##
                    Route::rule('edit', '/edit')->name('companySystemAfficheCateEdit');##
                    Route::rule('del', '/del')->name('companySystemAfficheCateDel');##
                    Route::post('status', '/switchStatus')->name('companySystemAfficheCateSwitch');##
                })->prefix('company.system.affiche.AfficheCate');
            });##公告管理 end

            Route::group('mine/mine', function () {
                Route::get('list', '/list')->name('companyMineList');##
                Route::rule('add', '/add')->name('companyMineAdd');##
                Route::rule('edit', '/edit')->name('companyMineEdit');##
                Route::rule('del', '/del')->name('companyMineDel');##
                Route::rule('status', '/status')->name('companyMineSwitch');##
                Route::rule('give', '/give')->name('companyMineAddGiveSwitch');##
            })->prefix('company.mine.mine');## 矿场管理

            Route::group('video/index', function () {
                Route::get('list', '/list')->name('companyVideoTaskList');##
                Route::rule('add', '/add')->name('companyVideoTaskAdd');##
                Route::rule('edit', '/edit')->name('companyVideoTaskEdit');##
                Route::rule('del', '/del')->name('companyVideoTaskDel');##
            })->prefix('company.video.index');## 任务管理

            Route::group('mine/mine/user',function (){
                Route::get('list', '/list')->name('companyUserMineList');#
                Route::rule('del', '/del')->name('companyUserMineDel');##
                Route::rule('giveUserMine', '/giveUserCardPack')->name('companyGiveUserMine');## 空投赠送
                Route::rule('giveUserMineBatch', '/giveUserCardPackBatch')->name('companyGiveUserMineBatch');## 批量空投
            })->prefix('company.mine.MineUser');##会员矿场管理
            ##卡牌管理
            Route::group('pool',function (){
                Route::group('sale',function (){
                    Route::get('list', '/list')->name('companyPoolSaleList');##发售卡牌列表
                    Route::rule('add', '/add')->name('companyPoolSaleAdd');##发售卡牌列表
                    Route::rule('edit', '/edit')->name('companyPoolSaleEdit');##发售卡牌列表
                    Route::rule('del', '/del')->name('companyPoolSaleDel');##发售卡牌列表
                    Route::rule('status', '/status')->name('companyPoolSaleSwitch');
                    Route::rule('market', '/market')->name('companyPoolSaleMarketSwitch');
                    Route::rule('give', '/give')->name('companyPoolSaleGiveSwitch');
                    Route::rule('hot', '/hot')->name('companyPoolSaleHotSwitch');
                    Route::rule('checkPrice', '/checkPrice')->name('companyPoolSaleCheckPrice');
                    Route::rule('create', '/create')->name('companyPoolSaleCreateNft');
                    Route::rule('details', '/details')->name('companyPoolSaleDetails');##
                    Route::rule('poolBonus', '/poolBonus')->name('companyUserPoolBonus');##
                })->prefix('company.pool.sale');##卡牌发售
                Route::group('orderNo',function (){
                    Route::get('list', '/list')->name('companyPoolSaleOrderNo');#编号表
                    Route::rule('destroy', '/destroy')->name('companyPoolSaleNoDestroy');## 销毁
                    Route::rule('recovery', '/recovery')->name('companyPoolSaleNorecovery');## 回收
                })->prefix('company.pool.OrderNo');##卡牌发售
                Route::group('brand',function (){
                    Route::get('list', '/list')->name('companyPoolBrandList');##
                    Route::rule('add', '/add')->name('companyPoolBrandAdd');##
                    Route::rule('edit', '/edit')->name('companyPoolBrandEdit');##
                    Route::rule('del', '/del')->name('companyPoolBrandDel');##
                })->prefix('company.pool.brand');##卡牌品牌管理


                Route::group('UserPool',function (){
                    Route::get('list', '/list')->name('companyUserPoolList');#
                    Route::rule('sale', '/sale')->name('companyUserPoolSale');#
                    Route::rule('giveUserPool', '/giveUserPool')->name('companyGiveUserPoolSale');## 空投赠送卡牌
                    Route::rule('giveUserProp', '/giveUserProp')->name('companyGiveUserPoolProp');## 空投赠送卡牌
                    Route::rule('giveUserPoolBatch', '/giveUserPoolBatch')->name('companyGiveUserPoolSaleBatch');## 批量空投
                    Route::rule('giveBlackUserPoolBatch', '/giveBlackUserPoolBatch')->name('companyGiveBlackUserPoolSaleBatch');## 批量白名单设置
                    Route::rule('giveBlackUserPoolDestroy', '/giveBlackUserPoolDestroy')->name('companyGiveBlackUserPoolSaleDestroy');## 批量销毁卡牌
                })->prefix('company.pool.userPool');##
                Route::group('transferLog',function (){
                    Route::get('list', '/list')->name('companyPoolTransferList');#
                    Route::post('del', '/del')->name('companyPoolTransferDel');#
                })->prefix('company.pool.transfer');##
            });

            ##游戏列表  2024-1-30 owen  add
             Route::group('game',function (){
                Route::group('game',function (){
                    Route::get('list', '/list')->name('companyGameList');##
                    Route::rule('add', '/add')->name('companyGameAdd');##
                    Route::rule('edit', '/edit')->name('companyGameEdit');##
                    Route::rule('del', '/del')->name('companyGameDel');##
                    Route::post('status', '/switchStatus')->name('companyGameSwitch');##
                })->prefix('company.game.game');
            })->prefix('company.game');
             ##活动管理
            Route::group('active',function (){
                Route::group('active',function (){
                    Route::get('list','/list')->name('companyActiveList');##
                    Route::rule('add','/add')->name('companyActiveAdd');##
                    Route::rule('edit','/edit')->name('companyActiveEdit');##
                    Route::rule('del','/del')->name('companyActiveDel');##
                    Route::rule('status','/status')->name('companyActiveSwitch');##
                    Route::rule('default','/default')->name('companyActiveDefaultSwitch');##默认
                })->prefix('company.active.active');
                Route::group('syn',function (){
                    Route::group('syn',function () {
                        Route::get('list','/list')->name('companyActiveSynList');##
                        Route::rule('add','/add')->name('companyActiveSynAdd');##
                        Route::rule('edit','/edit')->name('companyActiveSynEdit');##
                        Route::rule('del','/del')->name('companyActiveSynDel');##
                    })->prefix('company.active.syn.syn');
                    Route::group('synInfo',function (){
                        Route::get('list','/list')->name('companyActiveSynTarget');##
                        Route::rule('add','/add')->name('companyActiveSynTargetAdd');##
                        Route::rule('edit','/edit')->name('companyActiveSynTargetEdit');##
                        Route::rule('del','/del')->name('companyActiveSynTargetDel');##
                        Route::rule('addMateria','/addMateria')->name('companyActiveSynMaterialAdd');##
                        Route::rule('editMateria','/editMateria')->name('companyActiveSynMaterialEdit');##
                        Route::rule('delMateria','/delMateria')->name('companyActiveSynMaterialDel');##
                        Route::rule('addSill','/addSill')->name('companyActiveSynSillAdd');## 添加合成门槛
                        Route::rule('editSill','/editSill')->name('companyActiveSynSillEdit');##编辑合成门槛
                        Route::rule('delSill','/delSill')->name('companyActiveSynSillDel');## 删除合成门槛
                    })->prefix('company.active.syn.synInfo');
                    Route::group('usre',function () {
                        Route::get('list','/list')->name('companyActiveSynUserList');##
                        Route::rule('add','/add')->name('companyActiveSynUserAdd');##
                        Route::rule('import','/import')->name('companyActiveSynUserImport');##
                        Route::rule('del','/del')->name('companyActiveSynUserDel');##
                    })->prefix('company.active.syn.SynUser');

                    Route::group('synKey',function () {
                        Route::get('list','/list')->name('companySynMateriaList');##
                        Route::rule('add','/add')->name('companySynMateriaAdd');##
                        Route::rule('edit','/edit')->name('companySynMateriaEdit');##
                        Route::rule('del','/del')->name('companySynMateriaDel');##
                    })->prefix('company.active.syn.synKey');

                    Route::group('materiaInfo',function () {
                        Route::get('list','/list')->name('companySynMateriaInfoList');##
                        Route::rule('add','/add')->name('companySynMateriaInfoAdd');##
                        Route::rule('edit','/edit')->name('companySynMateriaInfoEdit');##
                        Route::rule('del','/del')->name('companySynMateriaInfoDel');##
                    })->prefix('company.active.syn.SynMateria');

                })->prefix('company.active.syn');

                    Route::group('task',function () {
                        Route::get('list', '/list')->name('companyActiveTask');##
                        Route::rule('add', '/add')->name('companyActiveTaskAdd');##
                        Route::rule('edit', '/edit')->name('companyActiveTaskEdit');##
                        Route::rule('del', '/del')->name('companyActiveTaskDel');##
                })->prefix('company.active.task.task');
                Route::group('taskLog',function (){
                    Route::get('list','/list')->name('companyActiveTaskLog');##
                })->prefix('company.active.task.taskLog');

            })->prefix('company.active');
            // 文章管理
            Route::group('article', function () {
                Route::group('news', function () {##资讯管理
                    Route::get('list', '/list')->name('companyArticleNewsList');##
                    Route::rule('add', '/add')->name('companyArticleNewsAdd');##
                    Route::rule('edit', '/edit')->name('companyArticleNewsEdit');##
                    Route::rule('del', '/del')->name('companyArticleNewsDel');##
                    Route::post('status', '/switchStatus')->name('companyArticleNewsSwitch');##
                    Route::post('placedTop', '/placedTop')->name('companyArticleNewsPlacedTop');##
                })->prefix('company.article.news.News');
                Route::group('newsCate', function () {##资讯管理
                    Route::get('list', '/list')->name('companyArticleNewsCateList');##
                    Route::rule('add', '/add')->name('companyArticleNewsCateAdd');##
                    Route::rule('edit', '/edit')->name('companyArticleNewsCateEdit');##
                    Route::rule('del', '/del')->name('companyArticleNewsCateDel');##
                })->prefix('company.article.news.NewsCate');

            });
            // 代理配置
            Route::group('agent', function () {
                Route::group('agent', function () {##代理管理
                    Route::get('list', '/list')->name('companyAgentList');##
                    Route::rule('add', '/add')->name('companyAgentAdd');##
                    Route::rule('edit', '/edit')->name('companyAgentEdit');##
                    Route::rule('del', '/del')->name('companyAgentDel');##
                    Route::post('status', '/switchStatus')->name('companyAgentStatus');##
                    Route::post('giv', '/placedTop')->name('companyAgentGiveSwitch');##
                })->prefix('company.agent.agent');
            });
            // 代理配置

            Route::group('guild', function () {
                Route::group('config', function () {##公会配置管理
                    Route::get('list', '/list')->name('companyGuildConfig');##
                    Route::rule('add', '/add')->name('companyGuildConfigAdd');##
                    Route::rule('edit', '/edit')->name('companyGuildConfigEdit');##
                    Route::rule('del', '/del')->name('companyGuildConfigDel');##
                    Route::post('status', '/switchStatus')->name('companyGuildConfigStatus');##
                })->prefix('company.guild.config');
                Route::group('guild', function () {##公会列表
                    Route::get('list', '/list')->name('companyGuildList');##
                    Route::rule('add', '/add')->name('companyGuildAdd');##
                    Route::rule('edit', '/edit')->name('companyGuildEdit');##
                    Route::rule('del', '/del')->name('companyGuildDel');##
                })->prefix('company.guild.guild');
                Route::group('member', function () {##公会列表
                    Route::get('list', '/list')->name('companyGuildChild');##
                    Route::rule('del', '/del')->name('companyGuildChildDel');##
                })->prefix('company.guild.member');
                Route::group('ware', function () {##公会列表
                    Route::get('list', '/list')->name('companyGuildware');##
                    Route::rule('send', '/send')->name('companyGuildwareSend');##
                    Route::rule('del', '/del')->name('companyGuildwareDel');##
                })->prefix('company.guild.ware');

            });


        })->middleware(\app\http\middleware\company\CheckAuth::class);
    })->middleware(\app\http\middleware\company\CheckLogin::class);
    // miss路由
    Route::miss(function () {
        return view('company/error/404');
    });
};

if (env('SINGLE_DOMAIN_MODE')) {
    Route::group(env('company_URL') ?: 'company', $companyRoute)->prefix('company.'); // 单域名访问
} else {
    Route::domain(env('company_URL'), $companyRoute)->prefix('company.'); // 独立域名访问
}