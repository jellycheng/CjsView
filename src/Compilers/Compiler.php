<?php namespace CjsView\Compilers;

use CjsView\Filesystem;

abstract class Compiler {

	/**
	 * The Filesystem instance.
	 *
	 * @var \CjsView\Filesystem
	 */
	protected $files;

	/**
	 * Get the cache path for the compiled views.
	 *
	 * @var string
	 */
	protected $cachePath;

	/**
	 * Create a new compiler instance.
	 *
	 * @param  \CjsView\Filesystem  $files
	 * @param  string  $cachePath
	 * @return void
	 */
	public function __construct(Filesystem $files, $cachePath)
	{
		$this->files = $files;
		$this->cachePath = $cachePath;
	}

	/**
	 * Get the path to the compiled version of a view.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function getCompiledPath($path)
	{
		return $this->cachePath.'/'.md5($path);
	}

	/**
	 * Determine if the view at the given path is expired.
	 *
	 * @param  string  $path
	 * @return bool
	 */
	public function isExpired($path)
	{
		$compiled = $this->getCompiledPath($path);

		if ( ! $this->cachePath || ! $this->files->exists($compiled))
		{
			return true;
		}

		$lastModified = $this->files->lastModified($path);

		return $lastModified >= $this->files->lastModified($compiled);
	}

}
