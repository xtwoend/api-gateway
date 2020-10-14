<?php

namespace Api\Gateway\Logger;

use Jenssegers\Mongodb\Eloquent\Model;


class DBLogger extends Model
{
	protected $connection='logger';
	protected $guarded = ['id'];
}