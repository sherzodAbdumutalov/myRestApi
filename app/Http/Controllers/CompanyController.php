<?php

namespace App\Http\Controllers;

use App\Company;
use Illuminate\Http\Request;
use JWTAuth;
use Validator;

class CompanyController extends Controller
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
            return Company::all();
        }else{
            return $this->user->company();
        }
    }

    public function show($id)
    {
        if ($this->isAdmin($this->user->roles)){
            $company = Company::find($id);
        }else{
            $company = $this->user->company()->find($id);
        }

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, company with id ' . $id . ' cannot be found'
            ], 400);
        }

        return $company;
    }

    public function store(Request $request)
    {
        if (!$this->isAdmin($this->user->roles)){
            return response()->json([
                'success' => false,
                'message' => 'Sorry, you cannot add'
            ], 400);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'owner_firstname' => 'required',
            'owner_lastname' => 'required',
            'owner_middlename' => 'required',
            'address' => 'required',
            'website' => 'required',
            'email' => 'required',
            'phone_number' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>array($validator->errors()->all())], 500);
        }

        try{
            Company::create($this->convertToArray($request));
            return response()->json([
                'success' => true,
                'company' => $request->all()
            ]);
        }catch (\Exception $exception){
            return response()->json([
                'success' => false,
                'message' => 'Sorry, company could not be added',
                'error' => $exception
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try{
            if ($this->isAdmin($this->user->roles)){
                $company = Company::find($id);
            }else{
                $company = $this->user->company()->find($id);
            }
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, company with id ' . $id . ' cannot be found'
                ], 400);
            }

            $updated = $company->fill($request->all())->save();

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'updated!'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Sorry, company could not be updated'
                ], 500);
            }
        }catch (\Exception $exception){
            dd($exception);
            return response()->json([
                'success' => false,
                'message' => 'Sorry, company could not be updated'
            ], 500);
        }
    }

    public function destroy($id)
    {
        if ($this->isAdmin($this->user->roles)){
            $company = Company::find($id);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Sorry, you cannot delete'
            ], 400);
        }

        if ($company->delete()) {
            return response()->json([
                'success' => true,
                'message' => 'company was deleted!'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'company could not be deleted'
            ], 500);
        }
    }

    public function convertToArray($request)
    {
        return [
            'name' => $request->name,
            'owner_firstname' => $request->owner_firstname,
            'owner_lastname' => $request->owner_lastname,
            'owner_middlename' => $request->owner_middlename,
            'address' => $request->address,
            'website' => $request->website,
            'phone_number' => $request->phone_number,
            'email' => $request->email
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
