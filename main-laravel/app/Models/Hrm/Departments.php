<?php 

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Departments extends Model{
	
	protected $table = 'hrm_departments';
	
	public function designations(){
		return $this->hasMany('\App\Models\Hrm\Designations','department_id');
	}

	public function managers(){
		return $this->belongsToMany('\App\User','hrm_department_managers','department_id','user_id');
	}
	
}