<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Customer;
use App\Models\Lessor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Helpers\JwtAuth;
use App\Models\UserRole;
use App\Utils\Data;
use App\Utils\JsonResponses;

class UserController
{
    public function indexAdministrator(){
        $data = Administrator::all();
        return JsonResponses::ok(
            "Todos los registros de los arrendadores",
            $data,
        );
    }

    public function index()
    {
        return JsonResponses::ok(
            'Todos los registros de usuarios',
            User::with('userRole')->get()
        );
    }

    public function indexUserRole()
    {
        return JsonResponses::ok(
            'Todos los registros de roles de usuario',
            UserRole::all()
        );
    }

    public function store(Request $request)
    {
        $data_input = $request->input('data', null);

        if ($data_input) {
            $data = json_decode($data_input, true);
            $data = array_map('trim', $data);
            $rules = [
                'name' => 'required|unique:user|max:50',
                'password' => 'required|string|max:64',
                'role_id' => 'numeric|exists:user_role',
                'email_address' => 'required|email|unique:user|max:150'
            ];
            $isValid = validator($data, $rules);

            if (!$isValid->fails()) {
                $user = new User();
                $user->name = $data['name'];
                $user->password = Data::hash($data['password']);
                $user->role_id = $data['role_id'];
                $user->email_address = $data['email_address'];
                $user->save();

                if ($data['role_id'] == 1) {
                    $administrator = new Administrator();
                    $administrator->user_name = $data['name'];
                    $administrator->first_name = $data['first_name'];
                    $administrator->last_name = $data['last_name'];
                    $administrator->phone_number = $data['phone_number'];
                    $administrator->save();
                } elseif ($data['role_id'] == 2) {
                    $customer = new Customer();
                    $customer->user_name = $data['name'];
                    $customer->first_name = $data['first_name'];
                    $customer->last_name = $data['last_name'];
                    $customer->phone_number = $data['phone_number'];
                    $customer->save();
                } elseif ($data['role_id'] == 3) {
                    $lessor = new Lessor();
                    $lessor->user_name = $data['name'];
                    $lessor->first_name = $data['first_name'];
                    $lessor->last_name = $data['last_name'];
                    $lessor->phone_number = $data['phone_number'];
                    $lessor->save();
                }

                $response = [
                    'message' => 'El usuario se ha agregado correctamente.',
                    'status' => 200
                ];
            } else {
                $response = [
                    'message' => 'Error al ingresar los datos.',
                    'errors' => $isValid->errors(),
                    'status' => 400
                ];
            }
        } else {
            $response = [
                'message' => 'Error al ingresar los datos.',
                'status' => 400
            ];
        }

        return response()->json($response, $response['status']);
    }

    public function storePassword(Request $request)
    {
        $userName = $request->route('name');
        $dataInput = $request->input('data', null);

        if ($dataInput) {
            $user = User::find($userName);

            if ($user) {
                $data = json_decode($dataInput, true);
                $data = array_map('trim', $data);

                $rules = [
                    'current_password' => 'required|alpha_num|max:50',
                    'new_password' => 'required|alpha_num|max:50',
                    'new_password_confirmation' => 'required|alpha_num|max:50',
                ];
                $validation = validator($data, $rules);
                if ($validation->fails()) {
                    $response = JsonResponses::notAcceptable(
                        'Error al ingresar los datos',
                        'errors',
                        $validation->errors()
                    );
                } else if (strcasecmp($user->password, Data::hash($data['current_password'])) != 0) {
                    $response = JsonResponses::notAcceptable(
                        'Contraseña incorrecta'
                    );
                } else if (strcmp($data['new_password'], $data['new_password_confirmation']) != 0) {
                    $response = JsonResponses::notAcceptable(
                        'Las contraseñas no coinciden'
                    );
                } else if (strcasecmp($user->password, ($newPassword = Data::hash($data['new_password']))) == 0) {
                    $response = JsonResponses::notAcceptable(
                        'La nueva contraseña no puede ser igual a la anterior'
                    );
                } else {
                    $user->password = $newPassword;
                    $user->save();

                    $response = JsonResponses::ok('La contraseña ha sido cambiada con éxito');
                }
            } else {
                $response = JsonResponses::notFound('No existe un usuario con el nombre especificado');
            }
        } else {
            $response = JsonResponses::badRequest('No se especificó el objeto "data" en la solicitud');
        }

        return $response;
    }

    public function destroy($userName)
    {
        $user = User::find($userName);

        if (!$user) {
            $response = [
                'message' => 'El usuario no existe.',
                'status' => 404
            ];
        } else {
            switch ($user->role_id) {
                case 1:
                    $model = Administrator::where('user_name', $user->name)->first();
                    break;
                case 2:
                    $model = Customer::where('user_name', $user->name)->first();
                    break;
                case 3:
                    $model = Lessor::where('user_name', $user->name)->first();
                    break;
                default:
                    $model = null;
                    break;
            }
            if (!$model) {
                $response = [
                    'message' => 'No se pudo encontrar el modelo asociado al usuario.',
                    'status' => 404
                ];
            } else {
                $model->delete();
                $user->delete();

                $response = [
                    'message' => 'Usuario y modelo asociado eliminados exitosamente.',
                    'status' => 200
                ];
            }
        }

        return response()->json($response, $response['status']);
    }
    public function updatePartial(Request $request, $name)
    {
        $user = User::where('name', $name)->first();

        if (!$user) {
            $response = [
                'message' => 'El usuario no existe.',
                'status' => 404
            ];
        } else {
            switch ($user->role_id) {
                case 1:
                    $model = Administrator::where('user_name', $user->name)->first();
                    break;
                case 2:
                    $model = Customer::where('user_name', $user->name)->first();
                    break;
                case 3:
                    $model = Lessor::where('user_name', $user->name)->first();
                    break;
                default:
                    $model = null;
                    break;
            }

            if (!$model) {
                $response = [
                    'message' => 'No se pudo encontrar el modelo asociado al usuario.',
                    'status' => 404
                ];
            } else {
                $data = $request->only(['user_name', 'first_name', 'last_name', 'phone_number', 'email_address']);

                if (empty($data)) {
                    $response = [
                        'message' => 'No se proporcionaron datos para actualizar.',
                        'status' => 400
                    ];
                } else {
                    $model->fill($data);
                    $model->save();

                    $response = [
                        'message' => 'Usuario y modelo asociado actualizados correctamente.',
                        'status' => 200
                    ];
                }
            }
        }
        return response()->json($response, $response['status']);
    }

    public function show($name)
    {
        $data = User::find($name);
        if (is_object($data)) {
            if ($data->role_id == UserRole::ADMINISTRATOR) {
                $person = Administrator::firstWhere('user_name', $data->name);
                $person->id = $person->administrator_id;
            }
            else if ($data->role_id == UserRole::LESSOR) {
                $person = Lessor::firstWhere('user_name', $data->name);
                $person->id = $person->lessor_id;
            }
            else {
                $person = Customer::firstWhere('user_name', $data->name);
                $person->id = $person->customer_id;
            }

            $response = JsonResponses::ok(
                'Datos del usuario',
                [
                    'name' => $data->name,
                    'email_address' => $data->email_address,
                    'role_id' => $data->role_id,
                    'image' => $data->image,
                    'person_id' => $person->id,
                    'first_name' => $person->first_name,
                    'last_name' => $person->last_name,
                    'phone_number' => $person->phone_number,
                ],
            );
        } else {
            $response = JsonResponses::notFound('Recurso no encontrado');
        }
        return $response;
    }

    public function login(Request $request)
    {
        $data_input = $request->input('data', null);
        $data = json_decode($data_input, true);
        $data = array_map('trim', $data);
        $rules = ['name' => 'required|exists:user', 'password' => 'required'];
        $validation = validator($data, $rules);

        if (!$validation->fails()) {
            $jwtAuth = new JwtAuth();
            $token = $jwtAuth->getToken($data['name'], $data['password']);
            if ($token) {
                $response = response()->json($token);
            } else {
                $response = JsonResponses::unauthorized('Datos de autenticacion incorrectos');
            }
        } else {
            $response = JsonResponses::notAcceptable(
                'Error en la validación de los datos',
                'errors',
                $validation->errors()
            );
        }

        return $response;
    }

    public function logOut($userName)
    {
        if (($user = User::find($userName))) {
            $user->last_logout = date('Y-m-d H:i:s', time()); // yyyy-mm-dd hh:mm:ss with 24 hour format
            $user->save();
        
            $response = JsonResponses::ok('La sesión ha sido finalizada con éxito.');
        } else {
            $response = JsonResponses::notFound('No existe un usuario con el nombre especificado.');
        }

        return $response;
    }

    public function getIdentity(Request $request)
    {
        $jwtAuth = new JwtAuth();
        $token = $request->bearerToken();

        if (isset($token)) {
            $response = response()->json($jwtAuth->checkToken($token, true));
        } else {
            $response = JsonResponses::notFound('Token (BearerToken) no encontrado');
        }

        return $response;
    }

    public function deleteImage($userName)
    {
        $user = User::find($userName);

        if ($user->image) {
            $fileName = $user->image;
            Storage::disk('users')->delete($fileName);
            $user->image = null;
            $user->save();

            $response = JsonResponses::ok(
                'Imagen eliminada con éxito.',
            );
        } else {
            $response = JsonResponses::notFound('El usuario no tiene una imagen.');
        }
        return $response;
    }

    public function uploadImage(Request $request)
    {
        $userName = $request->route('name');
        $user = User::find($userName);

        $validation = validator($request->all(), ['file' => 'required|image|mimes:jpg,png,jpeg,svg']);
        if (!$validation->fails()) {
            $image = $request->file('file');
            $fileName = Str::uuid() . "." . $image->getClientOriginalExtension();
            
            Storage::disk('users')->put($fileName, File::get($image));
            $user->image = $fileName;
            $user->save();

            $response = JsonResponses::created(
                'Imagen guardada.',
                $fileName
            );
        } else {
            $response = JsonResponses::notAcceptable(
                'No se encontró el archivo en la solicitud.',
                'errors',
                $validation->errors()
            );
        }
        return $response;
    }

    public function getImage($userName)
    {
        if (!($user = User::find($userName))) {
            return JsonResponses::notFound('No existe un usuario con el nombre especificado.');
        }

        if (!$user->image) {
            return JsonResponses::notFound('El usuario no tiene una imagen.');
        } 

        $fileName = $user->image;
        $exist = Storage::disk('users')->exists($fileName);
        if ($exist) {
            $filePath = Storage::disk('users')->path($fileName);
            $response = response()->file($filePath);
        } else {
            $response = JsonResponses::notFound(
                'La imagen no existe.'
            );
        }
        return $response;
    }
}
