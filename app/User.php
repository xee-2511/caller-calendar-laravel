<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
#use OwenIt\Auditing\Contracts\UserResolver;
use Illuminate\Notifications\Notifiable;
use \Illuminate\Support\Facades\Redis;
use \Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class User extends Model implements AuditableContract, AuthenticatableContract, CanResetPasswordContract { //UserResolvers

    use Authenticatable,
        Authorizable,
        CanResetPassword,
        Auditable,
        Notifiable;

    protected $auditEnabled = true;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'email',
        'activation_code', 'is_active', 'password', 'is_online'];
    protected $primaryKey = 'id';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function getId() {
        return $this->id;
    }

    public function setid($user_id) {
        $this->id = $user_id;
    }

    /**
     * Checks a Permission
     * @param  String permission Slug of a permission (i.e: manage_user)
     * @return Boolean true if has permission, otherwise false
     */
    public function can($permission = null) {
        return $this->checkIfHavePermission($permission);
        //return !is_null($permission) && $this->checkPermission($permission);
    }

    private function checkIfHavePermission($permission = null) {
        $return = false;
        $havePermissions = Redis::get('hpermissions:' . $this->id);

        if ($havePermissions) {
            $havePermissions = json_decode($havePermissions, true);
            if (array_key_exists($permission, $havePermissions)) {
                return true;
            }
        } else {
            $havePermissions = array();
            if (count($this->roles)) {
                foreach ($this->roles as $role) {
                    $rolePermissions = $role->permissions;
                    foreach ($rolePermissions as $permissions) {
                        $havePermissions[$permissions->name] = 1;
                    }

                    /*
                      $havePermission = $role->permissions->where('name', $permission);
                      if ($havePermission->isNotEmpty()) {
                      $return = true;
                      break;
                      } */
                }
            }
            $user_permissions = $this->permissions;
            foreach ($user_permissions as $permissions) {
                $havePermissions[$permissions->name] = 1;
            }
            if (count($havePermissions)) {
                Redis::set('hpermissions:' . $this->id, json_encode($havePermissions));
                Redis::Expire('hpermissions:' . $this->id, 600);
                if (array_key_exists($permission, $havePermissions)) {
                    return true;
                }
            }
        }
        return $return;
    }

    /**
     * Check if the permission matches with any permission user has
     * @param  String permission slug of a permission
     * @return Boolean true if permission exists, otherwise false
     */
    protected function checkPermission($perm) {
        $permissions = $this->getAllPermissionsFormAllRoles();
        $permissionArray = is_array($perm) ? $perm : [$perm];
        return count(array_intersect($permissions, $permissionArray));
    }

    /**
     * Get all permission slugs from all permissions of all roles
     *
     * @return Array of permission slugs
     */
    protected function getAllPermissionsFormAllRoles() {
        $permissionsArray = [];
        $permissions = $this->roles->load('permissions'); //->fetch('permissions')->toArray();
        $permissions = $permissions->toArray();
        return array_map('strtolower', array_unique(array_flatten(array_map(function ($permission) {
                                    return array_fetch($permission, 'name');
                                }, $permissions))));
    }

    public function accounts() {
        return $this->belongsToMany('App\Account', 'account_user', 'user_id', 'account_id');
    }

    public function accountsWithoutPivot() {
        return $this->hasMany('App\AccountUser', 'user_id', 'id');
    }

    public function getAdvAccounts() {
        $except_advs = [];
        foreach ($this->roles()->pluck('exclude_adv_ids') as $exclude) {
            $except_advs = array_merge($except_advs, json_decode($exclude, true) ?? []);
        }
        if ($this->can('manage_all_advertisers_except')) {
            if ($this->can('ADMIN_GLOBAL')) {
                return $this->hasManyThrough('App\Account', 'App\UserAgency', 'user_id', 'agency_id', 'id', 'agency_id')->whereNotIn('id', $except_advs)->where('type', '=', 'ADVERTISER');
            } else {
                return $this->hasMany('App\Account', 'agency_id', 'agency_id')->whereNotIn('id', $except_advs)->where('type', '=', 'ADVERTISER');
            }
        } else {
            return $this->accounts()->whereNotIn('accounts.id', $except_advs)->where('type', '=', 'ADVERTISER');
        }
    }

    public function getPubAccounts() {
        $except_pubs = [];
        foreach ($this->roles()->pluck('exclude_pub_ids') as $exclude) {
            $except_pubs = array_merge($except_pubs, json_decode($exclude, true) ?? []);
        }
        if ($this->can('manage_all_publishers_except')) {
            if ($this->can('ADMIN_GLOBAL')) {
                return $this->hasManyThrough('App\Account', 'App\UserAgency', 'user_id', 'agency_id', 'id', 'agency_id')->whereNotIn('id', $except_pubs)->where('type', '=', 'PUBLISHER');
            } else {
                return $this->hasMany('App\Account', 'agency_id', 'agency_id')->whereNotIn('id', $except_pubs)->where('type', '=', 'PUBLISHER');
            }
        } else {
            return $this->accounts()->whereNotIn('accounts.id', $except_pubs)->where('type', '=', 'PUBLISHER');
        }
    }

    public function advertiser() {
        return $this->hasOneThrough(
                        'App\Advertisers',
                        'App\AccountUser',
                        'user_id', // Foreign key on the cars table...
                        'account_id', // Foreign key on the owners table...
                        'id', // Local key on the mechanics table...
                        'account_id' // Local key on the cars table...
                );
    }

    public function publisher() {
        return $this->hasOneThrough(
                        'App\Affiliates',
                        'App\AccountUser',
                        'user_id', // Foreign key on the cars table...
                        'account_id', // Foreign key on the owners table...
                        'id', // Local key on the mechanics table...
                        'account_id' // Local key on the cars table...
                );
    }

    public function esigner_permissions() {
        $result = DB::table('signer_user_permissions')->where("user_id", $this->id)->first();
        return $result->permissions ?? '[]';
    }

    /*
      |--------------------------------------------------------------------------
      | Relationship Methods
      |--------------------------------------------------------------------------
     */

    /**
     * Many-To-Many Relationship Method for accessing the User->roles
     *
     * @return QueryBuilder Object
     */
    public function roles() {
        return $this->belongsToMany('App\Role', 'role_user', 'user_id', 'role_id');
    }

    public function permissions() {
        return $this->belongsToMany('App\Permission', 'permission_user', 'user_id', 'permission_id');
    }

    public function rolesWithoutPivot() {
        return $this->hasMany('\App\RoleUser', 'user_id', 'id');
    }

    public function agencies() {
        return $this->belongsToMany('App\Agency', 'user_agency', 'user_id', 'agency_id');
    }

    public function networks() {
        return $this->belongsToMany('App\Network', 'user_network', 'user_id', 'network_id');
    }

    public function agency() {
        return $this->belongsTo('App\Agency');
    }

    public function disabled_notifications_email() {
        return $this->belongsToMany('App\EmailType', 'disable_notifications', 'user_id', 'email_type_id');
    }

    public function disabled_notifications_app() {
        return $this->belongsToMany('App\EmailType', 'disable_app_notifications', 'user_id', 'app_type_id');
    }

    public function disabled_notifications_whatsapp() {
        return $this->belongsToMany('App\EmailType', 'disable_whatapp_notifications', 'user_id', 'whatsapp_type_id');
    }

    public function checknotification($name, $type = 'email') {
        //$result = $this->disabled_notifications()->join('email_types as et','et.id','=','email_type_id')->where('et.name',$name)->first();

        if ($this->is_active == 1 && $this->notifications == '1') {
            if ($type == 'email') {
                $result = $this->disabled_notifications_email()->where('act', $name)->first();
                if ($result) {
                    return false;
                } else {
                    return true;
                }
            } elseif ($type == 'app') {
                $result = $this->disabled_notifications_app()->where('act', $name)->first();
                if ($result) {
                    return false;
                } else {
                    return true;
                }
            } elseif ($type == 'whatsapp') {
                $result = $this->disabled_notifications_app()->where('act', $name)->first();
                if ($result) {
                    return false;
                } else {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public static function resolveId() {
        return \Auth::check() ? \Auth::user()->getAuthIdentifier() : null;
    }

    public function crmTasksAssigned() {
        return $this->belongsToMany(\App\Models\Crm\Tasks::class, 'crm_task_assigned', 'user_id', 'taskid');
    }

    public function crmTasksFollowed() {
        return $this->belongsToMany(\App\Models\Crm\Tasks::class, 'crm_task_followers', 'user_id', 'taskid');
    }

    public function crmTasksCreated() {
        return $this->hasMany(\App\Models\Crm\Tasks::class, 'addedfrom', 'id');
    }

    public function crmTasks() {
        return $this->crmTasksCreated
                        ->merge($this->crmTasksFollowed)
                        ->merge($this->crmTasksAssigned);
    }

    /*

      public function crmTasksAssigned() {
      return $this->belongsToMany('App\Models\Crm\Tasks', 'crm_task_assigned', 'user_id', 'taskid');
      }

      public function crmTasksFollowed() {
      return $this->belongsToMany('App\Models\Crm\Tasks', 'crm_task_followers', 'user_id', 'taskid');
      }

      public function crmTasksCreated() {
      return $this->hasMany('App\Models\Crm\Tasks', 'addedfrom', 'id');
      }

      public function crmTasks() {
      return $this->crmTasksCreated()->union($this->crmTasksFollowed()->selectRaw('crm_tasks.*')->toBase())->union($this->crmTasksAssigned()->selectRaw('crm_tasks.*')->toBase());
      } */

    public function crmTasksOnlyId() {
        $tasks1 = $this->crmTasksCreated()->select('crm_tasks.id');
        $tasks2 = $this->crmTasksFollowed()->select('crm_tasks.id');
        $tasks3 = $this->crmTasksAssigned()->select('crm_tasks.id');

        if (!($this->can('view_advertiser_tasks') || $this->can('superadmin'))) {
            $tasks1->where('crm_tasks.rel_type', '!=', 'advertiser');
            $tasks2->where('crm_tasks.rel_type', '!=', 'advertiser');
            $tasks3->where('crm_tasks.rel_type', '!=', 'advertiser');
        }
        if (!($this->can('view_publisher_tasks') || $this->can('superadmin'))) {
            $tasks1->where('crm_tasks.rel_type', '!=', 'publisher');
            $tasks2->where('crm_tasks.rel_type', '!=', 'publisher');
            $tasks3->where('crm_tasks.rel_type', '!=', 'publisher');
        }
        if (!($this->can('view_crm_projects') || $this->can('superadmin'))) {
            $tasks1->where('crm_tasks.rel_type', '!=', 'projects');
            $tasks2->where('crm_tasks.rel_type', '!=', 'projects');
            $tasks3->where('crm_tasks.rel_type', '!=', 'projects');
        }
        if (!($this->can('view_crm_leads') || $this->can('superadmin'))) {
            $tasks1->where('crm_tasks.rel_type', '!=', 'leads');
            $tasks2->where('crm_tasks.rel_type', '!=', 'leads');
            $tasks3->where('crm_tasks.rel_type', '!=', 'leads');
        }

        $tasks = collect()
                ->merge($tasks1->pluck('id'))
                ->merge($tasks2->pluck('id'))
                ->merge($tasks3->pluck('id'))
                ->unique() // Ensure no duplicates
                ->values()
                ->toArray();

        return $tasks;
    }

    /*
      public function crmTasksOnlyId() {
      $tasks1 = $this->crmTasksCreated()->selectRaw('crm_tasks.id');
      $tasks2 = $this->crmTasksFollowed()->selectRaw('crm_tasks.id');
      $tasks3 = $this->crmTasksAssigned()->selectRaw('crm_tasks.id');
      if (!($this->can('view_advertiser_tasks') || $this->can('superadmin'))) {
      $tasks1 = $tasks1->where('crm_tasks.rel_type', '!=', 'advertiser');
      $tasks2 = $tasks2->where('crm_tasks.rel_type', '!=', 'advertiser');
      $tasks3 = $tasks3->where('crm_tasks.rel_type', '!=', 'advertiser');
      }
      if (!($this->can('view_publisher_tasks') || $this->can('superadmin'))) {
      $tasks1 = $tasks1->where('crm_tasks.rel_type', '!=', 'publisher');
      $tasks2 = $tasks2->where('crm_tasks.rel_type', '!=', 'publisher');
      $tasks3 = $tasks3->where('crm_tasks.rel_type', '!=', 'publisher');
      }
      if (!($this->can('view_crm_projects') || $this->can('superadmin'))) {
      $tasks1 = $tasks1->where('crm_tasks.rel_type', '!=', 'projects');
      $tasks2 = $tasks2->where('crm_tasks.rel_type', '!=', 'projects');
      $tasks3 = $tasks3->where('crm_tasks.rel_type', '!=', 'projects');
      }
      if (!($this->can('view_crm_leads') || $this->can('superadmin'))) {
      $tasks1 = $tasks1->where('crm_tasks.rel_type', '!=', 'leads');
      $tasks2 = $tasks2->where('crm_tasks.rel_type', '!=', 'leads');
      $tasks3 = $tasks3->where('crm_tasks.rel_type', '!=', 'leads');
      }
      $tasks = $tasks1->union($tasks2->toBase())->union($tasks3->toBase())->pluck('id')->toArray();
      return $tasks;
      } */

    public function crmProjectsAssigned() {
        return $this->belongsToMany('App\Models\Crm\Projects', 'crm_project_members', 'user_id', 'project_id')->where('crm_project_members.type', 'member');
    }

    public function contentWriterProjectsAssigned() {
        return $this->belongsToMany('App\Models\Crm\Projects', 'crm_project_members', 'user_id', 'project_id')->where('crm_project_members.type', 'content_writer');
    }

    public function crmProjectsCreated() {
        return $this->hasMany('App\Models\Crm\Projects', 'addedfrom', 'id');
    }

    public function crmProjects() {
        return $this->crmProjectsCreated()->selectRaw('crm_projects.*')->union($this->crmProjectsAssigned()->selectRaw('crm_projects.*')->toBase());
    }

    public function crmLeadsAssigned() {
        return $this->hasMany('App\Models\Crm\Leads', 'assigned', 'id');
    }

    public function crmLeadsCreated() {
        return $this->hasMany('App\Models\Crm\Leads', 'addedfrom', 'id');
    }

    public function crmLeads() {
        return $this->crmLeadsAssigned()->selectRaw('crm_leads.*')->union($this->crmLeadsCreated()->selectRaw('crm_leads.*')->toBase());
    }

    public function chatsettings() {
        return $this->belongsTo('App\Models\Crm\ChatSettings', 'user_id', 'id');
    }

    public function chatssent() {
        $id = Auth::id();
        return $this->hasMany('App\Models\Crm\Chats', 'sender_id', 'id')->where('reciever_id', $id)->where('type', 'message');
    }

    public function chatsrecieved() {
        $id = Auth::id();
        return $this->hasMany('App\Models\Crm\Chats', 'reciever_id', 'id')->where('sender_id', $id)->where('type', 'message');
    }

    public function chats() {
        return $this->chatssent()->union($this->chatsrecieved()->toBase());
    }

    public function lastchat() {
        return $this->chats()->orderBy('time_sent', 'desc')->first();
    }

    /* public function lastchat2(){
      //return $this->chats()->orderBy('time_sent','desc')->first();
      //return $this->chatssent()->union($this->chatsrecieved()->toBase())->orderBy('time_sent','desc')->first();
      $id = Auth::id();
      $user_id = "users.id";
      $relation = $this->hasOne('App\Models\Crm\Chats','sender_id');
      $relation->getQuery()
      ->join('users', function ($join) use ($id) {
      $join->on('users.id', '=', 'crm_chat_messages.sender_id')
      ->orOn('users.id', '=', 'crm_chat_messages.reciever_id')
      ->whereRaw("(crm_chat_messages.sender_id = ".$id." OR crm_chat_messages.reciever_id = ".$id.") AND crm_chat_messages.type = 'messages'");
      })->selectRaw('crm_chat_messages.*');
      $relation->setQuery(
      $this->chats()->orderBy('time_sent','desc')->getQuery()
      );
      return $relation;
      } */

    public function getChatstatusAttribute() {
        return $this->chatsettings->status;
    }

    public function calling_products($level = []) {
        $query = $this->belongsToMany('App\Models\Crm\Products', 'user_products', 'user_id', 'product_id')->where('user_products.type', 'calling')->withPivot(['level']);
        if (!empty($level)) {
            $query->whereIn('user_products.level', $level);
        }
        return $query;
    }

    public function callingProductsWithoutPivot() {
        return $this->hasMany('\App\UserProducts', 'user_id', 'id')->where('user_products.type', 'calling');
    }

    public function chat_products($level = []) {
        $query = $this->belongsToMany('App\Models\Crm\Products', 'user_products', 'user_id', 'product_id')->where('user_products.type', 'chat')->withPivot(['level']);
        if (!empty($level)) {
            $query->whereIn('user_products.level', $level);
        }
        return $query;
    }

    public function chatProductsWithoutPivot() {
        return $this->hasMany('\App\UserProducts', 'user_id', 'id')->where('user_products.type', 'chat');
    }

    public function call_auditing_products() {
        return $this->belongsToMany('App\Models\Crm\Products', 'user_products', 'user_id', 'product_id')->where('user_products.type', 'call_auditing');
    }

    public function callAuditingProductsWithoutPivot() {
        return $this->hasMany('\App\UserProducts', 'user_id', 'id')->where('user_products.type', 'call_auditing');
    }

    public function languages() {
        return $this->belongsToMany('App\Languages', 'user_languages', 'user_id', 'language_id');
    }

    public function languagesWithoutPivot() {
        return $this->hasMany('\App\UserLanguages', 'user_id', 'id');
    }

    public function leadCalls() {
        return $this->hasMany('App\LeadCalls', 'from_id', 'id');
    }

    public function department() {
        return $this->belongsTo('App\Models\Hrm\Departments', 'department_id');
    }

    public function designation() {
        return $this->belongsTo('App\Models\Hrm\Designations', 'designation_id');
    }

    public function attendance() {
        return $this->hasMany('App\Models\Hrm\Attendance', 'user_id');
    }

    public function assignedDepartments() {
        return $this->belongsToMany('App\Models\Hrm\Departments', 'hrm_department_managers', 'user_id', 'department_id');
    }

    public function assignedDesignations() {
        return $this->belongsToMany('App\Models\Hrm\Designations', 'hrm_designation_managers', 'user_id', 'designation_id');
    }

    public function scoring() {
        return $this->hasMany('\App\UserScoring', 'user_id');
    }

    public function social_profiles() {
        return $this->hasOne('\App\UserSocialProfiles', 'user_id');
    }

    public function course_enrolled() {
        return $this->hasMany('\App\Models\Training\CourseEnrolled', 'user_id')->where('user_type','=','user');
    }

    public function calling_categories() {
        return $this->belongsToMany('App\Models\Crm\ProductCategories', 'user_product_linked_categories', 'user_id', 'category_id');
    }
}
