<?php


namespace Api\Gateway\Events;

use Api\Gateway\Services\Service;
use Illuminate\Queue\SerializesModels;


class HealthServiceMonitor 
{
	use SerializesModels;

	public $service;

	public function __construct(Service $service)
	{
		$this->service = $service;
	}
}