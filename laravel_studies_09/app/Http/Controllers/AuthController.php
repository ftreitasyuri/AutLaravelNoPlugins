<?php

namespace App\Http\Controllers;

use App\Mail\NewUserConfirmation;
use App\Mail\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as NotificationsResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


use Illuminate\Support\Str;
use LDAP\Result;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        // Validação do formulário
        $credentials = $request->validate(
            [
                'username' => 'required|min:3|max:30',
                'password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',

            ],
            [
                'username.required' => 'O usuário é obrigatório',
                'username.min' => 'O usuário deve ter no mínimo :min caracteres',
                'username.max' => 'O usuário deve ter no máximo :max caracteres',

                'password.required' => 'A senha é obrigatória',
                'password.min' => 'A senha deve conter no mínimo :min caracteres',
                'password.max' => 'A senha deve conter no máximo :max caracteres',
                'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número'

            ]
        );
        // Verificar se o user existe

        $user = User::where('username', $credentials['username'])->where('active', true)->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '<=', Carbon::now());
            })->whereNotNull('email_verified_at')->whereNull('deleted_at')->first();


        // Verifica se o user existe
        if (!$user) {
            return back()->withInput()->with([
                'invalid_login' => 'Login Inválido'
            ]);
        }

        // Verificar se a senha é valida

        if (!password_verify($credentials['password'], $user->password)) {
            return back()->withInput()->with([
                'invalid_login' => 'Login Inválido'
            ]);
        };


        // Atualizar o last_login

        $user->last_login_at = Carbon::now();
        $user->blocked_until = null;
        $user->save();

        // Logar
        $request->session()->regenerate();
        Auth::login($user);

        // Redirecionar
        return redirect()->intended(route('home'));
    }


    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function  store_user(Request $request)
    {
        // dd($request);

        // Form Validation
        $request->validate(
            [
                'username' => 'required|min:4|max:30|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'password_confirmation' => 'required|same:password'

            ],
            [

                'username.required' => 'O usuário é obrigatório',
                'username.min' => 'O usuário deve ter no mínimo :min caracteres',
                'username.max' => 'O usuário deve ter no máximo :max caracteres',

                'email.required' => 'O email é obrigatório',
                'email.email' => 'O email não é válido',

                'password.required' => 'A senha é obrigatória',
                'password.min' => 'A senha deve conter no mínimo :min caracteres',
                'password.max' => 'A senha deve conter no máximo :max caracteres',
                'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',

                'password_confirmation.required' => 'A confirmação da senha é obrigatória',
                'password_confirmation.same' => 'A confirmação da senha não é igual à senha',

            ]
        );

        // Criando um novo usuário definindo um token de verificação de email
        $user = new User();

        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);

        // Criando token
        $user->token = Str::random(64);

        // Verificando dados do user
        // dd($user);

        // Gerar link
        $link_confirm = route('new_user_confirmation', ['token' => $user->token]);

        // Enviar Email
        $result = Mail::to($user->email)->send(new NewUserConfirmation($user->username, $link_confirm));

        // Verificar se o envio foi com sucesso
        if (!$result) {
            return back()->withInput()->with(
                [
                    'server_error' => 'Ocorreu um erro ao enviar o email de confirmação!'

                ]
            );
        }

        // Salvar User
        $user->save();

        // Apresentar view de sucesso

        return view('auth.email_sent', ['email' => $user->email]);
    }

    public function home()
    {
        return view('home');
    }


    public function new_user_confirmation($token)
    {

        // Verificar se o token é válido
        $user = User::where('token', $token)->first();
        if (!$user) {
            return redirect()->route('login');
        }

        // Confirmar o registro do usuário
        $user->email_verified_at = Carbon::now();
        $user->token = null;
        $user->active = true;
        $user->last_login_at = Carbon::now();
        $user->save();

        // Autenticação automática do usuário confirmado
        Auth::login($user);



        // Apresenta uma mensagem de sucesso

        return view('auth.new_user_confirmation');
    }

    public function profile()
    {
        return view('auth.profile');
    }

    public function change_password(Request $request)
    {

        $request->validate(
            [
                // 'username' => 'required|min:4|max:30|unique:users,username',
                // 'email' => 'required|email|unique:users,email',
                'current_password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',

                'new_password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|different:current_password',

                'new_password_confirmation' => 'required|same:new_password'
                // 'new_password_confirmation'=> 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',



            ],
            [

                'current_password.required' => 'A senha atual é obrigatória',
                'current_password.required' => 'A senha atual é obrigatória',
                'current_password.required' => 'A senha atual é obrigatória',
                'current_password.required' => 'A senha atual é obrigatória',

                'new_password.required' => 'A senha nova é obrigatória',
                'new_password.different' => 'A senha nova deve ser diferente da atual',
                'new_password.min' => 'A senha deve conter no mínimo :min caracteres',
                'new_password.max' => 'A senha deve conter no máximo :max caracteres',
                'new_password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',

                'new_password_confirmation.required' => 'A confirmação da senha é obrigatória',
                'new_password_confirmation.same' => 'A confirmação da senha não é igual à senha',

            ]
        );

        // dd($request);

        // Validar a senha atual
        if (!password_verify($request->current_password, Auth::user()->password)) {
            return back()->with([
                'server_error' => 'A senha atual não está correta'
            ]);
        }

        // dd('Senha OK');


        // Atualizando a senha

        $user = Auth::user();

        $user->password = bcrypt($request->new_password);

        $user->save();

        // Atualizar a senha da sessão
        Auth::user()->password = $request->new_password;

        return redirect()->route('profile')->with([
            'success' => 'A senha foi alterada com sucesso!'
        ]);
    }

    public function forgot_password()
    {
        return view('auth.forgot_password');
    }

    public function send_reset_password_link(Request $request)
    {
        // Form validation
        $request->validate(
            [
                'email' => 'required|email'
            ],
            [
                'email.required' => 'O e-mail é obrigatório',
                'email.email' => 'O e-mail deve ser um endereço válido'
            ]
        );

        // dd($request);

        $generic_message = 'Verifique a sua caixa de correio para prosseguir com a recuperação da senha';
        // Verificar se o email existe

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->with([
                'server_message' => $generic_message
            ]);
        }

        // dd('Aqui');


        // Gerando token
        $user->token = Str::random(64);
        $token_link = route('reset_password', ['token' => $user->token]);

        // Envio do email com o link para recuperar
        $result = Mail::to($user->email)->send(new ResetPassword($user->username, $token_link));

        

        // Verifica se o email foi envia
        if (!$result) {
            return back()->with([
                'server_message' => $generic_message
            ]);
        }

        // Guardar o token na base
        $user->save();

        return back()->with([
            'server_message' => $generic_message
        ]);
        // etc...
    }

    public function reset_password($token)
    {
        // Verificar se o token é válido
        $user = User::where('token', $token)->first();
        if (!$user) {
            return redirect('login');
        }

        return view('auth.reset_password', ['token' => $token]);
    }

    public function reset_password_update(Request $request)
    {
        // Validando senha
        $request->validate(
            [
                'new_password' => 'required|min:6|max:32|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'new_password_confirmation' => 'required|same:new_password'
            ],
            [
                'new_password.required' => 'A senha é obrigatória',
                'new_password.min' => 'A senha deve conter no mínimo :min caracteres',
                'new_password.max' => 'A senha deve conter no máximo :max caracteres',
                'new_password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número',

                'new_password_confirmation.required' => 'A confirmação da senha é obrigatória',
                'new_password_confirmation.same' => 'A confirmação da senha não é igual à senha',
            ]
        );


        // dd('Senhas OK');

        // Verifica se o token é válido
        $user = User::where('token', $request->token)->first();

        if (!$user) {
            return redirect()->route('login');
        }

        // Atualizando a senha na base de dados
        $user->password = bcrypt($request->new_password);
        $user->token = null;
        $user->save();



        // Retornando para a view login
        return redirect('login')->with([
            'success' => true
        ]);
    }


    // Deletando conta
    public function delete_account(Request $request)
    {
        $request->validate(
            [
                'delete_confirmation' => 'required|in:ELIMINAR'
            ],
            [
                'delete_confirmation.required' => 'A confirmação é obrigatória',
                'delete_confirmation.in' => 'É obrigatório escrever a palavra ELIMINAR'
            ]
        );


        // logout e removendo a conta
        // SOFT DELETE
        $user = Auth::user();
        $user->delete();

        // HARD DELETE
        // $user = Auth::user();
        // $user->forcDelete();

        // Logout
        Auth::logout();

        // Redirecionando para login
        return redirect()->route('login')->with([
            'success_delete' => 'Conta removida com sucesso!'
        ]);
    }
}
