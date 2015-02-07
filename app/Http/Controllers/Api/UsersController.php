<?php namespace Forret\Http\Controllers\Api;

use Cartalyst\Sentry\Users\UserExistsException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Appit\Interfaces\UserInterface;
use Input;
use Redirect;
use View;
use Mail;
use Sentry;

class UsersController extends BaseController
{

    /** @var \Appit\Repositories\UserRepository  */
    protected $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
        $this->beforeFilter('hasGroup:Admins', ['only' => ['destroy', 'search']]);
    }

    public function index()
    {
        return $this->user->recent25();
    }

    public function store()
    {
        try {
            return $this->user->createNew(Input::all());
        }
        catch(UserExistsException $e) {
            throw new StoreResourceFailedException('user already exists');
        }
    }
    public function show($id)
    {
        return $this->user->findById($id);
    }

    public function destroy($id)
    {
        return $this->user->deleteById($id);
    }

    public function undestroy($id)
    {
        return $this->user->undestroy($id);
    }

    public function update($id)
    {
        $this->user->privatePage($id);
        return $this->user->updateExisting($id,Input::all());
    }

    public function search()
    {

        return $this->user->search(Input::all());
    }

    public function getActivate()
    {
        $user = Sentry::findUserByCredentials(['email' => $_REQUEST['useremail']]);
        if($user->activation_code == $_REQUEST['activationcode']) {
            $user->activated = 1;
            $user->activated_at = new DateTime;
            $user->save();
            return Redirect::to('/')->with('message', 'Activation successful. You may login now.');
        }
    }

    public function getLogout()
    {
        Sentry::logout();
        return Redirect::to('/')->with('message', 'Logout successful.');
    }

    public function postForgotPassword()
    {
        try {
            // Find the user using the user email address
            $user = Sentry::findUserByLogin(Input::get('email'));

            // Get the password reset code
            $resetCode = $user->getResetPasswordCode();
        } catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
            echo 'User was not found.';
        }

        $data = array(
            'detail' =>'Account activation mail',
            'name' => $user['first_name'] .' '. $user['last_name'],
            'reset_code' => $resetCode,
            'email' => $user['email'],
        );

        // use Mail::send function to send email passing the data and using the $user variable in the closure
        Mail::send('passwordReset', $data, function ($message) use ($user, $data)
        {
            $message->from('admin@org.com', 'Organisation');
            $message->to($data['email'], $data['name'])->subject('Password Reset');
        });
        return Redirect::to('/')->with('message','Reset password code has been sent to your inbox!');
    }

    public function getResetPassword()
    {
        // var_dump("expression");die;
        $user = Sentry::findUserByLogin(Input::get('useremail'));
        if($user->reset_password_code == Input::get('reset_code')) {
            return View::make('resetPassword', ['email' => Input::get('useremail')]);
        }
    }

    public function postResetPassword()
    {
        // var_dump("expression");die;
        try {
            $user = Sentry::findUserByLogin(Input::get('email'));
        } catch (Cartalyst\Sentry\Users\UserNotFoundException $e) {
            echo 'User was not found.';
        }

        $user->password = Input::get('password');
        $user->save();
        return Redirect::to('/')->with('message', 'Password succesfully changed.');
    }
}
