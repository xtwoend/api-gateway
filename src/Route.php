<?php

namespace Api\Gateway;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 */
class Route extends Model
{
	// protected $casts = [
 //        'middleware' => 'array',
 //    ];

	public function group()
	{
		return $this->belongsTo(Group::class);
	}

	public function services()
	{
		return $this->belongsToMany(Service::class, 'route_services', 'service_id', 'route_id');
	}

	public function scopeActive($query)
	{
		return $query->where('down', 0);
	}
}