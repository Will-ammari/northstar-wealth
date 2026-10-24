<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
        $user=User::where('email',$data['email'])->first();
        if (!$user || !Hash::check($data['password'],$user->password)) {
            throw ValidationException::withMessages(['email'=>'Invalid credentials.']);
        }
        // Short-lived server-side BFF credential; do not expose this token to browser JS.
        $token=$user->createToken('portfolio-bff',['portfolio:read','portfolio:write'],now()->addHours(8));
        return response()->json(['token'=>$token->plainTextToken,'user'=>['id'=>$user->id,'name'=>$user->name,'email'=>$user->email]]);
    }
    public function me(Request $request): JsonResponse
    {
        $user=$request->user();
        return response()->json(['id'=>$user->id,'name'=>$user->name,'email'=>$user->email]);
    }
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok'=>true]);
    }
}
