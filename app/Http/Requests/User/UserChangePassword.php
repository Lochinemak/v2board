<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserChangePassword extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'old_password' => 'required',
            'new_password' => 'required|min:8'
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 允许OAuth用户使用特殊标识来设置密码
            if ($this->input('old_password') === 'OAUTH_USER_FIRST_PASSWORD_SETUP') {
                // 这是OAuth用户首次设置密码的特殊情况，跳过旧密码验证
                return;
            }
        });
    }

    public function messages()
    {
        return [
            'old_password.required' => __('Old password cannot be empty'),
            'new_password.required' => __('New password cannot be empty'),
            'new_password.min' => __('Password must be greater than 8 digits')
        ];
    }
}
