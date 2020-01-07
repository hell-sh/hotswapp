<?php
namespace hotswapp;
trait CancellableEvent
{
	/**
	 * Whether the event was cancelled.
	 *
	 * @var boolean $cancelled
	 */
	public $cancelled = false;

	function cancel()
	{
		$this->cancelled = true;
	}
}
