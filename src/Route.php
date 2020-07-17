<?php

namespace Api\Gateway;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 */
class Route extends Model
{
	public function group()
	{
		return $this->belongsTo(Group::class);
	}

	public function scopeActive($query)
	{
		return $query->where('active', 1);
	}
}