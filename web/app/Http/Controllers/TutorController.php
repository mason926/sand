<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Firebase\Auth\Token\Exception\InvalidToken;

class TutorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(session()->has("role") == false) {
            return view('logout');
        }
        $role = session()->get("role");
        if(Roles::isAdmin($role)) {
            return view("tutor")->with("tutors", $this->getAll())
            ->with("role",$role);
        }
        $error = [
            "Access Denied",
            "You are not authorized to view this page"
        ];
        return view('error')->with("error",$error);
    }

    public function getAll() {
        $factory = (new Factory)->withServiceAccount(__DIR__ . '/myapp.json');
        $database = $factory->createDatabase();
        $uid = 0;
        $ref = $database->getReference("tutors");
        $keys = $ref->getChildKeys();

        $data = array();

        foreach($keys as $key) {
            $obj = $ref->getChild($key)->getValue();

            $tutor = new Tutor();
            $tutor->setUid($key);
            $tutor->setUserName($obj['userName']);
            $tutor->setFirstName($obj['firstName']);
            $tutor->setLastName($obj['lastName']);
            $tutor->setEmail($obj['email']);
            $tutor->setPassword($obj['password']);
            if($ref->getChild($key)->getSnapshot()->hasChild("rating")) {
                $tutor->setRating($obj['rating']);
            }
            if($ref->getChild($key)->getSnapshot()->hasChild("availableFrom")) {
                $tutor->setAvailableFrom($obj['availableFrom']);
            }
            if($ref->getChild($key)->getSnapshot()->hasChild("availableUpto")) {
                $tutor->setAvailableUpto($obj['availableUpto']);
            }

            $location = new Location();
            $loc = $obj['location'];
            $location->setStreetAddress($loc['streetAddress']);
            $location->setCity($loc['city']);
            $location->setState($loc['state']);
            $location->setZipCode($loc['zipCode']);
            $tutor->setLocation($location);


            if($ref->getChild($key)->getSnapshot()->hasChild("courses")) {
                $tutor->setCourses($obj['courses']);
            } else {
                $tutor->setCourses(array());
            }

            array_push($data, $tutor);
        }

        return $data;
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $factory = (new Factory)->withServiceAccount(__DIR__ . '/myapp.json');
        $database = $factory->createDatabase();
        $ref = $database->getReference("tutors");

        $key = $ref->push()->getKey();

        $ref->getChild($key)->set([
            "userName" => request('userName'),
            "firstName" => request('firstName'),
            "lastName" => request('lastName'),
            "password" => request('password'),
            "email" => request('email'),
            "availableFrom" => request('availableFrom'),
            "availableUpto" => request('availableUpto'),
             "location" => [
                "streetAddress" => request('streetAddress'),
                "city" => request('city'),
                'state' => request('state'),
                'zipCode' => request('zipCode')
            ],
        ]);

        return $this->index();
    }

    public function create(Request $request)
    {
        $factory = (new Factory)->withServiceAccount(__DIR__ . '/myapp.json');
        $database = $factory->createDatabase();
        $auth = $factory->createAuth();
        $idTokenString = request('idToken');
        try {
            $verifiedIdToken = $auth->verifyIdToken($idTokenString);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                "status" => 400,
                "message" => $e->getMessage()
            ], 400);
            // echo 'The token could not be parsed: '.$e->getMessage();
        } catch (InvalidToken $e) {
            return response()->json([
                "status" => 403,
                "message" => $e->getMessage()
            ], 403);
            // echo 'The token is invalid: '.$e->getMessage();
        }
        $uid = $verifiedIdToken->getClaim('sub');
        $user = $auth->getUser($uid);
        if($user == null) {
            echo 'Invalid User';
        }
        //Create student record
        $ref = $database->getReference("tutors");
        $ref->getChild($uid)->set([
            "userName" => request('username'),
            "firstName" => request('firstName'),
            "lastName" => request('lastName'),
            "password" => request('password'),
            "email" => request('email'),
            "availableFrom" => request('availableFrom'),
            "availableUpto" => request('availableUpto'),
            "location" => request('location'),
            "courses" => request('courses')
        ]);

        //Create role
        $ref = $database->getReference("roles");
        $ref->getChild($uid)->set([
            "role" => Roles::getTutorRole()
        ]);

        $data = [
            "status"=> "success"
        ];
        return response()->json($data, 200);
    }
}
