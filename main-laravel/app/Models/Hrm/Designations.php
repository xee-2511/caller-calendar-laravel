<?php 

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Designations extends Model{
	
	protected $table = 'hrm_designations';
	
	public function department(){
		return $this->belongsTo('\App\Models\Hrm\Departments','department_id');
	}

	public function managers(){
		return $this->belongsToMany('\App\User','hrm_designation_managers','designation_id','user_id');
	}
	
}