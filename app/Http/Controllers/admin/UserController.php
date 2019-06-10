<?php

namespace App\Http\Controllers\admin;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repository\Helper\Response;
use App\Repository\Services\UserService;
use App\User;
use Illuminate\Http\Request;


class UserController extends Controller
{
    protected $userService;
    protected $fileService;

    /**
     * UserController constructor.
     * @param userService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function list(Request $request)
    {
        $users = User::skip(intval($request->get('offset')))
            //->take(intval($request->get('limit')) ?: 10)
            ->with('role')
            ->get()->makeVisible(['_id', 'active', 'created_at']);
        foreach ($users as $user) {
            $user['project_num'] = $user->projects()->count();
            $user['node_num'] = $user->things()->count();
        }
        return Response::body(compact('users'));
    }

    public function excel(Request $request)
    {
        $users = User::skip(intval($request->get('offset')))
            //->take(intval($request->get('limit')) ?: 10)
            ->with('role')
            ->get()->makeVisible(['_id', 'active', 'created_at']);
        foreach ($users as $user) {
            $user['project_num'] = $user->projects()->count();
            $user['node_num'] = $user->things()->count();
        }
        return $this->userService->toExcel($users);
    }

    public function ban(User $user, Request $request)
    {
        $active = $request->get('active') ? true : false;
        $user->active = $active;
        $user->save();
        $user->makeVisible(['_id', 'active']);
        return Response::body(compact('user'));
    }

    public function get(User $user)
    {
        $user->makeVisible(['_id', 'active', 'is_admin']);
        $overview = [
            'projects' => $user->projects()->count(),
            'things' => $user->things()->count(),
            'thing_profiles' => $user->thingProfiles()->count(),
            'gateways' => $user->gateways()->count(),
            'success_payment' => $user->invoices()->where('status', true)->count(),
            'failed_payment' => $user->invoices()->where('status', false)->count(),
        ];
        return Response::body(compact('overview', 'user'));
    }

    public function transactions(User $user)
    {
        $invoices = $user->invoices()->get();
        return Response::body(compact('invoices'));
    }

    public function setPassword(User $user, Request $request)
    {
        $password = $request->get('password');
        if (!$password || strlen($password) < 6)
            throw  new GeneralException('لطفا رمز عبور را درست وارد کنید(حداقل ۶ کاراکتر)', GeneralException::VALIDATION_ERROR);
        $user->password = bcrypt($password);
        $user->save();
        $user->makeVisible(['_id', 'active']);
        return Response::body(compact('user'));
    }


    /**
     * Impersonate for given user in context of logged in user
     * @param User $user
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function impersonate(User $user, Request $request)
    {
        $user->impersonated = true;
        $tokens = $this->userService->activateImpersonate($user->_id);
        return Response::body([
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }

    /**
     * find out the main user based on impersonated token
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function deimpersonate(Request $request)
    {
        return Response::body($this->userService->deactivateImpersonate());
    }


}
