<?php 

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class WorkingSessions extends Model{
	
	protected $table = 'hrm_working_sessions';
	
	public function user(){
		return $this->belongsTo('\App\User','user_id');
	}
	
}