<?php 

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Holidays extends Model{
	
	protected $table = 'hrm_holidays';
	protected $fillable = [
		'date',
		'rel_id',
		'rel_type',
		'name',
		'created_at',
		'updated_at',
	];
	
}