<?php namespace CjsView\Contracts;

interface Renderable{

    /**
     * 获取渲染数据
     * @return string
     */
    public function render();

}