<?php
/**
 * Created by PhpStorm.
 * User: ahsuoy
 * Date: 4/10/2016
 * Time: 3:46 PM
 */

$app->get('/', function($request, $response, $args) use ($app) {

    // First test with api call
    
    echo "<h4 style\"text-align: center;\">Lets try to fetch some data from current nutshell account!</h4><br>";
    $curParams = array(
        'query'          => null,
        'orderBy'        => 'id',
        'orderDirection' => 'ASC',
        'limit'          => 50,
        'page'           => 1,
        'stubResponses'  => true
    );

    $res = $app->nutshellApi->call('findContacts', $curParams);
    print_r($res);
})->setName('home');