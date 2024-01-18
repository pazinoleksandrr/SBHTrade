<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
* --------------------------------------------------------------------------
* Base Site URL
* --------------------------------------------------------------------------
*
* URL to your CodeIgniter root. Typically this will be your base URL,
* WITH a trailing slash:
*
*   http://example.com/
*
* If this is not set then CodeIgniter will try guess the protocol, domain
* and path to your installation. However, you should always configure this
* explicitly and never rely on auto-guessing, especially in production
* environments.
*
*/
define('APP_BASE_URL', '[base_url]');

/*
* --------------------------------------------------------------------------
* Encryption Key
* IMPORTANT: Do not change this ever!
* --------------------------------------------------------------------------
*
* If you use the Encryption class, you must set an encryption key.
* See the user guide for more info.
*
* http://codeigniter.com/user_guide/libraries/encryption.html
*
* Auto added on install
*/
define('APP_ENC_KEY', '[encryption_key]');

/**
 * Database Credentials
 * The hostname of your database server
 */
define('APP_DB_HOSTNAME', '[db_hostname]');
/**
 * The username used to connect to the database
 */
define('APP_DB_USERNAME', '[db_username]');
/**
 * The password used to connect to the database
 */
define('APP_DB_PASSWORD', '[db_password]');
/**
 * The name of the database you want to connect to
 */
define('APP_DB_NAME', '[db_name]');

/**
 * @since  2.3.0
 * Database charset
 */
define('APP_DB_CHARSET', 'utf8');
/**
 * @since  2.3.0
 * Database collation
 */
define('APP_DB_COLLATION', 'utf8_general_ci');

/**
 *
 * Session handler driver
 * By default the database driver will be used.
 *
 * For files session use this config:
 * define('SESS_DRIVER', 'files');
 * define('SESS_SAVE_PATH', NULL);
 * In case you are having problem with the SESS_SAVE_PATH consult with your hosting provider to set "session.save_path" value to php.ini
 *
 */
define('SESS_DRIVER', 'database');
define('SESS_SAVE_PATH', 'sessions');
define('APP_SESSION_COOKIE_SAME_SITE', 'Lax');

/**
 * Enables CSRF Protection
 */
define('APP_CSRF_PROTECTION', true);

//api
defined('NOW')                  OR define("NOW", date("Y-m-d H:i:s"));
defined('SECRET_KEY')           OR define('SECRET_KEY', '47b92ca4a4a60ae8139a66cc97f25636');
defined('SERVICE_NAME')         OR define('SERVICE_NAME', 'bitrihub.apiv01');
defined('COOKIE_NAME')          OR define('COOKIE_NAME', 'bitrihub_apiv01_token');
defined('CONTENT_TYPE')         OR define('CONTENT_TYPE', 'application/json;charset=UTF-8');//'application/json; charset=UTF-8' or 'application/json'
defined('SPA_URL')              OR define('SPA_URL', 'http://localhost:3000/');
defined('T_BLOCKED')            OR define('T_BLOCKED', 'tblblocked');

// http codes
//----success
defined('OK')                                OR define('OK', 200);
defined('CREATED')                           OR define('CREATED', 201);

//----client error
defined('BAD_REQUEST')                       OR define('BAD_REQUEST', 400);
defined('UNAUTHORIZED')                      OR define('UNAUTHORIZED', 401);
defined('PAYMENT_REQUIRED')                  OR define('PAYMENT_REQUIRED', 402);
defined('FORBIDDEN')                         OR define('FORBIDDEN', 403);
defined('NOT_FOUND')                         OR define('NOT_FOUND', 404);
defined('METHOD_NOT_ALLOWED')                OR define('METHOD_NOT_ALLOWED', 405);
defined('NOT_ACCEPTABLE')                    OR define('NOT_ACCEPTABLE', 406);
defined('PROXY_AUTHENTICATION_REQUIRED')     OR define('PROXY_AUTHENTICATION_REQUIRED', 407);
defined('REQUEST_TIMEOUT')                   OR define('REQUEST_TIMEOUT', 408);

defined('UNPROCESSABLE_ENTITY')              OR define('UNPROCESSABLE_ENTITY', 422);

defined('PROHIBITED')                       OR define('PROHIBITED', 405);
defined('BAD_DATA')                         OR define('BAD_DATA', 400);
//defined('BAD_CREDENTIALS')                  OR define('BAD_CREDENTIALS', 403);
//defined('UNAUTHORIZED')                     OR define('UNAUTHORIZED', 403);
//defined('NO_COOKIE')                        OR define('NO_COOKIE', 409);
//defined('SUCCESS')                          OR define('SUCCESS', 200);