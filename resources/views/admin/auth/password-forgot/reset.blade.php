@extends('admin.auth.layouts.auth-master')

@section('section')
    <div class="account-wrapper">
        <div class="account-header">
            <div class="site-logo">
                 <img src="{{ get_logo($basic_settings) }}"  data-white_img="{{ get_logo($basic_settings,'white') }}"
                    data-dark_img="{{ get_logo($basic_settings,'dark') }}"
                        alt="site-logo">
            </div>
            <h6 class="sub-title mt-0">{{ __("Reset ") }} <span>{{ __("Password") }}</span></h6>
        </div>
        <form class="account-form" action="{{ setRoute('admin.password.update') }}" method="POST">
            @csrf
            @include('admin.components.form.hidden-input',[
                'name'      => 'token',
                'value'     => $token ?? "",
            ])
            @include('admin.components.form.hidden-input',[
                'name'      => 'email',
                'value'     => $email ?? "",
            ])
            <div class="form-group">
                <div class="form-group show_hide_password" >
                    <input type="password" title="Enter new password" required name="password">
                    <button class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                    <label>{{ __("New Password") }}</label>
                </div>
            </div>
            <div class="form-group show_hide_password">
                <input type="password" title="Enter Confirm password" required name="password_confirmation">
                <button class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                <label>{{ __("Confirm Password") }}</label>
            </div>
            <div class="form-group">
                <div class="forgot-item">
                    <p><a href="{{ setRoute('admin.login') }}" class="text--base">{{ __("Login") }}</a></p>
                </div>
            </div>
            <button type="submit" class="btn--base w-100 btn-loading">{{ __("Reset Password") }}</button>
        </form>
    </div>
@endsection
