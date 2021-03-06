<?php namespace CjsView\Engines;

use Exception;

class PhpEngine implements EngineInterface {

	/**
	 * Get the evaluated contents of the view.
	 *
	 * @param  string  $path
	 * @param  array   $data
	 * @return string
	 */
	public function get($path, array $data = array())
	{
		return $this->evaluatePath($path, $data);
	}

	/**
	 * Get the evaluated contents of the view at the given path.
	 *
	 * @param  string  $__path
	 * @param  array   $__data
	 * @return string
	 */
	protected function evaluatePath($__path, $__data)
	{
		$obLevel = ob_get_level();

		ob_start();
		extract($__data);
		try {
			include $__path;
		} catch (Exception $e) {
			$this->handleViewException($e, $obLevel);
		}

		return ltrim(ob_get_clean());
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
		while (ob_get_level() > $obLevel)
		{
			ob_end_clean();
		}

		throw $e;
	}

}
