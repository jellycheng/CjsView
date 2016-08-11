<?php
require_once __DIR__ . '/common.php';
$viewCfig = include __DIR__ . '/config/view.php';

$fileObj = new \CjsView\Filesystem();
$engineResolverObj = new \CjsView\Engines\EngineResolver();
$fileViewFinderObj = new \CjsView\FileViewFinder($fileObj, $viewCfig['paths']);

$findTplFile = $fileViewFinderObj->find("layout");
echo $findTplFile . PHP_EOL;

try{
    //模板文件未找到抛异常
    $findTplFile = $fileViewFinderObj->find("test");
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL;
}

//添加模板扩展名
$fileViewFinderObj->addExtension('yaml');

//可支持的文件扩展名
$extFile = $fileViewFinderObj->getExtensions();
var_export($extFile);
echo PHP_EOL;


//新增模板查找目录
$fileViewFinderObj->addLocation(__DIR__ . '/v/');
//模板查找目录
$filePath = $fileViewFinderObj->getPaths();
var_export($filePath);
echo PHP_EOL;

$hints = $fileViewFinderObj->getHints();
var_export($hints);
echo PHP_EOL;

try{
    //模板文件未找到抛异常
    $findTplFile = $fileViewFinderObj->find("jelly::v1.page.index");
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL;
}


#设置命名空间查找模板目录，数组尾部追加，覆盖方式
$fileViewFinderObj->addNamespace('jelly', [__DIR__.'/views/jelly']);
#设置命名空间查找模板目录，数组头部追加
$fileViewFinderObj->prependNamespace('jelly', [__DIR__.'/views/jelly2']);
try{
    //模板文件未找到抛异常
    $findTplFile = $fileViewFinderObj->find("jelly::v1.page.index");
    echo $findTplFile.PHP_EOL;
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL;
}

var_export($fileViewFinderObj->getHints());
echo PHP_EOL;