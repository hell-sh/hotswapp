<?php
namespace hotswapp;
abstract class Event
{
	const PRIORITY_HIGHEST = 2;
	const PRIORITY_HIGH = 1;
	const PRIORITY_NORMAL = 0;
	const PRIORITY_LOW = -1;
	const PRIORITY_LOWEST = -2;
}
