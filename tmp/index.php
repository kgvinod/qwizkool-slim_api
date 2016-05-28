<?php

require 'vendor/autoload.php';
$app = new \Slim\Slim(array(
    'debug' => true
));

$app->get('/', function(){
    echo "Home Page";
}); 

$app->get('/qwizbook/:id', function ($id) {
	echo "Get book # $id";
});

$app->run();

?>
