<?php
/**
 * @package     Nautik
 * @version     2.0
 * @link        https://github.com/gglnx/nautik
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2013, Dennis Morhardt
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Namespace
 */
namespace Nautik;

/**
 * Exceptions
 */
class NautikException extends \Exception { }
class ControllerNotFoundException extends NautikException { }
class ActionNotFoundException extends NautikException { }
class ErrorActionNotFoundException extends NautikException { }
class ReturnActionAlreadyPerformedException extends NautikException { }

/**
 * Main class, contains start process and dispatcher function
 */
class Nautik {
	/**
	 * With this configuration variable you can enable the debug mode
	 * of the application. All errors will be displayed.
	 */
	public static $debug = true;

	/**
	 * Default timezone of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/timezones
	 */
	public static $defaultTimezone = "GMT";

	/**
	 * Locale of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/setlocale
	 */
	public static $locale = "";
	
	/**
	 * Default route
	 * Used to catch not found routes and display an 404 error page
	 */
	public static $defaultRoute = ['_controller' => 'Errors', '_action' => '404'];

	/**
	 * Instance of Twig_Environment
	 *
	 * @see http://twig.sensiolabs.org/api/master/Twig_Environment.html
	 */
	public static $templateRender;

	/**
	 * Instance of Symfony\Component\Routing\Router
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/Routing/Router.html
	 */
	public static $routing;

	/**
	 * Instance of Symfony\Component\HttpFoundation\Request
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Request.html
	 */
	public static $request;

	/**
	 * Instance of Symfony\Component\HttpFoundation\Response
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Response.html
	 */
	public static $response;

	/**
	 * Instance of Symfony\Component\HttpFoundation\Session\Session
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Session.html
	 */
	public static $session;

	/**
	 * preApplicationStart()
	 * 
	 * Hook which can be overwritten in Application.php, excuted before
	 * the dispatcher is called.
	 */
	public static function preApplicationStart() {
		// Nothing, overwrite it!
	}

	/**
	 * afterApplicationStart()
	 *
	 * Hook which can be overwritten in Application.php, excuted after
	 * the dispatcher was called.
	 */
	public static function afterApplicationStart() {
		// Nothing, overwrite it!
	}

	/**
	 * startSessionHandler()
	 *
	 * 
	 */
	public static function startSessionHandler() {
		// Use auto expiring flash bag
		$flashbag = new \Symfony\Component\HttpFoundation\Session\Flash\AutoExpireFlashBag();

		// Init session handler
		static::$session = new \Symfony\Component\HttpFoundation\Session\Session(null, null, $flashbag);

		// Start session
		static::$session->start();
	}

	/**
	 * run()
	 * 
	 * Function to start the application and the framework behind it
	 */
	public final static function run() {
		// Set default timezone
		date_default_timezone_set(static::$defaultTimezone);

		// Throw php errors as exceptions
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
		});

		// Set locale
		setlocale(LC_ALL, static::$locale);

		// Init request from HttpFoundation
		static::$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

		// Init response from HttpFoundation
		static::$response = \Symfony\Component\HttpFoundation\Response::create()->prepare(static::$request);

		// Start session
		static::startSessionHandler();

		// Request context for routing
		$context = new \Symfony\Component\Routing\RequestContext();

		// Init Routing
		static::$routing = new \Symfony\Component\Routing\Router(
			// Use yaml
			new \Symfony\Component\Routing\Loader\YamlFileLoader(new \Symfony\Component\Config\FileLocator([APP])),
			
			// Use routes.yml from application
			APP . 'config/routes.yml',

			// Options
			array(
				'cache_dir' => APP . 'cache/routing',
				'debug' => static::$debug
			),

			// Context
			$context->fromRequest(static::$request)
		);

		// Init Twig as template render
		static::$templateRender = new \Twig_Environment(new \Twig_Loader_Filesystem(APP . 'views'), array(
			'cache' => APP . 'cache/templates',
			'debug' => static::$debug
		));

		// Set timezone
		static::$templateRender->getExtension('core')->setTimezone(static::$defaultTimezone);
		
		// Allow access to symfony component form views
		static::$templateRender->addGlobal("request", static::$request);
		static::$templateRender->addGlobal("response", static::$response);
		static::$templateRender->addGlobal("routing", static::$routing);
		static::$templateRender->addGlobal("session", static::$session);
		static::$templateRender->addGlobal("flash", static::$session->getFlashBag());
		
		// Add routing extension
		static::$templateRender->addExtension(new TwigRoutingExtension());

		// Pre hook
		static::preApplicationStart();

		// Run the dispatcher, if run is not silence
		if ( false == defined( 'SILENCE' ) || 1 !== SILENCE ):
			static::dispatch();
		endif;

		// After hook
		static::afterApplicationStart();
	}

	/**
	 * dispatch()
	 *
	 * The dispatcher coordinates the request of the user, sends data to the controller
	 * and receive data from it to render a template and displays the requested page
	 */
	public static function dispatch() {
		// Find controller and action
		try {
			// Match path
			$currentRoute = static::$routing->match(static::$request->getPathInfo());
		} catch ( \Symfony\Component\Routing\Exception\ResourceNotFoundException $e ) {
			// Use default route
			$currentRoute = static::$defaultRoute;
		}

		// Set action
		if ( !isset( $currentRoute['_action'] ) )
			$currentRoute['_action'] = 'index';

		// Set request parameters
		static::$request->attributes = new \Symfony\Component\HttpFoundation\ParameterBag($currentRoute);

		// Try to load the requested controller and action
		$data = static::performAction($currentRoute['_controller'], $currentRoute['_action']);

		// Render template
		if ( !isset( static::$response->template ) || false !== static::$response->template ):
			// Generate template name if not set
			if ( !isset( static::$response->template ) )
				static::$response->template = $currentRoute['_controller'] . '/' . $currentRoute['_action'];
			
			// Load and render template
			$data = static::$templateRender->loadTemplate(static::$response->template. '.html.twig')->render($data);
		endif;

		// Add content
		static::$response->setContent($data);

		// Send HTTP headers and content
		static::$response->send();
		exit;
	}

	/**
	 * performAction(string $controller, string $action)
	 *
	 * Creates instance of the controller and runs the action method. Besides
	 * the data returned from the action public variables from the controller
	 * will be added to the returned array.
	 */
	public static function performAction($controller, $action) {
		// Check if controller exists
		if ( false == is_file( $controllerLocation = APP . 'controllers/' . $controller . '.php' ) )
			throw new ControllerNotFoundException();
		
		// Include and init the controller
		include $controllerLocation;
		$controllerClass = "\\App\\Controllers\\{$controller}";
		$controllerClass = new $controllerClass;

		// Check if the action exists
		if ( 'Errors' != $controller && false == method_exists($controllerClass, $action.= "Action" ) )
			throw new ActionNotFoundException();
		// Check if the error display action exists
		elseif ( $controller == static::$defaultRoute['_controller'] && false == method_exists($controllerClass, $action = "display" . $action ) )
			throw new ErrorActionNotFoundException();
		
		// Run the action
		$data = $controllerClass->{$action}();

		// Data fetching
		if ( false === $controllerClass->__returnActionPerformed ):
			// Format returned data
			if ( false == is_array( $data ) )
				$data = (array) $data;
		
			// Start reflection for data loading
			$ref = new \ReflectionObject($controllerClass);
			$properties = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
			$controllerData = array();
			
			// Get public data
			foreach ( $properties as $property ):
				// Don't add internal variabels
				if ( '__returnActionPerformed' == $property->getName() )
					continue;
						
				// Add property to controller data
				$controllerData[$property->getName()] = $property->getValue($controllerClass);
			endforeach;

			// Merge data from controller and action
			$data = array_merge($controllerData, $data);
		endif;
		
		// Return the object
		return $data;
	}
}

/**
 * Provides integration of the Routing component with Twig.
 */
class TwigRoutingExtension extends \Twig_Extension {
	/**
	 * 
	 */
	public function getName() {
		return 'routing';
	}

	/**
	 * 
	 */
	public function getFunctions() {
		return array(
			'url' => new \Twig_Function_Method($this, 'getUrl', array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
			'path' => new \Twig_Function_Method($this, 'getPath', array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
		);
	}

	/**
	 * 
	 */
	public function getPath($name, $parameters = array(), $relative = false) {
		return \App\Application::$routing->generate($name, $parameters, $relative ? \Symfony\Component\Routing\Generator\UrlGeneratorInterface::RELATIVE_PATH : \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH);
	}

	/**
	 * 
	 */
	public function getUrl($name, $parameters = array(), $schemeRelative = false) {
		return \App\Application::$routing->generate($name, $parameters, $schemeRelative ? \Symfony\Component\Routing\Generator\UrlGeneratorInterface::NETWORK_PATH : \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
	}

	/**
	 * 
	 */
	public function isUrlGenerationSafe(\Twig_Node $argsNode) {
		$paramsNode = $argsNode->hasNode('parameters') ? $argsNode->getNode('parameters') : (
			$argsNode->hasNode(1) ? $argsNode->getNode(1) : null
		);

		if ( null === $paramsNode || $paramsNode instanceof \Twig_Node_Expression_Array && count( $paramsNode ) <= 2 && ( !$paramsNode->hasNode( 1 ) || $paramsNode->getNode( 1 ) instanceof \Twig_Node_Expression_Constant ) )
			return array('html');

		return array();
	}
}

/**
 * Base class for every controller in the application
 */
class Controller {
	/**
	 * 
	 */
	public $__returnActionPerformed = false;
	
	/**
	 * useTemplate(string $template[, int $status = 200])
	 *
	 * Sets the current template to $template, also allows the response
	 * HTTP status code.
	 */
	protected function useTemplate($template, $status = 200) {
		// Set the HTTP header status
		\App\Application::$response->setStatusCode($status);
		
		// Set the template file
		\App\Application::$response->template = $template;
	}
	
	/**
	 * renderJson(array $data[, string $callback = null, int $status = 200, array $headers = array()])
	 *
	 * 
	 */
	protected function renderJson($data, $callback = null, $status = 200, $headers = array()) {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();

		// Create a JSON response
		$jsonResponse = \Symfony\Component\HttpFoundation\JsonResponse::create($data, $status, $headers);

		// Set callback
		if ( null !== $callback )
			$jsonResponse->setCallback($callback);

		// Send json to client
		$jsonResponse->send();

		// Exit application
		exit;
	}

	/**
	 * 
	 */
	protected function renderFile($file, $status = 200, $headers = array(), $public = true, $contentDisposition = null, $autoEtag = false, $autoLastModified = true) {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();

		// Create a file response
		$fileResponse = \Symfony\Component\HttpFoundation\BinaryFileResponse::create($file, $status, $headers, $public, $contentDisposition, $autoEtag, $autoLastModified);
	
		// Add request informationen
		$fileResponse->prepare(\App\Application::$request);

		// Send file to client
		$fileResponse->send();

		// Exit application
		exit;
	}
	
	/**
	 * renderText(string $text[, int $status = 200, string $minetype = 'html'])
	 *
	 */
	protected function renderText($text, $status = 200, $minetype = 'html') {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();

		// Set the HTTP header status
		\App\Application::$response->setStatusCode($status);
		
		// Set the minetype
		\App\Application::$response->headers->set('Content-Type', \App\Application::$request->getMimeType($minetype));

		// Disable templating
		\App\Application::$response->template = false;

		return $text;
	}
	
	/**
	 *
	 */
	protected function renderError($status, $parameters = array()) {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();
				
		// Set HTTP status code
		\App\Application::$response->setStatusCode($status);
		
		// Set parameters
		\App\Application::$request->attributes = new \Symfony\Component\HttpFoundation\ParameterBag($parameters);

		// Set template
		\App\Application::$response->template = "errors/" . $status;

		// Run error action
		return \App\Application::performAction('errors', $status);
	}

	/**
	 * 
	 */
	protected function render404($parameters = array()) {
		return $this->renderError(404, $parameters);
	}

	/**
	 * redirect(string $location[, int $status = 302, array $headers = array()])
	 *
	 * Sends a redirect to the client, defaults with a 302 HTTP status code (can
	 * be changed with the $status parameter, use 301 for permanent redirects).
	 */
	protected function redirect($location, $status = 302, $headers = array()) {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();
		
		// Perform a redirect
		\Symfony\Component\HttpFoundation\RedirectResponse::create($location, $status, $headers)->send();

		// Exit application
		exit;
	}

	/**
	 *
	 */
	protected function cookie($name, $default = null) {
		return \App\Application::$request->cookie->get($name, $default);
	}

	/**
	 *
	 */
	protected function setCookie($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true) {
		// Create cookie
		$cookie = new \Symfony\Component\HttpFoundation\Cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);

		// Add cookie to response
		\App\Application::$response->headers->addCookie($cookie);
	}

	/**
	 *
	 */
	protected function clearCookie($name, $path = '/', $domain = null) {
		// Clear cookie
		\App\Application::$response->headers->clearCookie($name, $path, $domain);
	}

	/**
	 * flash()
	 * 
	 * Access the flash bag from Symfony\Component\HttpFoundation\Session\Session
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Flash/FlashBag.html
	 */
	protected function flash() {
		// Get flash message bag
		return \App\Application::$session->getFlashBag();
	}

	/**
	 * session()
	 *
	 * Access Symfony\Component\HttpFoundation\Session\Session
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Session.html
	 */
	protected function session() {
		// Get session
		return \App\Application::$session;
	}

	/**
	 * request()
	 *
	 * Access Symfony\Component\HttpFoundation\Request
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Request.html
	 */
	protected function request() {
		// Get request
		return \App\Application::$request;
	}

	/**
	 * response()
	 *
	 * Access Symfony\Component\HttpFoundation\Response
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Response.html
	 */
	protected function response() {
		// Get response
		return \App\Application::$response;
	}

	/**
	 * url(string $name[, array $parameters = array()])
	 *
	 * Generates a URL or path for a specific route based on the given parameters. Parameters
	 * that reference placeholders in the route pattern will substitute them in the path or
	 * hostname. Extra params are added as query string to the URL.
	 */
	protected function url($name, $parameters = array()) {
		// Generate URL
		return \App\Application::$routing->generate($name, $parameters);
	}

	/**
	 *
	 */
	protected function get($name, $default = null) {
		return \App\Application::$request->query->get($name, $default);
	}

	/**
	 *
	 */
	protected function post($name, $default = null) {
		return \App\Application::$request->request->get($name, $default);
	}

	/**
	 *
	 */
	protected function attr($name, $default = null) {
		return \App\Application::$request->attributes->get($name, $default);
	}

	/**
	 *
	 */
	protected function isMethodRequest($method) {
		return ( strtolower( $method ) == strtolower( $_SERVER["REQUEST_METHOD"] ) );
	}
	
	/**
	 *
	 */
	protected function isPostRequest() {
		return $this->isMethodRequest("post");
	}
	
	/**
	 *
	 */
	protected function isGetRequest() {
		return $this->isMethodRequest("get");
	}
	
	/**
	 *
	 */
	protected function isDeleteRequest() {
		return $this->isMethodRequest("delete");
	}
	
	/**
	 *
	 */
	protected function isPutRequest() {
		return $this->isMethodRequest("put");
	}
	
	/**
	 *
	 */
	protected function isAjaxRequest() {
		return \App\Application::$request->isXmlHttpRequest();
	}
	
	/**
	 * _checkIfPerformed()
	 */
	private function _checkIfPerformed() {
		// Check if a return action was already preformed
		if ( $this->__returnActionPerformed )
			throw new ReturnActionAlreadyPerformedException();
	
		// Set return action preformed to true
		$this->__returnActionPerformed = true;
	}
}
