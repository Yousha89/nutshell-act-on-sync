<?php
/**
 * Created by PhpStorm.
 * User: ahsuoy
 * Date: 4/10/2016
 * Time: 3:35 PM
 *
 * Nutshell and Act-on synchronization
 *
 *
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once 'vendor/autoload.php';
require_once 'vendor/nutshell-api/NutshellApi.php';
require_once 'vendor/nutshell-api/NutshellApiException.php';
require_once 'config/app.php';

/* Define site domain */

define('SITE_DOMAIN', 'http://' . $_SERVER['HTTP_HOST'] . rtrim($_SERVER['PHP_SELF'], 'index.php'));

$app = new Slim\App($slim_settings($_SERVER['HTTP_HOST']));

$nutshellApi = new NutshellApi($nutshell_user(), $nutshell_api());

$app->nutshellApi = $nutshellApi;

require_once 'src/routes.php';

$app->run();