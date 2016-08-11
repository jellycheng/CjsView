<?php namespace CjsView;

use  CjsView\Exception\FileNotFoundException;

class Filesystem {

    public function exists($path)
    {
        return file_exists($path);
    }

    public function isFile($file)
    {
        return is_file($file);
    }

    public function isWritable($path)
    {
        return is_writable($path);
    }

    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    public function get($path)
    {
        if ($this->isFile($path)) return file_get_contents($path);

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    public function lastModified($path)
    {
        return filemtime($path);
    }

}