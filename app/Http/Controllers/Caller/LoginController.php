<?php

namespace App\Http\Controllers\Caller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Socialite;
use DB;
use Auth;
use Hash;
use Session;
use App\Models\Callers;
use App\User;
use App\Http\Requests;
use \Illuminate\Support\Facades\Redis;

class LoginController extends Controller
{
    public function __construct() {
        $this->agency_id = config('app.current_agency');
        $this->view_folder = "/" . config('app.view_folder');
        $this->agencies_auto_approve_user = config('app.agencies_auto_approve_user');
        $this->agency_view_folder = config("app.agencies_template.$this->agency_id");
    }
	
    public function index(Request $request){
		if (Auth::check()) {
			return redirect("calling");
		}
		return View('caller.auth.login');
	}
	
	public function validateUser(Request $request) {
        if ($request->email_address) {
            $validator = Validator::make(
                            array(
                                'email' => $request->email_address,
                                'password' => $request->password,
                            ), array(
                        'email' => 'required|email',
                        'password' => 'required',
                            )
            );
        }
		
        if (Auth::guard('web')->attempt(['email' => $request->email_address, 'password' => $request->password, 'caller_active' => 1, 'dsp' => 'hunt'])) {
			$user = Auth::user();
            return redirect("calling");
        } else {
            return redirect(config('app.url') . 'login')->with('error_login', 'Invalid credentials')->withInput();
        }
    }
	
	public function Logout() {
        if (Session::get('loggedin_as')) {
            Session::forget('loggedin_as');
            Session::forget('pub_current_domain');
            Session::forget('agency_current_adv');
            return redirect('/');
        }
        $user = Auth::User();
        Auth::logout();
        Session::flush();
        return redirect('/');
    }
	
	public function redirectToProvider(Request $request) {
        $driver = $request->driver;
        \Config::set('services.' . $driver . '.redirect', config('app.url') . 'login/' . $driver.'/callback');
        return Socialite::driver($driver)->redirect();
    }

    public function handleProviderCallback(Request $request) {

        $driver = $request->driver;
        \Config::set('services.' . $driver . '.redirect', config('app.url') . 'login/' . $driver.'/callback');
        try {
            $user = Socialite::driver($driver)->user();
        } catch (\Exception $e) {
            return redirect(config('app.url') . 'login')->withErrors(['exists' => 'Some error occured']);
        }

        //$existingUser = User::where('email', $user->getEmail())->where('login_method', $driver)->where('social_login_id', $user->getId())->first();
        $existingUser = User::where('email', $user->getEmail())->where('login_with', $driver)->where('social_auth_id', $user->getId())->where('dsp','=','hunt')->where('caller_active','=',1)->first();
        //if (Auth::attempt(['email' => $user->getEmail(), 'login_with' => $driver, 'social_auth_id', 'is_active' => 1, 'agency_id' => $agency_id])) {
        if ($existingUser) {
            Auth::guard('web')->login($existingUser);
            $existingUser = Auth::user();
            $existingUser->last_login = date('Y-m-d H:i:s');
            $existingUser->save();
            return redirect("calling");
        } else {
            return redirect(config('app.url') . 'login')->with('error_login', 'Invalid credentials')->withInput();
        }
    }
}
