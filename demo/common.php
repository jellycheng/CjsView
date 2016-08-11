<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';


if ( ! function_exists('view')) {
    function view($view = null, $data = array(), $mergeData = array())
    {
        static $factoryObj;
        if (!$factoryObj) {
            $viewCfig = include __DIR__ . '/config/view.php';
            $fileObj = new \CjsView\Filesystem();
            //引擎解决者
            $engineResolverObj = new \CjsView\Engines\EngineResolver();
            \CjsView\Engines\EngineRegister::getInstance()->setConfig(array_merge($viewCfig, ['files' => new \CjsView\Filesystem()]))->init($engineResolverObj);
            $fileViewFinderObj = new \CjsView\FileViewFinder($fileObj, $viewCfig['paths']);
            $factoryObj = new \CjsView\Factory($engineResolverObj, $fileViewFinderObj);
        }
        if (func_num_args() === 0) {
            return $factoryObj;
        }
        return $factoryObj->make($view, $data, $mergeData);
    }
}

if ( ! function_exists('array_except')) {

    function  array_except($array, $keys)
    {
        return array_diff_key($array, array_flip((array)$keys));
    }

}