<?php /** @noinspection PhpIncludeInspection */
namespace hotswapp;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
class Plugin
{
	/**
	 * The name of the plugin.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * @var array<array{function:Closure,priority:int}> $event_handlers
	 */
	public $event_handlers = [];
	protected $unregistered = false;

	/**
	 * Don't call this unless you know what you're doing.
	 *
	 * @param string $folder The path of the folder the plugin was loaded from.
	 * @param string $name The name of the plugin.
	 * @see PluginManager::loadPlugins()
	 */
	function __construct(string $folder, string $name)
	{
		$this->name = $name;
		if(is_file("$folder/$name.php"))
		{
			require "$folder/$name.php";
		}
		else if(is_file("$folder/$name/$name.php"))
		{
			require "$folder/$name/$name.php";
		}
		else if(is_file("$folder/$name.phar"))
		{
			\Phar::loadPhar("$folder/$name.phar", "$name.phar");
			require "phar://$name.phar/$name.php";
		}
		else
		{
			throw new RuntimeException("Couldn't find out how to load plugin \"$name\"");
		}
	}

	/**
	 * Fires the event handler for the given event with its data as parameter.
	 *
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	function fire(Event $event): bool
	{
		if($this->unregistered)
		{
			throw new RuntimeException("Call to Plugin::fire() after Plugin::unregister()");
		}
		$type = get_class($event);
		if(isset($this->event_handlers[$type]))
		{
			($this->event_handlers[$type]["function"])($event);
		}
		return in_array(CancellableEvent::class, class_uses($event)) ? $event->cancelled : false;
	}

	/**
	 * Defines a function to be called to handle the given event.
	 *
	 * @param callable $function The function. The first parameter should explicitly declare its type to be a decendant of Event.
	 * @param int $priority The priority of the event handler. The higher the priority, the earlier it will be executed. Use a high value if you plan to cancel the event.
	 * @return Plugin $this
	 */
	protected function on(callable $function, int $priority = Event::PRIORITY_NORMAL): Plugin
	{
		if($this->unregistered)
		{
			throw new RuntimeException("Call to Plugin::on() after Plugin::unregister()");
		}
		try
		{
			$params = (new ReflectionFunction($function))->getParameters();
			if(count($params) != 1)
			{
				throw new InvalidArgumentException("Callable needs to have exactly one parameter.");
			}
			$param = $params[0];
			if(!$param->hasType())
			{
				throw new InvalidArgumentException("Callable's parameter needs to explicitly declare parameter type.");
			}
			$type = $param->getType();
			/** @noinspection PhpDeprecationInspection */
			$type = $type instanceof ReflectionNamedType ? $type->getName() : $type->__toString();
			$class = new ReflectionClass($type);
			if(!$class->isSubclassOf("hotswapp\\Event"))
			{
				throw new InvalidArgumentException("Callable's parameter type needs to be a decendant of \\hotswapp\\Event.");
			}
			$this->event_handlers[$type] = [
				"function" => $function,
				"priority" => $priority
			];
		}
		catch(ReflectionException $e)
		{
			throw new RuntimeException("Unexpected exception: ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
		}
		return $this;
	}

	/**
	 * Unregisters the plugin, including its event handlers and its commands.
	 * Make sure your plugin has no statements other than `return;` after this.
	 *
	 * @return void
	 */
	protected function unregister(): void
	{
		unset(PluginManager::$loaded_plugins[$this->name]);
		$this->event_handlers = [];
		$this->unregistered = true;
	}
}
