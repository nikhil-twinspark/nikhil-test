<?php
/**
 * This is core configuration file.
 *
 * Use it to configure core behavior of Cake.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * CakePHP Debug Level:
 *
 * Production Mode:
 *     0: No error messages, errors, or warnings shown. Flash messages redirect.
 *
 * Development Mode:
 *     1: Errors and warnings shown, model caches refreshed, flash messages halted.
 *     2: As in 1, but also with full debug messages and SQL output.
 *
 * In production mode, flash messages redirect after a time interval.
 * In development mode, you need to click the flash message to continue.
 */
    Configure::write('debug', 2);

/**
 * Configure the Error handler used to handle errors for your application. By default
 * ErrorHandler::handleError() is used. It will display errors using Debugger, when debug > 0
 * and log errors with CakeLog when debug = 0.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle errors. You can set this to any callable type,
 *   including anonymous functions.
 *   Make sure you add App::uses('MyHandler', 'Error'); when using a custom handler class
 * - `level` - int - The level of errors you are interested in capturing.
 * - `trace` - boolean - Include stack traces for errors in log files.
 *
 * @see ErrorHandler for more information on error handling and configuration.
 */
    Configure::write('Error', array(
        'handler' => 'ErrorHandler::handleError',
        'level' => E_ALL & ~E_DEPRECATED,
        'trace' => true
    ));

/**
 * Configure the Exception handler used for uncaught exceptions. By default,
 * ErrorHandler::handleException() is used. It will display a HTML page for the exception, and
 * while debug > 0, framework errors like Missing Controller will be displayed. When debug = 0,
 * framework errors will be coerced into generic HTTP errors.
 *
 * Options:
 *
 * - `handler` - callback - The callback to handle exceptions. You can set this to any callback type,
 *   including anonymous functions.
 *   Make sure you add App::uses('MyHandler', 'Error'); when using a custom handler class
 * - `renderer` - string - The class responsible for rendering uncaught exceptions. If you choose a custom class you
 *   should place the file for that class in app/Lib/Error. This class needs to implement a render method.
 * - `log` - boolean - Should Exceptions be logged?
 * - `skipLog` - array - list of exceptions to skip for logging. Exceptions that
 *   extend one of the listed exceptions will also be skipped for logging.
 *   Example: `'skipLog' => array('NotFoundException', 'UnauthorizedException')`
 *
 * @see ErrorHandler for more information on exception handling and configuration.
 */
    Configure::write('Exception', array(
        'handler' => 'ErrorHandler::handleException',
        'renderer' => 'ExceptionRenderer',
        'log' => true
    ));

/**
 * Application wide charset encoding
 */
    Configure::write('App.encoding', 'UTF-8');
        Configure::write('variable_Name',array('top' => 'Верх', 'left' => 'Левое', 'right' => 'Правое', 'bottom' => 'Нижнее'));
 

/**
 * To configure CakePHP *not* to use mod_rewrite and to
 * use CakePHP pretty URLs, remove these .htaccess
 * files:
 *
 * /.htaccess
 * /app/.htaccess
 * /app/webroot/.htaccess
 *
 * And uncomment the App.baseUrl below. But keep in mind
 * that plugin assets such as images, CSS and Javascript files
 * will not work without url rewriting!
 * To work around this issue you should either symlink or copy
 * the plugin assets into you app's webroot directory. This is
 * recommended even when you are using mod_rewrite. Handling static
 * assets through the Dispatcher is incredibly inefficient and
 * included primarily as a development convenience - and
 * thus not recommended for production applications.
 */
    //Configure::write('App.baseUrl', env('SCRIPT_NAME'));

/**
 * To configure CakePHP to use a particular domain URL
 * for any URL generation inside the application, set the following
 * configuration variable to the http(s) address to your domain. This
 * will override the automatic detection of full base URL and can be
 * useful when generating links from the CLI (e.g. sending emails)
 */
    //Configure::write('App.fullBaseUrl', 'http://example.com');

/**
 * Web path to the public images directory under webroot.
 * If not set defaults to 'img/'
 */
    //Configure::write('App.imageBaseUrl', 'img/');

/**
 * Web path to the CSS files directory under webroot.
 * If not set defaults to 'css/'
 */
    //Configure::write('App.cssBaseUrl', 'css/');

/**
 * Web path to the js files directory under webroot.
 * If not set defaults to 'js/'
 */
    //Configure::write('App.jsBaseUrl', 'js/');

/**
 * Uncomment the define below to use CakePHP prefix routes.
 *
 * The value of the define determines the names of the routes
 * and their associated controller actions:
 *
 * Set to an array of prefixes you want to use in your application. Use for
 * admin or other prefixed routes.
 *
 *     Routing.prefixes = array('admin', 'manager');
 *
 * Enables:
 *    `admin_index()` and `/admin/controller/index`
 *    `manager_index()` and `/manager/controller/index`
 *
 */
    //Configure::write('Routing.prefixes', array('admin'));

/**
 * Turn off all caching application-wide.
 *
 */
    Configure::write('Cache.disable', true);

/**
 * Enable cache checking.
 *
 * If set to true, for view caching you must still use the controller
 * public $cacheAction inside your controllers to define caching settings.
 * You can either set it controller-wide by setting public $cacheAction = true,
 * or in each action using $this->cacheAction = true.
 *
 */
    //Configure::write('Cache.check', true);

/**
 * Enable cache view prefixes.
 *
 * If set it will be prepended to the cache name for view file caching. This is
 * helpful if you deploy the same application via multiple subdomains and languages,
 * for instance. Each version can then have its own view cache namespace.
 * Note: The final cache file name will then be `prefix_cachefilename`.
 */
    //Configure::write('Cache.viewPrefix', 'prefix');

/**
 * Session configuration.
 *
 * Contains an array of settings to use for session configuration. The defaults key is
 * used to define a default preset to use for sessions, any settings declared here will override
 * the settings of the default config.
 *
 * ## Options
 *
 * - `Session.cookie` - The name of the cookie to use. Defaults to 'CAKEPHP'
 * - `Session.timeout` - The number of minutes you want sessions to live for. This timeout is handled by CakePHP
 * - `Session.cookieTimeout` - The number of minutes you want session cookies to live for.
 * - `Session.checkAgent` - Do you want the user agent to be checked when starting sessions? You might want to set the
 *    value to false, when dealing with older versions of IE, Chrome Frame or certain web-browsing devices and AJAX
 * - `Session.defaults` - The default configuration set to use as a basis for your session.
 *    There are four builtins: php, cake, cache, database.
 * - `Session.handler` - Can be used to enable a custom session handler. Expects an array of callables,
 *    that can be used with `session_save_handler`. Using this option will automatically add `session.save_handler`
 *    to the ini array.
 * - `Session.autoRegenerate` - Enabling this setting, turns on automatic renewal of sessions, and
 *    sessionids that change frequently. See CakeSession::$requestCountdown.
 * - `Session.ini` - An associative array of additional ini values to set.
 *
 * The built in defaults are:
 *
 * - 'php' - Uses settings defined in your php.ini.
 * - 'cake' - Saves session files in CakePHP's /tmp directory.
 * - 'database' - Uses CakePHP's database sessions.
 * - 'cache' - Use the Cache class to save sessions.
 *
 * To define a custom session handler, save it at /app/Model/Datasource/Session/<name>.php.
 * Make sure the class implements `CakeSessionHandlerInterface` and set Session.handler to <name>
 *
 * To use database sessions, run the app/Config/Schema/sessions.php schema using
 * the cake shell command: cake schema create Sessions
 *
 */
    Configure::write('Session', array(
        'defaults' => 'php'
    ));

    Configure::write('Session.timeout', '300');

/**
 * A random string used in security hashing methods.
 */
    Configure::write('Security.salt', 'sk2QVc8Oa413VpR4qGTQde1PQPMh977dGQEc56ZJyIOeWpPqeePsd2tJQqpyFaf');

/**
 * A random numeric string (digits only) used to encrypt/decrypt strings.
 */
    Configure::write('Security.cipherSeed', '345235345623345235424');

/**
 * Apply timestamps with the last modified time to static assets (js, css, images).
 * Will append a query string parameter containing the time the file was modified. This is
 * useful for invalidating browser caches.
 *
 * Set to `true` to apply timestamps when debug > 0. Set to 'force' to always enable
 * timestamping regardless of debug value.
 */
    //Configure::write('Asset.timestamp', true);

/**
 * Compress CSS output by removing comments, whitespace, repeating tags, etc.
 * This requires a/var/cache directory to be writable by the web server for caching.
 * and /vendors/csspp/csspp.php
 *
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use HtmlHelper::css().
 */
    //Configure::write('Asset.filter.css', 'css.php');

/**
 * Plug in your own custom JavaScript compressor by dropping a script in your webroot to handle the
 * output, and setting the config below to the name of the script.
 *
 * To use, prefix your JavaScript link URLs with '/cjs/' instead of '/js/' or use JavaScriptHelper::link().
 */
    //Configure::write('Asset.filter.js', 'custom_javascript_output_filter.php');

/**
 * The class name and database used in CakePHP's
 * access control lists.
 */
    Configure::write('Acl.classname', 'DbAcl');
    Configure::write('Acl.database', 'default');
        define('AWS_KEY','AKIAJTJWV5FVP63JVX6A');
        define('AWS_SECRET','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');
        define('AWS_BUCKET','integrateortho_prod');
        define('AWS_server','https://s3.amazonaws.com/');

/**
 * Uncomment this line and correct your server timezone to fix
 * any date & time related errors.
 */
    date_default_timezone_set('UTC');
//        date_default_timezone_set('America/New_York');


/**
 *
 * Cache Engine Configuration
 * Default settings provided below
 *
 * File storage engine.
 *
 *      Cache::config('default', array(
 *        'engine' => 'File', //[required]
 *        'duration' => 3600, //[optional]
 *        'probability' => 100, //[optional]
 *         'path' => CACHE, //[optional] use system tmp directory - remember to use absolute path
 *         'prefix' => 'cake_', //[optional]  prefix every cache file with this string
 *         'lock' => false, //[optional]  use file locking
 *         'serialize' => true, //[optional]
 *         'mask' => 0664, //[optional]
 *    ));
 *
 * APC (http://pecl.php.net/package/APC)
 *
 *      Cache::config('default', array(
 *        'engine' => 'Apc', //[required]
 *        'duration' => 3600, //[optional]
 *        'probability' => 100, //[optional]
 *         'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *    ));
 *
 * Xcache (http://xcache.lighttpd.net/)
 *
 *      Cache::config('default', array(
 *        'engine' => 'Xcache', //[required]
 *        'duration' => 3600, //[optional]
 *        'probability' => 100, //[optional]
 *        'prefix' => Inflector::slug(APP_DIR) . '_', //[optional] prefix every cache file with this string
 *        'user' => 'user', //user from xcache.admin.user settings
 *        'password' => 'password', //plaintext password (xcache.admin.pass)
 *    ));
 *
 * Memcache (http://www.danga.com/memcached/)
 *
 *      Cache::config('default', array(
 *        'engine' => 'Memcache', //[required]
 *        'duration' => 3600, //[optional]
 *        'probability' => 100, //[optional]
 *         'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *         'servers' => array(
 *             '127.0.0.1:11211' // localhost, default port 11211
 *         ), //[optional]
 *         'persistent' => true, // [optional] set this to false for non-persistent connections
 *         'compress' => false, // [optional] compress data in Memcache (slower, but uses less memory)
 *    ));
 *
 *  Wincache (http://php.net/wincache)
 *
 *      Cache::config('default', array(
 *        'engine' => 'Wincache', //[required]
 *        'duration' => 3600, //[optional]
 *        'probability' => 100, //[optional]
 *        'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *    ));
 */

/**
 * Configure the cache handlers that CakePHP will use for internal
 * metadata like class maps, and model schema.
 *
 * By default File is used, but for improved performance you should use APC.
 *
 * Note: 'default' and other application caches should be configured in app/Config/bootstrap.php.
 *       Please check the comments in bootstrap.php for more info on the cache engines available
 *       and their settings.
 */
$engine = 'File';

// In development mode, caches should expire quickly.
$duration = '+999 days';
if (Configure::read('debug') > 0) {
    $duration = '+10 seconds';
}

// Prefix each application on the same server with a different string, to avoid Memcache and APC conflicts.
$prefix = 'myapp_';

/**
 * Configure the cache used for general framework caching. Path information,
 * object listings, and translation cache files are stored with this configuration.
 */
Cache::config('_cake_core_', array(
    'engine' => $engine,
    'prefix' => $prefix . 'cake_core_',
    'path' => CACHE . 'persistent' . DS,
    'serialize' => ($engine === 'File'),
    'duration' => $duration
));

/**
 * Configure the cache for model and datasource caches. This cache configuration
 * is used to store schema descriptions, and table listings in connections.
 */
Cache::config('_cake_model_', array(
    'engine' => $engine,
    'prefix' => $prefix . 'cake_model_',
    'path' => CACHE . 'models' . DS,
    'serialize' => ($engine === 'File'),
    'duration' => $duration
));
           
//local domain
define('Domain_Name','twinspark.co');
define('Buzzy_Name','http://bd.twinspark.co/buzzy/');   
define('Buzzy','bd');                               
define('Staff_Name','/');
//local key
define('Facebook_APP_ID','1042211419137673');
define('Facebook_Secret_Key','6a6110cc8ea7d24eac0dda0152d751e3');
//qa domain
//define('Domain_Name','sourcefuse.com');   
//aws domain
//define('Domain_Name','integrateortho.sourcefuse.com');
//stg domain
//define('Domain_Name','integratestg.sourcefuse.com');
//qa buzzy
//define('Buzzy_Name','http://buzzydoc.sourcefuse.com/');
//stg buzzy
//define('Buzzy_Name','http://buzzydocstg.sourcefuse.com/');
                                       


//dev key



define('DEF_CHALLENGE_HEADER_IMAGE','<img width="730" height="300" src="'.Staff_Name.'img/reward_imges/challanges.jpg">');

define('DEF_CHALLENGE_NAME','ENTER A CONTEST TODAY TO WIN A PRIZE');
define('DEF_CHALLENGE_DESC','We are making the contest for u to make a more points and redeem the rewards....');
define('DEF_CHALLENGE_AREA','<p><img src="'.Staff_Name.'img/reward_imges/pop_up.jpg" style="height:271px; width:558px" /></p>

<h2>contest name<br />
50 PoinTS</h2>

<p>enteries left: 21/30<br />
time left: 02:20:35</p>

<p>ENTER NOW</p>

<p>SHARE</p>

<p><img alt="facebook" src="'.Staff_Name.'img/reward_imges/facebook.png" style="height:22px; width:23px" /><img alt="twitter" src="'.Staff_Name.'img/reward_imges/twitter.png" style="height:22px; width:23px" /><img alt="gpluse" src="'.Staff_Name.'img/reward_imges/googleplus.png" style="height:22px; width:23px" /></p>

<p><strong>Details/Instructions:</strong> Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
');
define('SUPER_ADMIN_EMAIL','nikhil.verma@twinspark.co');
define('SUPER_ADMIN_EMAIL_STAFF','nikhil.verma@twinspark.co');
//define('SUPER_ADMIN_EMAIL','help@buzzdoc.com');

//define('SUPER_ADMIN_EMAIL','help@buzzdoc.com');
////////////////////////////add by sahoo////////////////////////
define('PUBLIC_KEY', 'AKIAJ7LOWJKIJTX7JGRA');
define('PRIVATE_KEY', 'Yx1/+1/5hKDgFJRgtouUNaEuhbPt1uBsP62ZnjPc');
define('ASSOCIATE_TAG', 'httpwwwint097-20');

////for live
//define("AUTHORIZENET_API_LOGIN_ID", "33H64PNDpapN");
//define("AUTHORIZENET_TRANSACTION_KEY", "4gY9Z4y44j9YHher");
//define("AUTHORIZENET_SANDBOX", false);
//for dev
define("AUTHORIZENET_API_LOGIN_ID", "599uBzqfUB");
define("AUTHORIZENET_TRANSACTION_KEY", "9RS5T7gJ68b4t2xH");
define("AUTHORIZENET_SANDBOX", true);
//authorize test url

define("AUTHORIZENET_URL", 'https://test.authorize.net/profile/manage');

//authorize live url

//define("AUTHORIZENET_URL", 'https://secure.authorize.net/profile/manage');
//for dev
define("PLATFORM_ID", "BuzzyDocTest");
define("PLATFORM_KEY", "VFF4dq5DTzrFXzre7rjSBLlts2rOYrwFwYlznDxJwLnXy6FmixMk0Id8JhI");
define("TANGO_MODE", "sandbox");


//for dev
//define("BEANSTREAM_COMPANY", "buzzydoc");
//define("BEANSTREAM_ID", "buzzydoc");
//define("BEANSTREAM_Password", "Buzzy@123");

//for live
define("BEANSTREAM_COMPANY", "Buzzy");
define("BEANSTREAM_ID", "admin");
define("BEANSTREAM_Password", "SFitb123$");


//for live
//define("PLATFORM_ID", "BuzzyDoc");
//define("PLATFORM_KEY", "xcDeXy5APlZL8Hoi4iE4E1xOxBg4nL2qxF3P8KZ6T0XwgPwyb8ng4SQqE");
//define("TANGO_MODE", "production");
define("POINTS_PER_DOLLAR", "50");

define('PERFORMING_PATIENT_BADGE','Performing Patient Badge');
define('POLISHED_PATIENT_BADGE','Polished Patient Badge');
define('PERFECT_PATIENT_BADGE','Perfect Patient Badge');
define('COMPLETION_BADGE','Completion Badge');

//define to stop debit balance from buzzydoc bank
define('DEBIT_FROM_BANK','0');
define('MAILTYPE','Smtp');

require_once dirname(__DIR__) . '/Vendor/autoload.php';