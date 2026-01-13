<?php 

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Leaves extends Model{
	
	protected $table = 'hrm_leaves';
	
	public function user(){
		return $this->belongsTo('\App\User','user_id');
	}

	public function hrm_leave_type(){
		return $this->belongsTo('\App\Models\Hrm\LeaveTypes','type_id');
	}
	
}