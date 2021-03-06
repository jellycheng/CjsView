<?php namespace CjsView\Engines;

use ErrorException;
use CjsView\Compilers\CompilerInterface;

class CompilerEngine extends PhpEngine {

	/**
	 * The Blade compiler instance.
	 *
	 * @var \CjsView\Compilers\CompilerInterface
	 */
	protected $compiler;

	/**
	 * A stack of the last compiled templates.
	 *
	 * @var array
	 */
	protected $lastCompiled = array();

	/**
	 * Create a new Blade view engine instance.
	 *
	 * @param  \CjsView\Compilers\CompilerInterface  $compiler
	 * @return void
	 */
	public function __construct(CompilerInterface $compiler)
	{
		$this->compiler = $compiler;
	}

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  string  $path
	 * @param  array   $data
	 * @return string
	 */
	public function get($path, array $data = array())
	{
		$this->lastCompiled[] = $path;

		if ($this->compiler->isExpired($path))
		{
			$this->compiler->compile($path);
		}

		$compiled = $this->compiler->getCompiledPath($path);

		$results = $this->evaluatePath($compiled, $data);

		array_pop($this->lastCompiled);

		return $results;
	}

	/**
	 * Handle a view exception.
	 *
	 * @param  \Exception  $e
	 * @param  int  $obLevel
	 * @return void
	 *
	 * @throws $e
	 */
	protected function handleViewException($e, $obLevel)
	{
		$e = new ErrorException($this->getMessage($e), 0, 1, $e->getFile(), $e->getLine(), $e);

		parent::handleViewException($e, $obLevel);
	}

	/**
	 * Get the exception message for an exception.
	 *
	 * @param  \Exception  $e
	 * @return string
	 */
	protected function getMessage($e)
	{
		return $e->getMessage().' (View: '.realpath(last($this->lastCompiled)).')';
	}

	/**
	 * Get the compiler implementation.
	 *
	 * @return \CjsView\Compilers\CompilerInterface
	 */
	public function getCompiler()
	{
		return $this->compiler;
	}

}
