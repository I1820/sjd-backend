<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 12/15/17
 * Time: 12:28 PM
 */

namespace App\Repository\Traits;

use App\Exceptions\UserException;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

trait UpdateUser
{

    /**
     * @param Request $request
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function updateUser(Request $request)
    {
        $user = Auth::user();
        if ($user['legal']) {
            return $this->updateLegalUser($request);
        } else {
            return $this->updateRealUser($request);
        }
    }

    /**
     * @param Request $request
     * @return void
     * @throws UserException
     */
    public function validateUpdateUser(Request $request)
    {
        if ($request->has('legal') && $request->get('legal') == 1) {
            $this->validateUpdateLegal($request);
        } else {
            $this->validateUpdateReal($request);
        }

    }

    private function validateUpdateReal(Request $request)
    {
        $messages = [
            'name.filled' => 'لطفا نام را وارد کنید',
            'password.filled' => 'لطفا رمزعبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل باید ۶ کارکتر باشد',
            'other_info.filled' => 'لطفا سایر اطلاعات را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'filled|string|max:255',
            'password' => 'filled|string|min:6',
            'other_info' => 'json',
        ], $messages);

        if ($validator->fails())
            throw new UserException($validator->errors()->first(), UserException::C_UE);
    }

    private function validateUpdateLegal(Request $request)
    {
        $messages = [
            'org_interface_name.filled' => 'لطفا نام رابط سازمان را وارد کنید',
            'org_interface_last_name.filled' => 'لطفا نام خانوادگی رابط سازمان را وارد کنید',
            'org_interface_phone.filled' => 'لطفا شماره تلفن ثابت رابط سازمان را وارد کنید',
            'org_interface_mobile.filled' => 'لطفا شماره موبایل شخصی را وارد کنید',
            'type.filled' => 'لطفا نوع حقوقی را وارد کنید',
            'org_name.filled' => 'لطفا نام سازمان را وارد کنید',
            'reg_number.filled' => 'لطفا شماره ثبت را وارد کنید',
            'ec_code.filled' => 'لطفا کد اقتصادی را وارد کنید',

            'password.filled' => 'لطفا رمزعبور را وارد کنید',
            'password.min' => 'رمز عبور حداقل باید ۶ کارکتر باشد',
        ];

        $validator = Validator::make($request->all(), [
            'org_interface_name' => 'filled',
            'org_interface_last_name' => 'filled',
            'org_interface_phone' => 'filled',
            'org_interface_mobile' => 'filled',
            'type' => 'filled',
            'org_name' => 'filled',
            'reg_number' => 'filled',
            'ec_code' => 'filled',
            'password' => 'filled|string|min:6',
        ], $messages);

        if ($validator->fails())
            throw new UserException($validator->errors()->first(), UserException::C_UE);
    }


    private function updateRealUser(Request $request)
    {
        $user = Auth::user();
        if ($request->get('password')) {
            $user->password = bcrypt($request->get('password'));
        }

        if ($request->get('name')) {
            $user->name = $request->get('name');
        }

        $updated_other_info = json_decode($request->get('other_info'), true) ?: [];

        $other_info = $user['other_info'];
        foreach ($updated_other_info as $key => $value) {
            $other_info[$key] = $value;
        }
        $user->other_info = $other_info;

        $user->save();

        return $user;
    }

    private function updateLegalUser(Request $request)
    {
        $user = Auth::user();
        if ($request->get('password')) {
            $user->password = bcrypt($request->get('password'));
        }

        $updated_other_info = $request->only([
            'org_interface_name',
            'org_interface_last_name',
            'org_interface_phone',
            'org_interface_mobile',
            'type',
            'org_name',
            'reg_number',
            'ec_code'
        ]);

        $other_info = $user['other_info'];
        foreach ($updated_other_info as $key => $value) {
            $other_info[$key] = $value;
        }
        $user->other_info = $other_info;

        $user->save();

        return $user;

        return User::create([
            'legal' => true,
            'active' => false,
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'other_info' => $request->only([
                'org_interface_name',
                'org_interface_last_name',
                'org_interface_phone',
                'org_interface_mobile',
                'type',
                'org_name',
                'reg_number',
                'ec_code'
            ])
        ]);
    }
}