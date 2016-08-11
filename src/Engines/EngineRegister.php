<?php
namespace CjsView\Engines;

class EngineRegister {

    protected static $instance = null;
    protected $config = ['compiled'=>'',
                            'files'=>'', //\CjsView\Filesystem
                            ];

    protected function __construct() {

    }

    public static function getInstance() {
        if(is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function setConfig($config) {
        $this->config = array_merge($this->config, (array)$config);
        return $this;
    }

    public function init($resolver, $config=null) {
        static $isInit = false;
        if($isInit) {
            return '';
        }
        $isInit = true;
        if($config && is_array($config)) {
            $this->setConfg($config);
        }
        foreach (array('php', 'blade') as $engine)
        {
            $this->{'register'.ucfirst($engine).'Engine'}($resolver);
        }
    }

    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function() { return new PhpEngine; });
    }

    public function registerBladeEngine($resolver)
    {
        $bladeCompiler = new \CjsView\Compilers\BladeCompiler($this->config['files'], $this->config['compiled']);

        $resolver->register('blade', function() use ($bladeCompiler)
        {
            return new CompilerEngine($bladeCompiler);
        });
    }

}
