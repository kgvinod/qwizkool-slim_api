<?php

/**
 * Qwizkool REST server.
 * Provides a simple REST API interface
 * based on slimPHP REST framework and RedBeanPHP ORM.
 *
 * @file    index.php
 * @author  Vinod.K.G, Balagopal.A
 * @license proprietary
 *
 * @copyright
 * copyright (c) Qwizkool.com
 */
// Non-composer loads
require 'vendor/redbean/rb.php';

// Chrome console logger
require 'vendor/ChromePhp/ChromePhp.php';
ChromePhp::log('Hello from qwizkool!');
#ChromePhp::log($_SERVER);
#ChromePhp::warn('something went wrong!');

// load required files (composer)
require 'vendor/autoload.php';

// Set teh logfile
//$logfile = 'logs/qkool.log.'.time();

// Set teh logfile
//$logfile = 'logs/qkool.log.'.time();

ini_set("display_errors","1");
error_reporting(E_ALL);

// Allow CORS
//header('Access-Control-Allow-Origin: http://swagger.qwizkool.com'); 


function my_error_handler($error_level, $error_message, $error_file, 
$error_line) {

    error_log("Error $error_level : $error_message in $error_file on line $error_line", 0);

}

//set error handler
set_error_handler("my_error_handler");


//echo "libs loaded\r\n";
function debug_to_console( $output ) {

    $debug_enabled = true;

    if ($debug_enabled) {
       //echo $output . " [**] ";
    }
    
    $logstr = $output . "\n\r";
 
    $logfile = 'logs/qkool.log';   
    file_put_contents($logfile, $logstr, FILE_APPEND | LOCK_EX); 
    //file_put_contents($file, $output); 
}

function header_log(&$header_var, $log ) {

    $header_var .= "[".$log."]##";

}


// Post process the exported http payload
function q_export($output) {

    // TODO : There has to be a better way to do this 

    $output_q1 = str_replace("ownQwizbooksections", "qwizbookSections", $output);
    $output_q2 = str_replace("ownQwizbookpages", "qwizbookPages", $output_q1);
    $output_q3 = str_replace("ownMediaurls", "mediaUrls", $output_q2);
    $output_q4 = str_replace("ownChoices", "choices", $output_q3);
    
    return $output_q4;

}

// set up database connection
//R::setup('mysql:host=localhost;dbname=appdata','user','pass');
R::setup('sqlite:qwizkool.db'); //sqlite
//R::freeze(true);
//R::setStrictTyping(false);  // Allow uppercase
//R::ext('xdispense', function($type){
//     return R::getRedBean()->dispense( $type);
//});
//RedBean_OODBBean::setFlagBeautifulColumnNames(false); 

//echo "db setup done ..\r\n";
debug_to_console( "******************* Start **************" );

debug_to_console( "main() : Slim app created" );

// set default conditions for route parameters



class ResourceNotFoundException extends Exception {}




//$app = new Slim\App();


$app = new Slim\App(array(

                          'cookies.secret_key' => 'my_secret_key',
                          'debug' => true,

                      ));


// route middleware for simple API authentication
function authenticate(\Slim\Route $route) {

    $app = \Slim\Slim::getInstance();
    $uid = $app->getEncryptedCookie('uid');
    $key = $app->getEncryptedCookie('key');
    if (validateUserKey($uid, $key) === false) {

        debug_to_console( "authenticate() : auth failed.. continue anyway" );
        //$app->halt(401);
    }

}


function validateUserKey($uid, $key) {

    // insert your (hopefully more complex) validation routine here

    if ($uid == 'demo' && $key == 'demo') {

        return true;

    } else {

        return false;

    }

}

/******************/
/* COMMON methods */
/******************/

function getItemInDb($inputArray, $item, $name) {

    if (property_exists($item, "id")) {
    
        debug_to_console ("getItemInDb() : Searching for " . $name . " id " . $item->id);    
 
        foreach( $inputArray as $foundItem ) {
        
            if ($foundItem->id == $item->id) {
                debug_to_console ("getItemInDb() : found match for " . $name . " id " . $item->id);
                return $foundItem;
            }
        }
    }
    else {
        debug_to_console ("getItemInDb() : " . $name . " without id - must be new !");        
    }

    debug_to_console ("getItemInDb() : did not find match for " . $name);    
    return NULL;                              
}


function getItemInInput($input, $itemDb, $name) {

    //if (property_exists($itemDb, "id")) { // should be true always

        for ($x = 0; $x < count($input); $x++) {
            //debug_to_console (__METHOD__ . "(): checking " . $name . " at input index " . $x);        
            if (property_exists($input[$x], "id")) {       
                //debug_to_console (__METHOD__ . "() : " . $name . " id " . $input[$x]->id);                    
                if ($input[$x]->id == $itemDb->id) {
                    debug_to_console (__METHOD__ . "() : found match for " . $name . " id " . $itemDb->id);
                    return $input[$x];
                }
            }
        }
                
   // }
    
    debug_to_console ("getItemInInput() : did not find match for " . $name . " id " . $itemDb->id);    
    return NULL;                              
}




// Note : Array has to be passed by reference !
function deleteItemInDb(&$parentArray, $item, $name) {

    unset($parentArray[$item->id]);
    debug_to_console ("deleteItemInDb() : deleted " . $name . " " . $item->id);
}



function addNewChoice(&$page, $choice) {

    debug_to_console ("addNewChoice() : Adding new choice");                
    $choice_new = R::dispense('choices');
    
    $choice_new->answer = $choice->answer;
    $choice_new->text = $choice->text;   
    $choice_new->media_type = $choice->media_type; 
    $choice_new->media_text = $choice->media_text;         
    $choice_new->media_url = $choice->media_url;    
    $choice_new->wide_media = $choice->wide_media;     
    
    $page->xownChoices[] = $choice_new;                               

}

function updateChoice(&$choiceDb, $choiceIn) {

    $choiceDb->answer = $choiceIn->answer;
    $choiceDb->text = $choiceIn->text;
    $choiceDb->media_type = $choiceIn->media_type;    
    $choiceDb->media_text = $choiceIn->media_text;        
    $choiceDb->media_url = $choiceIn->media_url;    
    $choiceDb->wide_media = $choiceIn->wide_media;     
    
    debug_to_console ("updateChoice() : updated choice");                    

}

function addNewMediaUrl(&$page, $murl) {

    debug_to_console ("addNewMediaUrl() : Adding new mediaUrl");                
    $murl_new = R::dispense('mediaurls');
    
    $murl_new->type = $murl->type;
    $murl_new->text = $murl->text;   
    $murl_new->media_url = $murl->media_url;    
    $murl_new->wide_media = $murl->wide_media;     
    
    $page->xownMediaurls[] = $murl_new;                               

}

function updateMediaUrl(&$murlDb, $murlIn) {

    $murlDb->type = $murlIn->type;
    $murlDb->text = $murlIn->text;
    $murlDb->media_url = $murlIn->media_url;    
    $murlDb->wide_media = $murlIn->wide_media;     
    
    debug_to_console ("updateMediaUrl() : updated mediaUrl");                    

}

/* QwizBooksection */


/* QwizBookpage */





function addNewQwizbookPage(&$section, $page) {

    debug_to_console ("addNewQwizbookPage() : Adding new page");                
    $page_new = R::dispense('qwizbookpages');
    
    $page_new->type = $page->type;
    $page_new->text = $page->text;   
    
    for ($x = 0; $x < count($page->mediaUrls); $x++) {

        addNewMediaUrl($page_new, $page->mediaUrls[$x]);              
    }     
    
    // Choices are available only for page type "Question"
    // TODO : perform exact checks against the page type
    if (property_exists($page, "choices")) {    
        for ($x = 0; $x < count($page->choices); $x++) {

            addNewChoice($page_new, $page->choices[$x]);              
        }       
    }
    
    $section->xownQwizbookpages[] = $page_new;                               

}

function updateQwizbookPage(&$pageDb, $pageIn) {

    $pageDb->type = $pageIn->type;
    $pageDb->text = $pageIn->text;
    $pageDb->choices = $pageIn->choices;     
    
    // Update and add murls
    for ($x = 0; $x < count($pageIn->mediaUrls); $x++) {
    
        $murlDb = getItemInDb($pageDb->xownMediaurls, $pageIn->mediaUrls[$x], "mediaUrl");
        if (is_null($murlDb)) {
            addNewMediaUrl($pageDb, $pageIn->mediaUrls[$x]);
        }
        else {
            updateMediaUrl($pageDb, $pageIn->mediaUrls[$x]);                                
        }               
   }    
    
    
    // Update and add choices
    for ($x = 0; $x < count($pageIn->choices); $x++) {
    
        $choiceDb = getItemInDb($pageDb->xownChoices, $pageIn->choices[$x], "choice");
        if (is_null($choiceDb)) {
            addNewChoice($pageDb, $pageIn->choices[$x]);
        }
        else {
            updateChoice($pageDb, $pageIn->choices[$x]);                                
        }               
   }        
    debug_to_console ("updateQwizbookPage() : updated page");                    

}

/* QwizBooksection */




function addNewQwizbookSection(&$qwizbookDb, $sectionIn) {

    debug_to_console ("addNewQwizbookSection() : Adding new section");                
    $qwizbookSection_new = R::dispense('qwizbooksections');
    $qwizbookSection_new->title = $sectionIn->title;
    $qwizbookSection_new->is_starting = $sectionIn->is_starting;   
    
    for ($x = 0; $x < count($sectionIn->qwizbookPages); $x++) {

        addNewQwizbookPage($qwizbookSection_new, $sectionIn->qwizbookPages[$x]);              
    }      
    
    $qwizbookDb->xownQwizbooksections[] = $qwizbookSection_new;                               

}

function updateQwizbookSection(&$sectionDb, $sectionIn) {

    $sectionDb->title = $sectionIn->title;
    $sectionDb->is_starting = $sectionIn->is_starting; 
    
    
    // Update and add pages
    for ($x = 0; $x < count($sectionIn->qwizbookPages); $x++) {
    
        $pageDb = getItemInDb($sectionDb->xownQwizbookpages, $sectionIn->qwizbookPages[$x], "qwizbookPage");
        if (is_null($pageDb)) {
            addNewQwizbookPage($sectionDb, $sectionIn->qwizbookPages[$x]);
        }
        else {
            updateQwizbookPage($pageDb, $sectionIn->qwizbookPages[$x]);                                
        }               
   }
   
    debug_to_console ("updateQwizbookSection() : updated section " . $sectionDb->id);                   

}



// Note: adding a trailing slash handles both /qwizbooks and /qwizbooks/
// handle GET requests for /qwizbook
$app->get('/qwizbooks/', function ($request, $response, $args) {

    ChromePhp::log("app->get() : url:/qwizbook");                   

    try {

        $mediaType = $request->getMediaType();
        $searchString = $request->getQueryParams()['search'];
        ChromePhp::log("app->get() : search string = " . $searchString);                           
        $searchLikeString = '%' . $searchString . '%';
        
        $qwizbook = R::find('qwizbook', ' title LIKE ? ', [$searchLikeString]);

        $response = $response->withHeader('Content-type', 'application/json');
        
        $export_1 = q_export(json_encode(R::exportAll($qwizbook)));
        $export_2 = "{\"qwizbooks\" : " . $export_1 . "}";
        echo $export_2;


    } catch (Exception $e) {

        $response = $response->withStatus(400);
        $response = $response->withHeader('X-Status-Reason', $e->getMessage());
    }

    // Support swagger's cross domain request
    $response = $response->withHeader('Access-Control-Allow-Origin', 'http://swagger.qwizkool.com'); 

    return $response;


});

// handle GET requests for /qwizbook/:id
$app->get('/qwizbooks[/{id}[/]]', function ($request, $response, $args) {

    ChromePhp::log("app->get() : url:/qwizbook/".$args['id']);                   

    
    try {

	ChromePhp::log("Search for qwizbook id ". $args['id']);

        $qwizbook = R::findOne('qwizbook', 'id=?', array($args['id']));
	
	ChromePhp::log("Found ". $qwizbook);

        if ($qwizbook) {

            $mediaType = $request->getMediaType();
            $response = $response->withHeader('Content-type', 'application/json');
            echo q_export(json_encode(R::exportAll($qwizbook)));

        } else {

            throw new ResourceNotFoundException();
        }

    } catch (ResourceNotFoundException $e) {

        $response = $response->withStatus(404);

    } catch (Exception $e) {

        $response = $response->withStatus(400);
        $response = $response->withHeader('X-Status-Reason', $e->getMessage());
    }

    // Support swagger's cross domain request
    $response = $response->withHeader('Access-Control-Allow-Origin', 'http://swagger.qwizkool.com'); 

    return $response;	

});


// handle POST requests for /qwizbook
$app->post('/qwizbooks/', function ($request, $response, $args) {

    $x_info_log = "";
    header_log($x_info_log, "app->post() : url:/qwizbooks");
        
    try {

        $mediaType = $request->getMediaType();
        header_log($x_info_log, "app->post() : mediaType :". $mediaType);

        $body = $request->getBody();

/*     
//$body = '{ "qwizbook": {"id": "1","title": "Qwizbook 2"}}';
//$body ='{"a":1,"b":"vinod","c":3,"d":4,"e":{"f":100}}';
$body = 
'{
  "qwizbook": 
    {
      "id": "1",
      "title": "Qwizbook 1",
      "subtitle": "An awesome learning experience",
      "description": "A qwizbook",
      "category": "uncategorized",
      "tags": "great cool",
      "owner": "kgvinod@gmail.com",
      "created_on": "2015-09-20T18:59:12.337Z",
      "public": "true",
      "sharing_enabled": "true",
      "shared_with": "none"
    }
}';
*/


        if ($mediaType == 'application/json') {
            //echo "app->post: decode json\r\n";
            $input = json_decode($body)->qwizbook;
        }
     
        header_log($x_info_log, "app->post() : Qwizbook Title :". (string)$input->title);

        $qwizbook = R::dispense('qwizbook');
        $qwizbook->title = (string)$input->title;
        $qwizbook->subtitle = (string)$input->subtitle;
        $qwizbook->description = (string)$input->description; // text area
        $qwizbook->category = (string)$input->category; 
        $qwizbook->tags = (string)$input->tags;
        $qwizbook->owner = (string)$input->owner; // email
        $qwizbook->created_on = (string)$input->created_on; // date
        $qwizbook->is_public = (string)$input->is_public; // boolean
        $qwizbook->sharing_enabled = (string)$input->sharing_enabled; // boolean
        $qwizbook->shared_with = (string)$input->shared_with; // email

        for ($x = 0; $x < count($input->qwizbookSections); $x++) {
        
            addNewQwizbookSection($qwizbook, $input->qwizbookSections[$x]);
        }

        $id = R::store($qwizbook);
        
        header_log($x_info_log, "app->post() : created id " . $id);

        if ($mediaType == 'application/json') {
            $response = $response->withHeader('Content-Type', 'application/json');
            echo q_export(json_encode(R::exportAll($qwizbook)));
        }

    } catch (Exception $e) {

        $response = $response->withStatus(400);
        $response = $response->withHeader('X-Status-Reason', 'line ' . $e->getLine() . ":" . $e->getMessage());

    }

    $response = $response->withHeader('X-Info-Log', $x_info_log);

    // Support swagger's cross domain request
    $response = $response->withHeader('Access-Control-Allow-Origin', 'http://swagger.qwizkool.com'); 

    return $response;	

});



// handle PUT requests for /qwizbook
$app->put('/qwizbooks[/{id}[/]]', function ($request, $response, $args) {

    ChromePhp::log("app->put() : url:/qwizbook/".$args['id']);                                
    
    try {
 
        $mediaType = $request->getMediaType();
        $body = $request->getBody();
        
        if ($mediaType == 'application/json') {

            $input = json_decode($body)->qwizbook;
        }

        $qwizbook = R::findOne('qwizbook', 'id=?', array($args['id']));

        if ($qwizbook) {

            $qwizbook->title = (string)$input->title;
            $qwizbook->subtitle = (string)$input->subtitle;
            $qwizbook->description = (string)$input->description; // text area
            $qwizbook->category = (string)$input->category; 
            $qwizbook->tags = (string)$input->tags;
            $qwizbook->owner = (string)$input->owner; // email
            $qwizbook->created_on = (string)$input->created_on; // date
            $qwizbook->is_public = (string)$input->is_public; // boolean
            $qwizbook->sharing_enabled = (string)$input->sharing_enabled; // boolean
            $qwizbook->shared_with = (string)$input->shared_with; // email
          
            // Delete the removed entries
            foreach( $qwizbook->xownQwizbooksections as $qwizbookSection ) {
                debug_to_console ("app->put() : checking section is in input ". $qwizbookSection->id);                   
                $qsectionInput = getItemInInput($input->qwizbookSections, $qwizbookSection, "qwizbookSection");
                if (is_null($qsectionInput)) {
                    deleteItemInDb($qwizbook->xownQwizbooksections, $qwizbookSection, "qwizbookSection");
                }
                else {
                
                    foreach( $qwizbookSection->xownQwizbookpages as $qwizbookPage ) {
                        debug_to_console ("app->put() : checking page is in input ". $qwizbookPage->id);                   
                        $qpageInput = getItemInInput($qsectionInput->qwizbookPages, $qwizbookPage, "qwizbookPage");                    
                        if (is_null($qpageInput)) {
                            deleteItemInDb($qwizbookSection->xownQwizbookpages, $qwizbookPage, "qwizbookPage");
                        }
                        else {
                        
                            foreach( $qwizbookPage->xownMediaUrls as $mediaUrl ) {
                                if (is_null(getItemInInput($qpageInput->mediaUrls, $mediaUrl, "mediaUrl"))) {
                                    deleteItemInDb($qwizbookPage->xownMediaUrls, $mediaUrl, "mediaUrl");
                                }
                                else {
                                
                                } 
                            }
                            
                            foreach( $qwizbookPage->xownChoices as $choice ) {
                                if (is_null(getItemInInput($qpageInput->choices, $choice, "choice"))) {
                                    deleteItemInDb($qwizbookPage->xownChoices, $choice, "choice");
                                }
                                else {
                                
                                } 
                            }                                                                    
                        } 
                    }              
                }
            }           
            
            // Update and add sections
            for ($x = 0; $x < count($input->qwizbookSections); $x++) {
            
                $sectionInDb = getItemInDb($qwizbook->xownQwizbooksections, $input->qwizbookSections[$x], "qwizbookSection");
                if (is_null($sectionInDb)) {
                    addNewQwizbookSection($qwizbook, $input->qwizbookSections[$x]);
                }
                else {
                    updateQwizbookSection($sectionInDb, $input->qwizbookSections[$x]);                                
                }               
           }
               

            R::store($qwizbook);
           
            if ($mediaType == 'application/json') {

                $response = $response->withHeader('Content-Type', 'application/json');
                echo q_export(json_encode(R::exportAll($qwizbook)));
            }
           

        } else {

            throw new ResourceNotFoundException();
        }

    } catch (ResourceNotFoundException $e) {

        $response = $response->withStatus(404);

    } catch (Exception $e) {

        $response = $response->withStatus(400);
        $response = $response->withHeader('X-Status-Reason', ' [file:] ' . $e->getFile() . ' [line:] ' . $e->getLine() . ":" . $e->getMessage());
    }

    // Support swagger's cross domain request
    $response = $response->withHeader('Access-Control-Allow-Origin', 'http://swagger.qwizkool.com'); 

    return $response;	

});



// handle DELETE requests for /qwizbook
$app->delete('/qwizbooks[/{id}]', function ($request, $response, $args) {

    
    try {

        $qwizbook = R::findOne('qwizbook', 'id=?', array($args['id']));

        if ($qwizbook) {

            R::trash($qwizbook);
            $response = $response->withStatus(204);

        } else {

            throw new ResourceNotFoundException();
        }

    } catch (ResourceNotFoundException $e) {

        $response = $response->withStatus(404);

    } catch (Exception $e) {

        $response = $response->withStatus(400);
        $response = $response->withHeader('X-Status-Reason', 'line ' . $e->getLine() . ":" . $e->getMessage());
    }
    

    // Support swagger's cross domain request
    $response = $response->withHeader('Access-Control-Allow-Origin', 'http://swagger.qwizkool.com'); 

    return $response;	

});


// handle OPTIONS requests for /qwizbook
$app->options('[/{params:.*}]', function ($request, $response, $args) {

    $response = $response->withStatus(200);

    // Support swagger's cross domain request
    $response = $response->withHeader('Access-Control-Allow-Headers', 'origin, content-type, accept, accept-language, content-type'); 
    $response = $response->withHeader('Access-Control-Allow-Origin', 'http://swagger.qwizkool.com');
    $response = $response->withHeader('Access-Control-Allow-Methods', 'POST, GET, PUT, OPTIONS, DELETE');  


    return $response;	

});


// run
$app->run();
