<?php
// Converts a folder-style plugin into a .phar file for hotswapp >=1.2 to load.
if(empty($argv[1]))
{
	die(/** @lang text */ "Syntax: php pack-plugin.php <plugin name>\n");
}
if(ini_get("phar.readonly") != 0)
{
	passthru("php -d phar.readonly=0 ".$argv[0]." ".escapeshellarg($argv[1]));
	exit;
}
$phar = new Phar("plugins/".$argv[1].".phar");
$phar->buildFromDirectory("plugins/".$argv[1]);
echo "Successfully created ".$argv[1].".phar\n";
