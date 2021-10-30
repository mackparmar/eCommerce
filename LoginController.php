<?php
namespace App\Http\Controllers\front;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Validator;

/* Define Function */ 
use App\CommonTrait;


/* Define Models */ 
use App\Models\Users;
use App\Models\Countries;

class LoginController extends Controller
{    
    

    public function __construct() 
    {
        
        $this->view_base = 'front.';
        $this->badRequestMsg = 'Bad request!';
		$this->responseData = array();
		$this->responseData['status'] = 0;
		$this->responseData['msg'] = $this->badRequestMsg;
        $this->resetPasswordUrl = url('reset-password');
        $this->successForgotPasswordMsg = "Password reset link successfully sent to your email.";
        $this->successResetPasssowrdMsg = "Password has been successfully reseted.";
    }

    /**
     * Login Page Function
     * @param  void
     * @return $data
    */
    public function Login(Request $request){
        $data = array(); 
        $data['pageTitle'] = 'Log in';
        return view($this->view_base.'login',$data);
    }

    /**
     * Register Page Function
     * @param  void
     * @return $data
    */
    public function Register(Request $request){

        $data = array(); 
        $data['pageTitle'] = 'Log ind';
        return view($this->view_base.'register',$data);
    }

    /**
     * process to Users Register
     * @param  void
     * @return $data
    */
    public function UsersRegister(Request $request){
        $data = array();
		$this->responseData['status'] = 1;
		$this->responseData['msg'] = "List available.";

        $requestArr = $request->all();
        $requestArr['country_id'] = (isset($requestArr['country_id']) && $requestArr['country_id'] !='') ?  $requestArr['country_id'] : DEFAULT_COUNTRY_ID;

        if(isset($requestArr['country_id']) && is_numeric($requestArr['country_id']) && isset($requestArr['phone']))
        {
            $countryObj = Countries::find($requestArr['country_id']);
            if(isset($countryObj->id) && $countryObj->id > 0)
            {
                    
                $requestArr['phone'] = CommonTrait::clean_input($requestArr['phone']);
                $phone = CommonTrait::remove_country_prefix($requestArr['phone'], $countryObj->phonecode);
                $requestArr['phone']  = $countryObj->phonecode . $phone;
                $requestArr['plain_phone']  = $phone;
            }
        }
          
		$validator = Validator::make($requestArr, [
            'email' => 'required|min:2|email|unique:' . TBL_USERS,
            'phone' => 'required|min:2|max:255|unique:' . TBL_USERS,
            'name' => 'required|min:2|max:255',
            'password' => 'required|min:8|max:16',
            'confirm-password' => 'required|same:password',

        ]);

		if ($validator->fails()) 
		{
			$this->responseData['status'] = 0;
            $this->responseData['msg'] = $validator->messages()->first();
            			
		}else
		{
            //check for other validation
            $checkPassword = \App\CommonTrait::checkPassword($requestArr['password']);
            if($checkPassword['status'] == 0 ){
                $this->responseData['status'] = 0;
                $this->responseData['msg'] = $checkPassword['msg'];
                
            }else{
                
                $name = isset($requestArr['name'])? ucfirst(strtolower($requestArr['name'])) : '';
                $email = $requestArr['email'];          
                $password = bcrypt($requestArr['password']);
                //$secret_key = \App\CommanFunctions::encrypt_decrypt("encrypt", $requestArr['password']);
                
                $country_id = $requestArr['country_id'];
                $state_id = isset($requestArr['state_id'])? $requestArr['state_id'] : 0;
                $city = isset($requestArr['city'])? $requestArr['city'] : '';
                $address = isset($requestArr['address'])? $requestArr['address'] : '';
                $ip_address = \App\CommonTrait::get_client_ip();

                $data_to_insert = array(
                    "name" => $name,                
                    "email" => $email,
                    "phone" => $phone,
                    "plain_phone" => $requestArr['plain_phone'],
                    "password" => $password,
                    "country_id" => $country_id,
                    "state_id" => $state_id,
                    "city" => $city,
                    "address" => $address,
                    "ip_address" => $ip_address
                );

                $obj = Users::create($data_to_insert);
                $this->responseData['status'] = 1;
                $this->sendVerifyEmail($obj->id);
                $this->responseData['msg'] = "Your account successfully created. Please check your email for verification link.";
            }
			
        }
        //return response()->json(['msg' => $msg,'status' => $status]); 
		echo json_encode($this->responseData); exit;     
    }


    public function sendVerifyEmail($id){
        
        $formObj = Users::find($id);
        
        // send otp and update details
        $email_token = md5($id);
        $to = $formObj->email;
        $email_verify_url = url("verify-account") . '/' . $email_token;
        
        // SEND EMAIL VERIFICATION LINK DETAILS EMAIL
        $emailData = array(
            'email_name' => 'VERIFY-ACCOUNT-EMAIL',
            'to_email' => $formObj->email,
            'name' => ucfirst($formObj->name),
            'VERIFICATIONLINK' => $email_verify_url,            
        );

        $is_sent_email = CommonTrait::sendBulkEmailUsingSendGridAPI($emailData);    
        
        $emailData = array(
            'email_name' => 'CELEBRITY-ACCOUNT-CREATED-EMAIL',
            'to_email' => "sarthak@pricus.com",
            'name' => $formObj->name,
            'loginemail' => $formObj->email,
            'password' => "*******",
        );

        $is_email_sent = CommonTrait::sendBulkEmailUsingSendGridAPI($emailData);
        
         // SEND LOGIN DETAILS EMAIL celebrity
        $emailData = array(
            'email_name' => 'CELEBRITY-ACCOUNT-CREATED-EMAIL',
            'to_email' => "philip@veng-it.dk",
            'name' => $formObj->name,
            'loginemail' => $formObj->email,
            'password' => "*******",
        );

        $is_email_sent = CommonTrait::sendBulkEmailUsingSendGridAPI($emailData);
    }

    public function verifyEmail($key) {
        
        $model = new Users;
        $formObj = $model->getUserByKey($key);
        $data = array();        
        if(isset($formObj->id) && $formObj->id > 0){
            $formObj = $model::find($formObj->id);
            
            $formObj->activated_by_user = '1';
            $formObj->status = '1';
            
            $formObj->save();               
            $this->responseData['status'] = 1;
            $this->responseData['msg'] = 'Your account successfully verified .';
        
        
        }else{
            $this->responseData['status'] = 0;
            $this->responseData['msg'] = 'Invalid Details!';
        }
        $data = $this->responseData;
        $data['pageTitle'] = 'Email Verification';  
        return view($this->view_base.'login',$data);
    }

    /**
     * Register Page Function
     * @param  void
     * @return $data
    */
    public function ForgotPassword(Request $request){
        $data = array(); 
        $data['pageTitle'] = 'Log ind';
        return view($this->view_base.'forgot-password',$data);
    }
    
    public function processLogin(Request $request){
        
        $data = array();
        $this->responseData['status'] = 0;
        $this->responseData['msg'] = $this->badRequestMsg;

        $validator = Validator::make($request->all(), [
            'email' => 'required|email', 
            'password' => 'required',            
        ]);        
        
        // check validations
        if ($validator->fails()) 
        {
            $this->responseData['status'] = 0;
            $this->responseData['msg'] = $validator->messages()->first();
            echo json_encode($this->responseData); exit;
        }         
        else
        {           

            if (Auth::guard('users')->check()){
                Auth::guard('users')->logout();
            }

            if (Auth::guard('users')->attempt(['email' => $request->get('email'), 'password' => $request->get('password')])) 
            {

               $user = Auth::guard('users')->user();                
                if($user->status != 1)
                {
                    Auth::guard('users')->logout();
                                         
                    $this->responseData['status'] = 0;
                    $this->responseData['msg'] = "user is not Actived";
                    //return response()->json($this->responseData);
                    echo json_encode($this->responseData); exit;
                }else{
                    
                    $this->responseData['status'] = 1;
                    $this->responseData['msg'] = "Logged in successfully.";
                    $user->last_login_at = \Carbon\Carbon::now();
                    $user->save();
                }
               // dd($user);
                echo json_encode($this->responseData); 

            }
            else
            {
                $this->responseData['status'] = 0;
                $this->responseData['msg'] = "The credential that you've entered doesn't match any account.";
                echo json_encode($this->responseData); 
            }
        }
        
        
    }
    
    public function processForgotPassword(Request  $request){

        $requestArr = $request->all();
        $validator = Validator::make($requestArr, [
            'email' => 'required|email',            
        ]);

        if ($validator->fails()) {
        
            $this->responseData['status'] = 0;
            $this->responseData['msg'] = $validator->messages()->first();
            echo json_encode($this->responseData); exit;
        } else {
            
            $email = isset($requestArr['email'])? $requestArr['email'] : '';            
            
            $model = new Users;
            $userObj = $model::where('email', '=', $email)->where('status', '=', '1')->select("*")->first();
            if(isset($userObj->id) && $userObj->id > 0){

                $reset_passsword_token = md5($userObj->id);
                $to = $userObj->email;
                $name = ucfirst($userObj->firstname) . ' ' . ucfirst($userObj->lastname);
                $this->resetPasswordUrl = $this->resetPasswordUrl . '/' . $reset_passsword_token;

                // SEND PASSWORD RESET LINK DETAILS EMAIL
                $emailData = array(
                    'email_name' => 'ACCOUNT-FORGOT-PASSWORD-EMAIL',
                    'to_email' => $userObj->email,
                    'name' => $userObj->name,
                    'PASSWORDRESETLINK' => $this->resetPasswordUrl,         
                );
                $is_sent_email = CommonTrait::sendBulkEmailUsingSendGridAPI($emailData);
                    
                if($is_sent_email){                 
                    $userObj->reset_passsword_token = $reset_passsword_token;
                    $userObj->save();
                    
                    $this->responseData['status'] = 1;
                    $this->responseData['msg'] = $this->successForgotPasswordMsg;
                }
            }else{
                $this->responseData['status'] = 0;
                $this->responseData['msg'] = "The credential that you've entered doesn't match any email.";
            }
            echo json_encode($this->responseData); exit;
            //return response()->json($this->responseData);
        }
    }

    public function resetPassword(){

        $data = array();
        $data['pageTitle'] = 'Reset Password';
        return view($this->view_base . 'resetPassword', $data);    
    }

    public function processResetPassword(Request $request) {
        
        $requestArr = $request->all();
        $validator = Validator::make($requestArr, [
            'password' => 'required|min:8|max:16',
            'confirm-password' => 'required|same:password',            
        ]);

        if ($validator->fails()) {
        
            $this->responseData['status'] = 0;
            $this->responseData['msg'] = $validator->messages()->first();
            echo json_encode($this->responseData); exit;
            
        } else {
            
            //check for valid password
            $checkPassword = CommonTrait::checkPassword($requestArr['password']);
            if($checkPassword['status'] == 0 ){
                $this->responseData['status'] = 0;
                $this->responseData['msg'] = $checkPassword['msg'];
                echo json_encode($this->responseData); exit;
            }
            
            $token = isset($requestArr['token'])? $requestArr['token'] : '0';
            $password = isset($requestArr['password'])? $requestArr['password'] : '0';
            
            $userModel = new Users;
            $userObj = $userModel->getUserByKey($token);

            if(isset($userObj->id) && $userObj->id > 0){
                
                $userObj = $userModel::find($userObj->id);
                $userObj->password = bcrypt($password);
                $userObj->save();
                
                $this->responseData['status'] = 1;
                $this->responseData['msg'] = $this->successResetPasssowrdMsg;
                echo json_encode($this->responseData); exit;
                
            }else{
                $this->responseData['status'] = 0;
                $this->responseData['msg'] = $this->badRequestMsg . ', try forgot password again.';
                echo json_encode($this->responseData); exit;
            }
                        
        }   
    }

    public function Logout()
    {
        Auth::guard('users')->logout();
        return redirect('/');
    } 
}
