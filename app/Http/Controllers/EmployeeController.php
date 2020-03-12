<?php

namespace App\Http\Controllers;

use App\Company;
use App\Employee;
use Illuminate\Http\Request;
use JWTAuth;
use Validator;

class EmployeeController extends Controller
{
    protected $user;
    protected const ADMIN = 1;

    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
    }

    public function index()
    {
        if ($this->isAdmin($this->user->roles)){
            return Employee::all();
        }else{
            return $this->user->company->first()->employees;
        }
    }

    public function show($id)
    {
        if ($this->isAdmin($this->user->roles)){
            $employee = Employee::find($id);
        }else{
            $employee = $this->user->company->first()->employees()->find($id);
        }


        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, employee with id ' . $id . ' cannot be found'
            ], 400);
        }

        return $employee;
    }

    public function store(Request $request)
    {
        if ($this->isAdmin($this->user->roles)){
            return response()->json([
                'success' => false,
                'message' => 'Sorry, you cannot add employee'
            ], 500);
        }

        $validator = Validator::make($request->all(), [
            'passport_serial_number' => 'required|unique:employees',
            'firstname' => 'required',
            'lastname' => 'required',
            'middlename' => 'required',
            'address' => 'required',
            'position' => 'required',
            'phone_number' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>array($validator->errors()->all())], 500);
        }

        try{
            if (is_null($this->user->company->first()->id)){
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, you cannot add',
                ], 500);
            }
            Employee::create($this->convertToArray($request, $this->user->company->first()->id));
            return response()->json([
                'success' => true,
                'employee' => $request->all()
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'success' => false,
                'message' => 'Sorry, employee could not be added',
                'error' => $exception
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $employee = $this->user->company->first()->employees()->find($id);

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, employee with id ' . $id . ' cannot be found'
                ], 400);
            }

            $updated = $employee->fill($request->all())->save();

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'updated!'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, employee could not be updated'
                ], 500);
            }
        }catch (\Exception $exception){
            dd($exception);
            return response()->json([
                'success' => false,
                'message' => 'Sorry, employee could not be updated'
            ], 500);
        }
    }

    public function destroy($id)
    {
        $employee = $this->user->company->first()->employees()->find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, employee with id ' . $id . ' cannot be found'
            ], 400);
        }

        if ($employee->delete()) {
            return response()->json([
                'success' => true,
                'message' => 'employee was deleted!'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'employee could not be deleted'
            ], 500);
        }
    }

    public function convertToArray($request, $company_id)
    {
        return [
            'passport_serial_number' => $request->passport_serial_number,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'middlename' => $request->middlename,
            'address' => $request->address,
            'position' => $request->position,
            'phone_number' => $request->phone_number,
            'company_id' => $company_id
        ];
    }

    public function isAdmin($roles){
        foreach ($roles as $role){
            if ($role->id == self::ADMIN){
                return true;
            }
        }
        return false;
    }
}
