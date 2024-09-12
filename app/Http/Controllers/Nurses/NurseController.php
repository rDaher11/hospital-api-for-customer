<?php

namespace App\Http\Controllers\Nurses;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DepartementController;
use App\Http\Requests\NurseForm;
use App\Http\Requests\UserForm;
use App\Models\Nurse;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class NurseController extends Controller
{

    public const ADMIN_INDEX_RESPONSE_FORMAT = [
        "user" => [
            "id" => "id",
            "first_name" => "first_name",
            "last_name" => "last_name",
            "email" => "email",
            "phone_number" => "phone_number",
            "gender" => "gender",
            "address" => "address",
            "birth_date" => "birth_date",
            "profile_picture_path" => "profile_picture_path",
        ],
        "departement_id" => "departement_id",
        "specialization" => "specialization",
        "short_description" => "short_description",
        "assigned_at" => "assigned_at",
        "rate" => "rate",
        "structured" => true
    ];

    public const ADMIN_READ_RESPONSE_FORMAT = [
        "user" => [
            "id" => "id",
            "first_name" => "first_name",
            "last_name" => "last_name",
            "email" => "email",
            "phone_number" => "phone_number",
            "gender" => "gender",
            "address" => "address",
            "birth_date" => "birth_date",
            "profile_picture_path" => "profile_picture_path"
        ],
        "departement" => "departement",
        "specialization" => "specialization",
        "short_description" => "short_description",
        "rate" => "rate",
        "assigned_at" => "assigned_at",
        "structured" => true
    ];


    public const NURSE_READ_RESPONSE_FORMAT = [
        "user" => [
            "id" => "id",
            "first_name" => "first_name",
            "last_name" => "last_name",
            "email" => "email",
            "phone_number" => "phone_number",
            "gender" => "gender",
            "address" => "address",
            "birth_date" => "birth_date",
            "profile_picture_path" => "profile_picture_path"
        ],
        "departement" => DepartementController::ALL_DEPARTEMENT_RESPONSE_FORMAT,
        "specialization" => "specialization",
        "short_description" => "short_description",
        "rate" => "rate",
        "assigned_at" => "assigned_at",
        "structured" => true
    ];

    public const PATIENT_READ_RESPONSE_FORMAT = [
        "user" => [
            "first_name" => "first_name",
            "last_name" => "last_name",
            "email" => "email",
            "phone_number" => "phone_number",
            "gender" => "gender",
            "profile_picture_path" => "profile_picture_path",
        ],
        "specialization" => "specialization",
        "rate" => "rate",
        "structured" => true
    ];

    public static function getNurseOr404($nurseId) {
        $nurse = Nurse::where('user_id' , $nurseId)->first();
        if ( $nurse == null ) {
            abort(404 , "nurse does not exist");       
        }
        return $nurse;
    } 

    
    /**
     *  @OA\Get(
     *       path="/api/nurses/{id}",
     *       tags={"Admin"},
     *       operationId = "readNurse",
     *       summary = "read specific nurse info",
     *       description= "Read Nurse Endpoint.",
     *       @OA\Parameter(name="id", description="nurse's id" , in="path", required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )),
     *       @OA\Response(response="200", description="OK"),
     *       @OA\Response(response="403", description="Forbidden"),
     *       @OA\Response(response="404", description="Object Not Found"),
     *  )
     */
    protected function read($id) {
        $nurse = NurseController::getNurseOr404($id);
        $this->authorize('view' , $nurse);
        return response()->json(
            Controller::formatData(
                $nurse , 
                NurseController::ADMIN_READ_RESPONSE_FORMAT
            ), 200
        );
    }

   /**
     *  @OA\Post(
     *     path="/api/nurses/",
     *     tags={"Admin"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              type="object",
     *              required={
     *                  "first_name",
     *                  "last_name",
     *                  "email",
     *                  "password",
     *                  "phone_number",
     *                  "address",
     *                  "gender",
     *                  "birth_date",
     *                  "specialization",
     *                  "short_description",
     *                  "rate",
     *                  "assinged_at",
     *                  "departement_id"
     *              },
     *              @OA\Property(property="first_name",type="string"),
     *              @OA\Property(property="last_name",type="string"),
     *              @OA\Property(property="email",type="string"),
     *              @OA\Property(property="password",type="string"),
     *              @OA\Property(property="phone_number",type="string"),
     *              @OA\Property(property="address",type="string"),
     *              @OA\Property(property="gender",type="integer"),
     *              @OA\Property(property="birth_date",type="date"),
     *              @OA\Property(property="specialization",type="integer"),
     *              @OA\Property(property="short_description",type="string"),
     *              @OA\Property(property="assigned_at",type="date"),
     *              @OA\Property(property="rate",type="integer"),
     *              @OA\Property(property="departement_id",type="integer"),
     *          ),
     *     ),
     *     @OA\Response(response="200", description="OK"),
     *     @OA\Response(response="403", description="Forbidden"),
     *     @OA\Response(response="404", description="Object Not Found")
     *  )
     */
    protected function create(NurseForm $request) {
        $this->authorize("create" , Nurse::class);
        $validated = $request->validated();
        if ( key_exists("departement_id" , $validated) == null ) {
            throw new HttpResponseException(response()->json([
                "errors" => [
                    "departement_id" => ["should be supported for this end-point"]
                ]] , 422));
        }

        $status_code = 0;
        $response_data = [];

        DB::beginTransaction();
        try {
            $user = User::create(array_merge($validated , ["role_id" => Role::NURSE]));
            Nurse::create(
                array_merge($validated, [
                "user_id" => $user->id
            ]));
            $user->markEmailAsVerified();
            
            DB::commit();

            $status_code = 200;
            $response_data = ["status" => "created" , "data" => $validated];

        } catch ( Exception $expception) {
            DB::rollBack();
            $status_code = 500;
            $response_data = ["status" => "uncreated"];
        }
        return response()->json($response_data , $status_code);
    }


    /**
     *  @OA\Put(
     *      path="/api/nurses/{id}",
     *      tags={"Admin"},
     *      operationId = "updateNurse",
     *      summary = "update a nurse",
     *      description= "Update Nurse Endpoint.",
     *      @OA\Parameter(name="id", description="nurse's id" , in="path" , required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )),
     *      @OA\Response(response="200", description="OK"),
     *      @OA\Response(response="403", description="Forbidden"),
     *      @OA\Response(response="404", description="Object Not Found"),     
     *      @OA\Response(response="422", description="Unprocessable Content"),
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="first_name",type="string"),
     *              @OA\Property(property="last_name",type="string"),
     *              @OA\Property(property="email",type="string"),
     *              @OA\Property(property="password",type="string"),
     *              @OA\Property(property="phone_number",type="string"),
     *              @OA\Property(property="address",type="string"),
     *              @OA\Property(property="gender",type="integer" , ref="#/components/schemas/Gender"),
     *              @OA\Property(property="birth_date",type="date"),
     *              @OA\Property(property="departement_id",type="integer"),
     *              @OA\Property(property="specialization",type="integer" , ref="#/components/schemas/MedicalSpecialization"),
     *              @OA\Property(property="short_description",type="string"),
     *              @OA\Property(property="rate",type="integer", ref="#/components/schemas/Rate"),
     *              @OA\Property(property="doctor_id",type="integer"),
     *              @OA\Property(property="assigned_at",type="date"),
     *          ),
     *      ),
     *  )
     */
    protected function update(NurseForm $request , $id) {
        $nurse = NurseController::getNurseOr404($id);
        $this->authorize('update' , $nurse);

        $validated = $request->validated();
        $response_data = [];
        $status_code = 0;

        DB::beginTransaction();
        try {
            $nurse->user->update($validated);
            $nurse->update($validated);
            DB::commit();

            $response_data = Controller::formatData(
                $nurse , 
                NurseController::ADMIN_READ_RESPONSE_FORMAT
            );
            $status_code = 200;

        } catch (Exception $exp) {
            DB::rollBack();
            $status_code = 500;
        }

        return response()->json($response_data , $status_code);
    }

    /**
     *  @OA\Delete(
     *      path="/api/nurses/{id}",
     *      tags={"Admin"},
     *      operationId = "deleteNurse",
     *      summary = "delete a nurse",
     *      description= "Delete Nurse Endpoint.",
     *      @OA\Parameter(name="id", description="nurse's id" , in="path" , required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )),
     *      @OA\Response(response="204", description="No Content"),
     *      @OA\Response(response="403", description="Forbidden"),
     *      @OA\Response(response="404", description="Object Not Found"), 
     *  )
     */
    protected function delete($id) {
        $nurse = NurseController::getNurseOr404($id);
        $this->authorize('delete' , $nurse);

        $user = $nurse->user;

        $status_code = 0;
        $response_data = [];
        
        DB::beginTransaction();
        try {
            $nurse->delete();
            $user->delete();
            DB::commit();

            $status_code = 204;
        } catch (Exception $exp) {
            DB::rollBack();

            $status_code = 500;
        }
        return response()->json($response_data, $status_code);
    }


    /**
     *  @OA\Get(
     *      path="/api/nurses/",
     *      tags={"Admin"},
     *      operationId = "listNurses",
     *      summary = "list all nurses",
     *      description= "List Nurses Endpoint.",
     *      @OA\Response(response="200", description="OK"),
     *      @OA\Response(response="403", description="Forbidden"),
     *  )
     */
    protected function index(){
        $this->authorize("viewAny" , Nurse::class);
        $nurses = Nurse::all(); 
        return response()->json(
            $this->paginate(
                Controller::formatCollection(
                    $nurses,
                    NurseController::ADMIN_INDEX_RESPONSE_FORMAT
                )
            )
        );
    }



    /**
     *  @OA\Get(
     *      path="/api/nurses/me/",
     *      tags={"Nurse"},
     *      operationId = "currentNurses",
     *      summary = "read the current nurse",
     *      description= "Current Nurse Endpoint.",
     *      @OA\Response(response="200", description="OK"),
     *      @OA\Response(response="403", description="Forbidden"),
     *      @OA\Response(response="401", description="Unauthorized")
     *  )
     */
    protected function me(Request $request) {
        $current_user = $request->user();
        if ( $current_user == null ) {
            return response()->json([
                "details" => "current user is undefined"
            ],401);
        }
        $current_nurse = 
            Nurse::where(
                "user_id",
                $current_user->id
            )->first();
        if ( $current_nurse == null ) {
            return response()->json([
                "details" => "current user is not a nurse"
            ],403);
        }

        return response()->json(
            Controller::formatData(
                $current_nurse,
                NurseController::NURSE_READ_RESPONSE_FORMAT
            )
        );
    }

    /**
     *  @OA\Put(
     *       path="/api/nurses/me",
     *       tags={"Nurse"},
     *       operationId = "updateCurrentNurse",
     *       summary = "update personal info for current nurse",
     *          @OA\RequestBody(
     *              @OA\JsonContent(
     *                  type="object",
     *                  @OA\Property(property="first_name",type="string"),
     *                  @OA\Property(property="last_name",type="string"),
     *                  @OA\Property(property="email",type="string"),
     *                  @OA\Property(property="password",type="string"),
     *                  @OA\Property(property="phone_number",type="string"),
     *                  @OA\Property(property="address",type="string"),
     *                  @OA\Property(property="gender",type="integer", ref="#/components/schemas/Gender"),
     *                  @OA\Property(property="birth_date",type="date"),
     *              ),
     *          ),
     *       description= "Update Nurse's Personal Info Endpoint.",
     *       @OA\Response(response="200", description="OK"),
     *       @OA\Response(response="403", description="Forbidden"),
     *       @OA\Response(response="422", description="Unprocessable Content"),
     *       @OA\Response(response="401", description="Unauthorized")
     *  )
     */
    public function updateMe(UserForm $request) {
        $current_user = $request->user();
        if ( $current_user == null ) {
            return response()->json([
                "details" => "current user is undefined"
            ],401);
        }

        $current_nurse = Nurse::where(
            "user_id" , $current_user->id
        )->first();
        if ( $current_nurse == null ) {
            return response()->json([
                "details" => "the current user is not a nurse"
            ] , 403);
        }
        
        $validated = $request->validated();
        $current_user->update($validated);
        return response()->json(
            ["status" => "updated" , "data" => $validated]
        );
    }


    
    public function paginate($items, $perPage = 5, $page = null, $options = [])
    {

        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);

    }

}

