<?php namespace CjsView;

use Closure;
use InvalidArgumentException;
use CjsView\Engines\EngineResolver;
use CjsView\Contracts\Factory as FactoryContract;
use CjsView\Contracts\ViewFinderInterface as ViewFinderInterface;

class Factory implements FactoryContract {

	/**
	 * The engine implementation.
	 *
	 * @var \CjsView\Engines\EngineResolver
	 */
	protected $engines;

	/**
	 * The view finder implementation.
	 *
	 * @var \CjsView\Contracts\ViewFinderInterface
	 */
	protected $finder;

	/**
	 * The event dispatcher instance.
	 *
	 */
	protected $events;

	/**
	 * The IoC container instance.
	 */
	protected $container;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = array();

	/**
	 * Array of registered view name aliases.
	 *
	 * @var array
	 */
	protected $aliases = array();

	/**
	 * All of the registered view names.
	 *
	 * @var array
	 */
	protected $names = array();

	/**
	 * The extension to engine bindings.
	 * ['扩展名'='模板引擎解决者标识', ]
	 * @var array
	 */
	protected $extensions = array('blade.php' => 'blade', 'php' => 'php');

	/**
	 * The view composer events.
	 *
	 * @var array
	 */
	protected $composers = array();

	/**
	 * All of the finished, captured sections.
	 *
	 * @var array
	 */
	protected $sections = array();

	/**
	 * The stack of in-progress sections.
	 *
	 * @var array
	 */
	protected $sectionStack = array();

	/**
	 * The number of active rendering operations.
	 *
	 * @var int
	 */
	protected $renderCount = 0;

	/**
	 * Create a new view factory instance.
	 *
	 * @param  \CjsView\Engines\EngineResolver  $engines
	 * @param  \CjsView\Contracts\ViewFinderInterface  $finder
	 * @param   $events
	 * @return void
	 */
	public function __construct(EngineResolver $engines, ViewFinderInterface $finder, $events='')
	{
		$this->finder = $finder;
		$this->events = $events;
		$this->engines = $engines;

		$this->share('__env', $this);
	}

	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param  string  $path 模板文件，绝对目录+文件名
	 * @param  array   $data
	 * @param  array   $mergeData
	 * @return \CjsView\View
	 */
	public function file($path, $data = array(), $mergeData = array())
	{
		$data = array_merge($mergeData, $this->parseData($data));
		$this->callCreator($view = new View($this, $this->getEngineFromPath($path), $path, $path, $data));
		return $view;
	}

	/**
	 * Get the evaluated view contents for the given view.
	 *
	 * @param  string  $view 视图文件
	 * @param  array   $data 视图数据
	 * @param  array   $mergeData
	 * @return \CjsView\View
	 */
	public function make($view, $data = array(), $mergeData = array())
	{
		if (isset($this->aliases[$view])) $view = $this->aliases[$view];
		//规范视图文件
		$view = $this->normalizeName($view);
		#模板文件,自动拼接扩展名
		$path = $this->finder->find($view);

		$data = array_merge($mergeData, $this->parseData($data));
		$this->callCreator($view = new View($this, $this->getEngineFromPath($path), $view, $path, $data));
		return $view;
	}

	/**
	 * Normalize a view name.
	 * 把/替换成点
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function normalizeName($name)
	{
		$delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;
		if (strpos($name, $delimiter) === false)
		{
			return str_replace('/', '.', $name);
		}
		list($namespace, $name) = explode($delimiter, $name);
		return $namespace . $delimiter . str_replace('/', '.', $name);
	}

	/**
	 * Parse the given data into a raw array.
	 *
	 * @param  mixed  $data
	 * @return array
	 */
	protected function parseData($data)
	{
		return $data; //todo
		//return $data instanceof Arrayable ? $data->toArray() : $data;
	}

	/**
	 * Get the evaluated view contents for a named view.
	 *
	 * @param  string  $view
	 * @param  mixed   $data
	 * @return \CjsView\View
	 */
	public function of($view, $data = array())
	{
		return $this->make($this->names[$view], $data);
	}

	/**
	 * Register a named view.
	 *
	 * @param  string  $view 模板文件
	 * @param  string  $name 代号
	 * @return void
	 */
	public function name($view, $name)
	{
		$this->names[$name] = $view;
	}

	/**
	 * Add an alias for a view.
	 *
	 * @param  string  $view
	 * @param  string  $alias
	 * @return void
	 */
	public function alias($view, $alias)
	{
		$this->aliases[$alias] = $view;
	}

	/**
	 * Determine if a given view exists.
	 * 模板文件是否存在
	 * @param  string  $view
	 * @return bool
	 */
	public function exists($view)
	{
		try
		{
			$this->finder->find($view);
		}
		catch (InvalidArgumentException $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the rendered contents of a partial from a loop.
	 *
	 * @param  string  $view
	 * @param  array   $data
	 * @param  string  $iterator
	 * @param  string  $empty
	 * @return string
	 */
	public function renderEach($view, $data, $iterator, $empty = 'raw|')
	{
		$result = '';
		if (count($data) > 0)
		{
			foreach ($data as $key => $value)
			{
				$data = array('key' => $key, $iterator => $value);

				$result .= $this->make($view, $data)->render();
			}
		} else {
			if (starts_with($empty, 'raw|'))
			{
				$result = substr($empty, 4);
			}
			else
			{
				$result = $this->make($empty)->render();
			}
		}

		return $result;
	}

	/**
	 * Get the appropriate view engine for the given path.
	 * 获取解决者对象
	 * @param  string  $path
	 * @return \CjsView\Engines\EngineInterface
	 *
	 * @throws \InvalidArgumentException
	 */
	public function getEngineFromPath($path)
	{
		if ( ! $extension = $this->getExtension($path))
		{
			throw new InvalidArgumentException("Unrecognized extension in file: $path");
		}

		$engine = $this->extensions[$extension];
		return $this->engines->resolve($engine);
	}

	/**
	 * Get the extension used by the view file.
	 *
	 * @param  string  $path 模板文件
	 * @return string
	 */
	protected function getExtension($path)
	{
		$extensions = array_keys($this->extensions);
		return $this->array_first($extensions, function($key, $extName) use ($path)
		{
			if ((string) $extName === substr($path, -strlen($extName))) return true;
			return false;
		});
	}

	public function array_first($array, $callback, $default = null)
	{
		foreach ($array as $key => $value)
		{
			if (call_user_func($callback, $key, $value)) return $value;
		}
		return $default instanceof Closure ? $default() : $default;
	}

	/**
	 * Add a piece of shared data to the environment.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function share($key, $value = null)
	{
		if ( ! is_array($key)) return $this->shared[$key] = $value;

		foreach ($key as $innerKey => $innerValue)
		{
			$this->share($innerKey, $innerValue);
		}
	}

	/**
	 * Register a view creator event.
	 *
	 * @param  array|string     $views
	 * @param  \Closure|string  $callback
	 * @return array
	 */
	public function creator($views, $callback)
	{
		$creators = array();

		foreach ((array) $views as $view)
		{
			$creators[] = $this->addViewEvent($view, $callback, 'creating: ');
		}

		return $creators;
	}

	/**
	 * Register multiple view composers via an array.
	 *
	 * @param  array  $composers
	 * @return array
	 */
	public function composers(array $composers)
	{
		$registered = array();
		foreach ($composers as $callback => $views)
		{
			$registered = array_merge($registered, $this->composer($views, $callback));
		}
		return $registered;
	}

	/**
	 * Register a view composer event.
	 *
	 * @param  array|string  $views
	 * @param  \Closure|string  $callback
	 * @param  int|null  $priority
	 * @return array
	 */
	public function composer($views, $callback, $priority = null)
	{
		$composers = array();
		foreach ((array) $views as $view)
		{
			$composers[] = $this->addViewEvent($view, $callback, 'composing: ', $priority);
		}
		return $composers;
	}

	/**
	 * Add an event for a given view.
	 *
	 * @param  string  $view
	 * @param  \Closure|string  $callback
	 * @param  string  $prefix
	 * @param  int|null  $priority
	 * @return \Closure
	 */
	protected function addViewEvent($view, $callback, $prefix = 'composing: ', $priority = null)
	{
		$view = $this->normalizeName($view);
		if ($callback instanceof Closure)
		{
			$this->addEventListener($prefix.$view, $callback, $priority);
			return $callback;
		} elseif (is_string($callback)) {
			return $this->addClassEvent($view, $callback, $prefix, $priority);
		}
	}

	/**
	 * Register a class based view composer.
	 *
	 * @param  string    $view
	 * @param  string    $class
	 * @param  string    $prefix
	 * @param  int|null  $priority
	 * @return \Closure
	 */
	protected function addClassEvent($view, $class, $prefix, $priority = null)
	{
		$name = $prefix.$view;
		$callback = $this->buildClassEventCallback($class, $prefix);
		$this->addEventListener($name, $callback, $priority);
		return $callback;
	}

	/**
	 * Add a listener to the event dispatcher.
	 *
	 * @param  string    $name
	 * @param  \Closure  $callback
	 * @param  int      $priority
	 * @return void
	 */
	protected function addEventListener($name, $callback, $priority = null)
	{
		if(!$this->events || !is_object($this->events)) {
			return '';
		}
		if (is_null($priority))
		{
			$this->events->listen($name, $callback);
		} else {
			$this->events->listen($name, $callback, $priority);
		}
	}

	/**
	 * Build a class based container callback Closure.
	 *
	 * @param  string  $class
	 * @param  string  $prefix
	 * @return \Closure
	 */
	protected function buildClassEventCallback($class, $prefix)
	{
		list($class, $method) = $this->parseClassEvent($class, $prefix);

		return function() use ($class, $method)
		{
			$callable = array($this->container->make($class), $method);

			return call_user_func_array($callable, func_get_args());
		};
	}

	/**
	 * Parse a class based composer name.
	 *
	 * @param  string  $class
	 * @param  string  $prefix
	 * @return array
	 */
	protected function parseClassEvent($class, $prefix)
	{
		if (str_contains($class, '@'))
		{
			return explode('@', $class);
		}
		$method = str_contains($prefix, 'composing') ? 'compose' : 'create';
		return array($class, $method);
	}

	/**
	 * Call the composer for a given view.
	 *
	 * @param  \CjsView\View  $view
	 * @return void
	 */
	public function callComposer(View $view)
	{
		if(!$this->events || !is_object($this->events)) {
			return false;
		}
		$this->events->fire('composing: '.$view->getName(), array($view));
	}

	/**
	 * Call the creator for a given view.
	 *
	 * @param  \CjsView\View  $view
	 * @return void
	 */
	public function callCreator(View $view)
	{
		if(!is_object($this->events) || !$this->events) {
			return false;
		}
		$this->events->fire('creating: '.$view->getName(), array($view));
	}

	/**
	 * Start injecting content into a section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	public function startSection($section, $content = '')
	{
		if ($content === '') {
			if (ob_start()) {
				$this->sectionStack[] = $section;
			}
		} else {
			$this->extendSection($section, $content);
		}
	}

	/**
	 * Inject inline content into a section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	public function inject($section, $content)
	{
		return $this->startSection($section, $content);
	}

	/**
	 * Stop injecting content into a section and return its contents.
	 *
	 * @return string
	 */
	public function yieldSection()
	{
		return $this->yieldContent($this->stopSection());
	}

	/**
	 * Stop injecting content into a section.
	 *
	 * @param  bool  $overwrite
	 * @return string
	 */
	public function stopSection($overwrite = false)
	{
		$last = array_pop($this->sectionStack);

		if ($overwrite)
		{
			$this->sections[$last] = ob_get_clean();
		}
		else
		{
			$this->extendSection($last, ob_get_clean());
		}

		return $last;
	}

	/**
	 * Stop injecting content into a section and append it.
	 *
	 * @return string
	 */
	public function appendSection()
	{
		$last = array_pop($this->sectionStack);

		if (isset($this->sections[$last]))
		{
			$this->sections[$last] .= ob_get_clean();
		}
		else
		{
			$this->sections[$last] = ob_get_clean();
		}

		return $last;
	}

	/**
	 * Append content to a given section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	protected function extendSection($section, $content)
	{
		if (isset($this->sections[$section]))
		{
			$content = str_replace('@parent', $content, $this->sections[$section]);
		}

		$this->sections[$section] = $content;
	}

	/**
	 * Get the string contents of a section.
	 *
	 * @param  string  $section
	 * @param  string  $default
	 * @return string
	 */
	public function yieldContent($section, $default = '')
	{
		$sectionContent = $default;

		if (isset($this->sections[$section]))
		{
			$sectionContent = $this->sections[$section];
		}

		$sectionContent = str_replace('@@parent', '--parent--holder--', $sectionContent);

		return str_replace(
			'--parent--holder--', '@parent', str_replace('@parent', '', $sectionContent)
		);
	}

	/**
	 * Flush all of the section contents.
	 *
	 * @return void
	 */
	public function flushSections()
	{
		$this->sections = array();
		$this->sectionStack = array();
	}

	/**
	 * Flush all of the section contents if done rendering.
	 *
	 * @return void
	 */
	public function flushSectionsIfDoneRendering()
	{
		if ($this->doneRendering()) $this->flushSections();
	}

	/**
	 * Increment the rendering counter.
	 *
	 * @return void
	 */
	public function incrementRender()
	{
		$this->renderCount++;
	}

	/**
	 * Decrement the rendering counter.
	 *
	 * @return void
	 */
	public function decrementRender()
	{
		$this->renderCount--;
	}

	/**
	 * Check if there are no active render operations.
	 *
	 * @return bool
	 */
	public function doneRendering()
	{
		return $this->renderCount == 0;
	}

	/**
	 * Add a location to the array of view locations.
	 * 新增模板查找目录
	 * @param  string  $location
	 * @return void
	 */
	public function addLocation($location)
	{
		$this->finder->addLocation($location);
	}

	/**
	 * Add a new namespace to the loader.
	 * 设置命名空间查找模板目录，数组尾部追加
	 * @param  string  $namespace
	 * @param  string|array  $hints
	 * @return void
	 */
	public function addNamespace($namespace, $hints)
	{
		$this->finder->addNamespace($namespace, $hints);
	}

	/**
	 * Prepend a new namespace to the loader.
	 * 设置命名空间查找模板目录，数组头部追加
	 * @param  string  $namespace
	 * @param  string|array  $hints
	 * @return void
	 */
	public function prependNamespace($namespace, $hints)
	{
		$this->finder->prependNamespace($namespace, $hints);
	}

	/**
	 * Register a valid view extension and its engine.
	 *
	 * @param  string    $extension
	 * @param  string    $engine
	 * @param  \Closure  $resolver
	 * @return void
	 */
	public function addExtension($extension, $engine, $resolver = null)
	{
		$this->finder->addExtension($extension);

		if (isset($resolver))
		{
			$this->engines->register($engine, $resolver);
		}
		unset($this->extensions[$extension]);
		$this->extensions = array_merge(array($extension => $engine), $this->extensions);
	}

	/**
	 * Get the extension to engine bindings.
	 *
	 * @return array
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}

	/**
	 * Get the engine resolver instance.
	 *
	 * @return \CjsView\Engines\EngineResolver
	 */
	public function getEngineResolver()
	{
		return $this->engines;
	}

	/**
	 * Get the view finder instance.
	 *
	 * @return \CjsView\Contracts\ViewFinderInterface
	 */
	public function getFinder()
	{
		return $this->finder;
	}

	/**
	 * Set the view finder instance.
	 *
	 * @param  \CjsView\Contracts\ViewFinderInterface  $finder
	 * @return void
	 */
	public function setFinder(ViewFinderInterface $finder)
	{
		$this->finder = $finder;
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return
	 */
	public function getDispatcher()
	{
		return $this->events;
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param  \CjsView\Contracts\Events\Dispatcher
	 * @return void
	 */
	public function setDispatcher(Dispatcher $events)
	{
		$this->events = $events;
	}

	/**
	 * Get the IoC container instance.
	 * app对象
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Set the IoC container instance.
	 * 注入app对象
	 * @param  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Get an item from the shared data.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function shared($key, $default = null)
	{
		return $this->array_get($this->shared, $key, $default);
	}

	/**
	 * Get all of the shared data for the environment.
	 *
	 * @return array
	 */
	public function getShared()
	{
		return $this->shared;
	}

	/**
	 * Get the entire array of sections.
	 *
	 * @return array
	 */
	public function getSections()
	{
		return $this->sections;
	}

	/**
	 * Get all of the registered named views in environment.
	 *
	 * @return array
	 */
	public function getNames()
	{
		return $this->names;
	}

	public static function array_get($array, $key, $default = null)
	{
		if (is_null($key)) return $array;

		if (isset($array[$key])) return $array[$key];

		foreach (explode('.', $key) as $segment)
		{
			if ( ! is_array($array) || ! array_key_exists($segment, $array))
			{
				return $default instanceof Closure ? $default() : $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}

}
