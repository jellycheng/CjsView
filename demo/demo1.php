<?php
require_once __DIR__ . '/common.php';
//$viewCfig = include __DIR__ . '/config/view.php';
//
//$fileObj = new \CjsView\Filesystem();
////引擎解决者
//$engineResolverObj = new \CjsView\Engines\EngineResolver();
// \CjsView\Engines\EngineRegister::getInstance()->setConfig(array_merge($viewCfig, ['files'=>new \CjsView\Filesystem()]))->init($engineResolverObj);
//$fileViewFinderObj = new \CjsView\FileViewFinder($fileObj, $viewCfig['paths']);
//
//$factoryObj = new \CjsView\Factory($engineResolverObj, $fileViewFinderObj);
$factoryObj = view();
$factoryObj->share('tpl_share_jelly1', "hello word,所有模板共享的数据");
$tplData = [
        'title'=>'xx网',
        'keywords'=>'',
        'data'=>[
                'page'=>1,
                'perPage'=>100,
                'hi'=>'hello',
        ],
];
//返回view对象
$viewObj = $factoryObj->make('page.index', $tplData);
$viewObj['good'] = "very good!";
echo $viewObj; //渲染



