
<!-- common source file -->
@extends('front.layout.app')

<!-- common content file -->
@section('content')

<!-- for popup messages -->
@extends('front.includes.flashMsg')

	<!--Breadcrumb Area Start-->
    <div class="breadcrumb-area pb-80">
		    <div class="container">
		        <div class="row">
		            <div class="col-12">
                        <div class="breadcrumb-bg" style="background-image:url({{ asset('/') }}themes/front/img/bg/breadcrumb.jpg)">
                        <ul class="breadcrumb-menu">
						<li><a href="{{ url('/') }}">Home</a></li>
                                <li>Login Register</li>
                            </ul>
                            <h2>Login Register</h2>
		                </div>
		            </div>
		        </div>
		    </div>
		</div>
		<!--Breadcrumb Area End-->
		<!--Login Register Area Strat-->
		<div class="login-register-area pb-50">
		    <div class="container">
		        <div class="row">
                    <!--Login Form Start-->
		            <div class="col-md-6 col-12">
		                <div class="customer-login-register">
		                    <div class="form-login-title">
		                        <h2>Login</h2>
		                    </div>
		                    <div class="login-form">
		                        <form id="loginform" method="POST" action="{{ route('process-login') }}">
								{{ csrf_field() }}
		                            <div class="form-fild">
		                                <p><label>Email address <span class="required">*</span></label></p>
		                                <input name="email" value="" type="text">
		                            </div>
		                            <div class="form-fild">
		                                <p><label>Password <span class="required">*</span></label></p>
		                                <input name="password" value="" type="password">
		                            </div>
		                            <div class="login-submit">
		                                <button type="submit" class="form-button">Login</button>
		                                <label>
		                                    <input class="checkbox ch-style" name="rememberme" value="" type="checkbox">
		                                    <span>Remember me</span>
		                                </label>
		                            </div>
		                            <div class="lost-password">
		                                <a href="#">Lost your password?</a>
		                            </div>
		                        </form>
		                    </div>
		                </div>
		            </div>
		            <!--Login Form End-->
		            <!--Register Form Start-->
		            <div class="col-md-6 col-12">
		                <div class="customer-login-register register-pt-0">
		                    <div class="form-register-title">
		                        <h2>Register</h2>
		                    </div>
		                    <div class="register-form config-2">
		                        <form id="rigisterform" method="POST" action="{{ route('process-register') }}">
								{{ csrf_field() }}
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="col-md-6">
													<div class="form-fild">
														<p><label>First name <span class="required">*</span></label></p>
														<input name="firstname" value="" type="text" data-maxlength="25" 
														data-required="true" data-minlength="2"class ="form-control"
														required />
													</div>
												</div>
												<div class="col-md-6">
													<div class="form-fild">
														<p><label>Last last<span class="required">*</span></label></p>
														<input name="lastname" value="" type="text" data-maxlength="25" 
														data-required="true" data-minlength="2" class="form-control"
														required />
													</div>
												</div>
											</div>
										</div>
										<div class="col-md-12">
											<div class="form-fild">
												<p><label>Email address <span class="required">*</span></label></p>
												<input name="email" value="" type="email" data-type="email" 
												data-required="true" data-minlength="2" class="form-control"
												required />
											</div>
										</div>
										<div class="col-md-12">
											<div class="form-fild">
												<p><label>Phone number <span class="required">*</span></label></p>
												<input name="phone" value="" type="text"
												data-required="true" data-minlength="2" class="form-control phone_number_mask"
												required />
											</div>
										</div>
										<div class="col-md-12">
											<div class="form-fild">
												<p><label>Password <span class="required">*</span></label></p>
												<input name="password" value="" type="password" id="password" class="form-control"
												data-required="true" 
												data-minlength="6" data-maxlength="16" 
												data-pattern="/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{8,12}$/g"
												required/>
											</div>
										</div>
										<div class="col-md-12">
											<div class="form-fild">
												<p><label>Confrim Password <span class="required">*</span></label></p>
												<input name="cpassword" value="" type="password"  class="form-control"
												data-required="true"  data-same="password"
												data-minlength="6" data-maxlength="16" 
												data-pattern="/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{8,12}$/g"
												required/>
											</div>
										</div>

										<div class="col-md-12">
											<div class="register-submit">
												<button type="submit" class="form-button">Register</button>
											</div>
										</div>
									</div>        
		                        </form>
		                    </div>
		                </div>
		            </div>
		            <!--Register Form End-->
		        </div> 
		    </div>
		</div>
<!--Login Register Area End-->

<script  type="text/javascript">
$(document).ready(function () {

	//set library for javascript validations
	jQuery('#rigisterform').parsley();
	jQuery('#loginform').parsley();

	// user register process
	jQuery('#rigisterform').submit(function(){
		if ($(this).parsley('isValid')) {
			var action_url = jQuery(this).attr('action');				
			jQuery('#ajax-loader-box').show();
			var formData = jQuery( this ).serialize();
			
			jQuery.ajax({
				type: "POST",
				url: action_url,
				data: formData,
				cache: false,
				success: function(data){
					data = JSON.parse(data);
					var $msg = data.msg;
					var $status = data.status;
					if($status == 1){
						$.bootstrapGrowl($msg, {type: 'success'});
						setTimeout(function(){
							window.location.href = "{{url('home')}}";
						}, 1500);						
					}else if($status == 0){							
						jQuery('#frm-user-login').trigger('reset');
						$.bootstrapGrowl($msg, {type: 'danger'});
					}
					jQuery('#ajax-loader-box').hide();
				}			
			});	
		}
		return false;
	});

	// user login process 
	jQuery('#loginform').submit(function(){
		if ($(this).parsley('isValid')) {
			var action_url = jQuery(this).attr('action');				
			jQuery('#ajax-loader-box').show();
			var formData = jQuery( this ).serialize();
			
			jQuery.ajax({
				type: "POST",
				url: action_url,
				data: formData,
				cache: false,
				success: function(data){
					data = JSON.parse(data);
					var $msg = data.msg;
					var $status = data.status;
					if($status == 1){
						$.bootstrapGrowl($msg, {type: 'success'});
						setTimeout(function(){
							window.location.href = "{{url('home')}}";
						}, 1500);						
					}else if($status == 0){							
						jQuery('#frm-user-login').trigger('reset');
						$.bootstrapGrowl($msg, {type: 'danger'});
					}
					jQuery('#ajax-loader-box').hide();
				}			
			});	
		}
		return false;
	});
});  
</script>
@endsection