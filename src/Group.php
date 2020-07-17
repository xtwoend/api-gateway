<?php

namespace Api\Gateway;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 */
class Group extends Model
{
	
	public function routes()
	{
		return $this->hasMany(Route::class);
	}
}