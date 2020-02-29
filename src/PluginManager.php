<?php
namespace hotswapp;
use Exception;
abstract class PluginManager
{
	/**
	 * @var array<string,Plugin> $loaded_plugins
	 */
	static $loaded_plugins = [];
	static $plugin_folders = [
		"plugins"
	];

	/**
	 * Loads all plugins in all PluginManager::$plugin_folders
	 *
	 * @return void
	 */
	static function loadPlugins(): void
	{
		$loaded_folders = [];
		foreach(self::$plugin_folders as $folder)
		{
			$folder = realpath($folder);
			if(in_array($folder, $loaded_folders))
			{
				continue;
			}
			array_push($loaded_folders, $folder);
			foreach(scandir($folder) as $name)
			{
				if(substr($name, -4) == ".php" && is_file("$folder/$name"))
				{
					$name = substr($name, 0, -4);
				}
				else if(!is_dir("$folder/$name") || !is_file("$folder/$name/$name.php"))
				{
					continue;
				}
				if(array_key_exists($name, self::$loaded_plugins))
				{
					echo "A plugin called $name is already loaded, not loading $name from ".$folder.DIRECTORY_SEPARATOR.$file."\n";
					continue;
				}
				try
				{
					self::$loaded_plugins[$name] = static::constructPlugin($folder, $name);
				}
				catch(Exception $e)
				{
					echo "Unhandled exception in plugin \"$name\": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
				}
			}
		}
	}

	/**
	 * @param string $folder
	 * @param string $name
	 * @return Plugin
	 */
	protected static function constructPlugin(string $folder, string $name)
	{
		return new Plugin($folder, $name);
	}

	/**
	 * @return void
	 */
	static function unloadAllPlugins(): void
	{
		PluginManager::$loaded_plugins = [];
	}

	/**
	 * Fires an Event to all loaded plugins.
	 *
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	static function fire(Event $event): bool
	{
		$type = get_class($event);
		$handlers = [];
		foreach(PluginManager::$loaded_plugins as $plugin)
		{
			if(isset($plugin->event_handlers[$type]))
			{
				array_push($handlers, $plugin->event_handlers[$type]);
			}
		}
		usort($handlers, function(array $a, array $b)
		{
			return $b["priority"] - $a["priority"];
		});
		try
		{
			foreach($handlers as $handler)
			{
				$handler["function"]($event);
			}
		}
		catch(Exception $e)
		{
			echo "Unhandled exception in plugin: ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
		}
		return in_array(CancellableEvent::class, class_uses($event)) ? $event->cancelled : false;
	}
}
