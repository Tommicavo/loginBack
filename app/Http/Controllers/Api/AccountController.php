<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make(
            $data,
            [
                'name' => 'required | max:64 | unique:accounts',
                'email' => 'required | email | unique:accounts',
                'password' => 'required | confirmed | min:8 | regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*\W).+$/',
                'password_confirmation' => 'required'
            ],
            [
                'password.regex' => 'The password field must contain at least one uppercase letter, one lowercase letter, one number, and one special character'
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();
            $response = ['errors' => [], 'status' => 'validationError'];

            foreach ($errors->messages() as $field => $fieldErrors) {
                $response['errors'][$field] = $fieldErrors;
            }
            return response()->json($response);
        }

        $data['password'] = Hash::make($data['password']);

        $account = new Account();
        $account->fill($data);
        $account->slug = Str::slug($account->name, '-');
        $account->save();

        $response = ['slug' => $account->slug, 'status' => 'signinOk'];
        return response()->json($response);
    }

    public function login(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make(
            $data,
            [
                'email' => 'required',
                'password' => 'required',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();
            $response = ['errors' => [], 'status' => 'validationError'];

            foreach ($errors->messages() as $field => $fieldErrors) {
                $response['errors'][$field] = $fieldErrors;
            }
            return response()->json($response);
        }


        $account = Account::where('email', $data['email'])->first();
        if (!$account) {
            $response = ['message' => 'There are no signed accounts with this email', 'status' => 'deniedEmail'];
            return response()->json($response);
        }

        if (!Hash::check($data['password'], $account->password)) {
            $response = ['message' => 'Entered Password is wrong, try again', 'status' => 'deniedPassword'];
            return response()->json($response);
        }

        $response = ['slug' => $account->slug, 'status' => 'loginOk'];
        return response()->json($response);
    }

    public function getAccount(string $slug)
    {
        $account = Account::where('slug', $slug)->first();
        if (!$account) return response(null, 404);
        return response()->json($account);
    }
}
