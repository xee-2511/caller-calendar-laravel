<?php 

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class UsedLeaves extends Model{
	
	protected $table = 'hrm_used_leaves';
	
	public function user(){
		return $this->belongsTo('\App\User','user_id');
	}

	public function leave_type(){
		return $this->belongsTo('\App\Models\Hrm\Leaves','leave_type_id');
	}
	
}