<?php
require '.././libs/Slim/Slim.php';

require_once 'passwordHash.php';

require '.././mongo/crud.php';
require '.././mongo/list.php';
require '.././mongo/command.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();

use Slim\Slim;
//localhost
//define('MONGO_HOST', 'localhost');
//qa server (mongolab)
define('MONGO_HOST', 'mongodb://selom:admin123@ds053080.mongolab.com:53080/ubadb');
define('MONGO_DB', 'ubadb');

$app = new \Slim\Slim();
$db = 'ubadb';

//Set default Time zone
date_default_timezone_set('Africa/Lagos');
/**
 * Routing
 */

$app->get(    '/:collection',      '_list');
$app->post(   '/:collection',      '_create');
$app->get(    '/:collection/:id',  '_read');
$app->put(    '/:collection/:id',  '_update');
$app->delete( '/:collection/:id',  '_delete');

// @todo: add count collection command mongo/commands.php

// List


function show($data) {
  header("Content-Type: application/json");
  echo json_encode($data);
  exit;
}


function _list($collection){
  
  $select = array(
    'limit' =>    (isset($_GET['limit']))   ? $_GET['limit'] : false, 
    'page' =>     (isset($_GET['page']))    ? $_GET['page'] : false,
    'filter' =>   (isset($_GET['filter']))  ? $_GET['filter'] : false,
    'regex' =>    (isset($_GET['regex']))   ? $_GET['regex'] : false,
    'sort' =>     (isset($_GET['sort']))    ? $_GET['sort'] : false
  );
  
  $data = mongoList(
    MONGO_HOST, 
    MONGO_DB, 
    $collection,
    $select
  );

 
  echoResponse(200,$data);

}

// Create

function _create($collection){

  $document = json_decode(Slim::getInstance()->request()->getBody(), true);

  $data = mongoCreate(
    MONGO_HOST, 
    MONGO_DB, 
    $collection, 
    $document
  ); 

}

// Read

function _read($collection, $id){

  $data = mongoRead(
    MONGO_HOST,
    MONGO_DB,
    $collection,
    $id
  );
  
  show($data);
}

// Update 

function _update($collection, $id){

  $document = json_decode(Slim::getInstance()->request()->getBody(), true);

  $data = mongoUpdate(
    MONGO_HOST, 
    MONGO_DB, 
    $collection, 
    $id,
    $document
  ); 
  
  show($data);
}

// Delete

function _delete($collection, $id){

  $data = mongoDelete(
    MONGO_HOST, 
    MONGO_DB, 
    $collection, 
    $id
  ); 
  
  show($data);
}

// Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");         

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    }


require_once 'authentication.php';

$app->options('/(:x+)', function() use ($app) {
    //...return correct headers...
    $app->response->setStatus(200);
});

$app->get("/", function() {
    echo "<h1>UBA GAME API</h1>";
});



function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}

function responseHanlder($data = null,$res) {
        $res->header('Content-Type', 'application/json');
        $res->header('Access-Control-Allow-Origin', '*');
        $res->header('Access-Control-Allow-Headers', 'x-requested-with, content-type');
        $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        $res->write(json_encode($data));
    }

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields,$request_params) {
    $error = false;
    $error_fields = "";
    foreach ($required_fields as $field) {
        if (!isset($request_params->$field) || strlen(trim($request_params->$field)) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        //$app = \Slim\Slim::getInstance();
        $response["status"] = "error";
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(200, $response);
        $app->stop();
    }
}

$app->run();
?>