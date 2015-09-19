<?php
 
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require_once '../include/TimeHandler.php';
require '../libs/Slim/Slim.php';

 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim(array(
    'debug' => true
));
 
// User id from db - Global Variable
$user_id = NULL;
 


// Function for basic field validation (present and neither empty nor only white space
function IsNullOrEmptyString($question){
    return (!isset($question) || trim($question)==='');
}

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
//    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
//    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
//        $app = \Slim\Slim::getInstance();
//        parse_str($app->request()->getBody(), $request_params);
//    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}
 
/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoResponse(400, $response);
        $app->stop();
    }
}
 
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param array $response Json response
 */
function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}
 
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'password', 'gender'));
            $response = array();
 
            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
            $gender = $app->request->post('gender');
            // validating email address
            validateEmail($email);
 
            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password, $gender);
 
            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
                echoResponse(201, $response);
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
                echoResponse(200, $response);
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
                echoResponse(200, $response);
            }
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));
 
            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();
 
            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);
 
                if ($user != NULL) {
                    $response["error"] = false;
                    $response['id'] = $user['id'];
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['gender'] = $user['gender'];
                    $response['key'] = $user['key'];
                 
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }
 
            echoResponse(200, $response);
        });
        
/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has a valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $header = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
 
    // Verifying Authorization Header
    if (isset($header['Authorization'])) {
        $db = new DbHandler();
 
        // get the api key
        $api_key = $header['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoResponse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);

        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoResponse(400, $response);
        $app->stop();
    }
}

        
        
/**
 * Creating new event in db
 * method POST
 * params - title, start_time, duration, location, details, [members]
 * url - /events/
 */
$app->post('/events', 'authenticate', function() use($app) {
    verifyRequiredParams(array('title', 'start_time',
        'duration', 'location', 'details'));
    
    global $user_id;
    $db = new DbHandler();
    
    $response = array();
    $title = $app->request->post('title');
    $start_time = $app->request->post('start_time');
    $location = $app->request->post('location');
    $duration = $app->request->post('duration');
    $details = $app->request->post('details');
    $members = array();
    
    // optional members param
    if (!IsNullOrEmptyString($app->request->post('members'))) {
       $members = explode(',', $app->request->post('members')); 
    }
    
    $response_code = 201;
    
    // if there are members and user doesn't have required privileges
    if (!empty($members) && !$db->hasMultiUserPriv($user_id, $members)) {
        $response["error"] = true;
        $response["message"] = "Failed to create event. Insufficient Privileges";
        $response_code = 403;
   
    }
    else {
        $event_id = $db->createEvent($user_id, $title, $details,
        $location, $start_time, $duration);
        if (!empty($members)) {
            $db->addMultiUserToEvent($event_id, $members);
        }
            

        if ($event_id != NULL) {
            $response["error"] = false;
            $response["message"] = "Event created successfully";
            $response["event"] = $db->getEventById($event_id);

        } else {
            $response["error"] = true;
            $response["message"] = "Failed to create event. Please try again.";
            $response_code = 500;
        }
    }
    
    echoResponse($response_code, $response);
    });
        
        
/**
 * Listing all events of particual user
 * method GET
 * url /events         
 */
        
 $app->get('/events', 'authenticate', function() {
     global $user_id;
     $response = array();
     $db = new DbHandler();
     
     // fetch all user events
     $result = $db->getAllUserEvents($user_id);
     $response["error"] = false;
     $response["events"] = $result;
     
     echoResponse(200, $response);
     
     
    });
    
/**
 * Creating new comment in db
 * method POST
 * params - event_id, content
 * url - /comments/
 */
$app->post('/comments', 'authenticate', function() use($app) {
    verifyRequiredParams(array('event_id', 'content'));
    
    global $user_id;
    $db = new DbHandler();
    
    $response = array();

    $eventID = $app->request->post('event_id');
    $content = $app->request->post('content');
    
    $response_code = 201;
    
    // user doesn't belong to event, not privileged to add comment
    if (!$db->isUserBelongsToEvent($user_id, $eventID)) {
        $response["error"] = true;
        $response["message"] = "Failed to add comment. Insufficient Privileges";
        $response_code = 403;
    }
    else {
        $commentId = $db->addCommentToEvent($eventID, $user_id, $content);
        if ($commentId != null) {
            $response["error"] = false;
            $response["message"] = "Comment Added Successfully";
            $response["comment"] = $db->getCommentByID($commentId);
        } else {
            $response["error"] = true;
            $response["message"] = "Failed to add comment. Please try again.";
            $response_code = 500;
        }
    }
    
    echoResponse($response_code, $response);
    });
    
/**
 * Listing all comments of particual event
 * method GET
 * params - event_id
 * url /comments       
 */
        
 $app->get('/comments', 'authenticate', function() use($app) {
     verifyRequiredParams(array('event_id'));
     
     global $user_id;
     $response = array();
     $db = new DbHandler();
     
     $eventID = $app->request->get('event_id');
     $response_code = 200;
     
    // user doesn't belong to event, not privileged to get comments
    if (!$db->isUserBelongsToEvent($user_id, $eventID)) {
        $response["error"] = true;
        $response["message"] = "Failed to get comments. Insufficient Privileges";
        $response_code = 403;
    }
    else {
        // fetch all comments for event
        $result = $db->getCommentsForEvent($eventID);
        $response["error"] = false;
        $response["comments"] = $result;
    }
     
     echoResponse($response_code, $response);
     
     
    });
    
/**
 * Listing all users whom the user who gave the request has privilege over
 * method GET
 * url /priv        
 */
        
 $app->get('/priv', 'authenticate', function() {
     global $user_id;
     $response = array();
     $db = new DbHandler();
     
     // fetch users
     $result = $db->getPrivForUser($user_id);
     $response["error"] = false;
     $response["members"] = $result;
     
     echoResponse(200, $response);
     
     
    });
    
    
/**
 * List free times for given users
 * method GET
 * params - [members], date_start, date_end, time_start, time_end, duration
 * url /freetimes   
 */
        
 $app->get('/freetimes', 'authenticate', function() use($app) {
     verifyRequiredParams(array('date_start', 'date_end',
         'time_start', 'time_end', 'duration'));
     
     global $user_id;
     
     $response = array();
     $db = new DbHandler();
     
     $members = array();
     $date_start = $app->request->get('date_start');
     $date_end = $app->request->get('date_end');
     $time_start = $app->request->get('time_start');
     $time_end = $app->request->get('time_end');
     $duration = $app->request->get('duration');
     
     // optional members param
     if (!IsNullOrEmptyString($app->request->get('members'))) {
        $members = explode(',', $app->request->get('members')); 
     }
     
    

     if (($key = array_search($user_id, $members)) !== false) {
       unset($members[$key]);
     }
     
     $response_code = 200;
     
    // user doesn't belong to event, not privileged to get comments
    if (!$db->hasMultiUserPriv($user_id, $members)) {
        $response["error"] = true;
        $response["message"] = "Failed to get free times. Insufficient Privileges";
        $response_code = 403;
    }
    else {
        
        array_push($members, $user_id);
        
        // get events for users in time range
        $events = $db->getEventsInTimeRange($members, $date_start, 
                $date_end, $time_start, $time_end);
        
        // get common free times
        $result = get_common_free_time($events, $duration,
                $date_start, $date_end, $time_start, $time_end);

        $response["error"] = false;
        $response["free_times"] = $result;
    }
     
     echoResponse($response_code, $response);
     
     
    });
        
        
    
$app->run();

?>