<?php 

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model{
	
	protected $table = 'hrm_attendances';
	
	public function user(){
		return $this->belongsTo('\App\User','user_id');
	}

	public function designation(){
		return $this->belongsTo('\App\Models\Hrm\Designations','designation_id');
	}

	public function leave(){
		return $this->belongsTo('\App\Models\Hrm\Leaves','leave_id');
	}
	
}