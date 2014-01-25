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
class Nautik implements \Symfony\Component\HttpKernel\HttpKernelInterface {
	/**
	 * With this configuration variable you can enable the debug mode
	 * of the application. All errors will be displayed.
	 */
	public $debug = true;

	/**
	 * Default timezone of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/timezones
	 */
	public $defaultTimezone = "GMT";

	/**
	 * Locale of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/setlocale
	 */
	public $locale = "";
	
	/**
	 * Default route
	 * Used to catch not found routes and display an 404 error page
	 */
	public $defaultRoute = ['_controller' => 'Errors', '_action' => '404'];

	/**
	 * Instance of Twig_Environment
	 *
	 * @see http://twig.sensiolabs.org/api/master/Twig_Environment.html
	 */
	public $templateRender;

	/**
	 * Instance of Symfony\Component\Routing\Router
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/Routing/Router.html
	 */
	public $routing;

	/**
	 * Instance of Symfony\Component\HttpFoundation\RequestStack
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/RequestStack.html
	 */
	public $requestStack;

	/**
	 * Instance of Symfony\Component\HttpFoundation\Response
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Response.html
	 */
	public $response;

	/**
	 * Instance of Symfony\Component\HttpFoundation\Session\Session
	 *
	 * @see http://api.symfony.com/master/Symfony/Component/HttpFoundation/Session/Session.html
	 */
	public $session;

	/**
	 * preApplicationStart()
	 * 
	 * Hook which can be overwritten in Application.php, excuted before
	 * the dispatcher is called.
	 */
	public function preApplicationStart() {
		// Nothing, overwrite it!
	}

	/**
	 * startSessionHandler()
	 *
	 * 
	 */
	public function startSessionHandler() {
		// Use auto expiring flash bag
		$flashbag = new \Symfony\Component\HttpFoundation\Session\Flash\AutoExpireFlashBag();

		// Init session handler
		$this->session = new \Symfony\Component\HttpFoundation\Session\Session(null, null, $flashbag);

		// Start session
		$this->session->start();
	}

	/**
	 * setUpEnvironment()
	 */
	public function setUpEnvironment() {
		// Set default timezone
		date_default_timezone_set($this->defaultTimezone);

		// Throw php errors as exceptions
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
		});

		// Set locale
		setlocale(LC_ALL, $this->locale);
	}

	/**
	 * setUpRouting()
	 */
	public function setUpRouting($context = null) {
		// Init Routing
		$this->routing = new \Symfony\Component\Routing\Router(
			// Use yaml
			new \Symfony\Component\Routing\Loader\YamlFileLoader(new \Symfony\Component\Config\FileLocator([APP])),
			
			// Use routes.yml from application
			APP . 'Config' . DIRECTORY_SEPARATOR . 'routes.yml',

			// Options
			array(
				'cache_dir' => APP . 'Cache' . DIRECTORY_SEPARATOR . 'routing',
				'debug' => $this->debug
			),

			// Context
			$context
		);
	}

	/**
	 * setUpTwig()
	 */
	public function setUpTwig() {
		// Init Twig as template render
		$this->templateRender = new \Twig_Environment(new \Twig_Loader_Filesystem(APP . 'Views'), array(
			'cache' => APP . 'Cache' . DIRECTORY_SEPARATOR . 'templates',
			'debug' => $this->debug
		));

		// Set timezone
		$this->templateRender->getExtension('core')->setTimezone($this->defaultTimezone);

		// Add routing extension
		$twigRoutingExtension = new TwigRoutingExtension();
		$twigRoutingExtension->setApplication($this);
		$this->templateRender->addExtension($twigRoutingExtension);
	}

	/**
	 * handle()
	 * 
	 * Function to start the application and the framework behind it
	 */
	public final function handle(\Symfony\Component\HttpFoundation\Request $request, $type = self::MASTER_REQUEST, $catch = true) {
		// Set up environment
		$this->setUpEnvironment();

		// Init request stack and request
		$this->requestStack = new \Symfony\Component\HttpFoundation\RequestStack;
		$this->requestStack->push($request);

		// Init response from HttpFoundation
		$this->response = \Symfony\Component\HttpFoundation\Response::create()->prepare($this->requestStack->getCurrentRequest());

		// Start session
		$this->startSessionHandler();

		// Request context for routing
		$context = new \Symfony\Component\Routing\RequestContext();
		$context->fromRequest($this->requestStack->getCurrentRequest());

		// Setup routing
		$this->setUpRouting($context);

		// Setup twig
		$this->setUpTwig();
		
		// Allow access to symfony component form views
		$this->templateRender->addGlobal("request", $this->requestStack->getCurrentRequest());
		$this->templateRender->addGlobal("response", $this->response);
		$this->templateRender->addGlobal("routing", $this->routing);
		$this->templateRender->addGlobal("session", $this->session);
		$this->templateRender->addGlobal("flash", $this->session->getFlashBag());

		// Pre hook
		$this->preApplicationStart();

		// Find controller and action
		try {
			// Match path
			$currentRoute = $this->routing->match($this->requestStack->getCurrentRequest()->getPathInfo());
		} catch ( \Symfony\Component\Routing\Exception\ResourceNotFoundException $e ) {
			// Use default route
			$currentRoute = $this->defaultRoute;
		}

		// Set action
		if ( !isset( $currentRoute['_action'] ) )
			$currentRoute['_action'] = 'index';

		// Set request parameters
		$this->requestStack->getCurrentRequest()->attributes = new \Symfony\Component\HttpFoundation\ParameterBag($currentRoute);

		// Try to load the requested controller and action
		$data = $this->performAction($currentRoute['_controller'], $currentRoute['_action']);

		// Render template
		if ( !isset( $this->response->template ) || false !== $this->response->template ):
			// Generate template name if not set
			if ( !isset( $this->response->template ) )
				$this->response->template = strtolower($currentRoute['_controller'] . '/' . $currentRoute['_action']);
			
			// Load and render template
			$data = $this->templateRender->loadTemplate($this->response->template . '.html.twig')->render($data);
		endif;

		// Add content
		$this->response->setContent($data);

		// Return response
		return $this->response;
	}

	/**
	 * handleTask()
	 */
	public final function handleTask() {
		// Set up environment
		$this->setUpEnvironment();

		// Setup routing
		$this->setUpRouting();

		// Setup twig
		$this->setUpTwig();

		// Pre hook
		$this->preApplicationStart();
	}

	/**
	 * performAction(string $controller, string $action)
	 *
	 * Creates instance of the controller and runs the action method. Besides
	 * the data returned from the action public variables from the controller
	 * will be added to the returned array.
	 */
	public function performAction($controller, $action) {
		// Check if controller exists
		if ( false == is_file( $controllerLocation = APP . 'Controllers/' . $controller . '.php' ) )
			throw new ControllerNotFoundException();
		
		// Include and init the controller
		$controllerClass = "\\Application\\Controllers\\{$controller}";
		$controllerObj = new $controllerClass($this);

		// Check if the action exists
		if ( 'Errors' != $controller && false == method_exists($controllerObj, $action.= "Action" ) )
			throw new ActionNotFoundException();
		// Check if the error display action exists
		elseif ( $controller == $this->defaultRoute['_controller'] && false == method_exists($controllerObj, $action = "display" . $action ) )
			throw new ErrorActionNotFoundException();
		
		// Run the action
		$data = $controllerObj->{$action}();

		// Data fetching
		if ( false === $controllerObj->__returnActionPerformed ):
			// Format returned data
			if ( false == is_array( $data ) )
				$data = (array) $data;
		
			// Start reflection for data loading
			$ref = new \ReflectionObject($controllerObj);
			$properties = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
			$controllerData = array();
			
			// Get public data
			foreach ( $properties as $property ):
				// Don't add internal variabels
				if ( '__returnActionPerformed' == $property->getName() )
					continue;
						
				// Add property to controller data
				$controllerData[$property->getName()] = $property->getValue($controllerObj);
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
	private $application;

	/**
	 *
	 */
	public function setApplication(Nautik $application) {
		$this->application = $application;
	}

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
		return $this->application->routing->generate($name, $parameters, $relative ? \Symfony\Component\Routing\Generator\UrlGeneratorInterface::RELATIVE_PATH : \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH);
	}

	/**
	 * 
	 */
	public function getUrl($name, $parameters = array(), $schemeRelative = false) {
		return $this->application->routing->generate($name, $parameters, $schemeRelative ? \Symfony\Component\Routing\Generator\UrlGeneratorInterface::NETWORK_PATH : \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
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
	 *
	 */
	private $application;

	/**
	 *
	 */
	public function __construct(Nautik $application) {
		$this->application = $application;
	}

	/**
	 *
	 */
	public function getApplication() {
		return $this->application;
	}
	
	/**
	 * useTemplate(string $template[, int $status = 200])
	 *
	 * Sets the current template to $template, also allows the response
	 * HTTP status code.
	 */
	protected function useTemplate($template, $status = 200, $minetype = 'html') {
		// Set the HTTP header status
		$this->response()->setStatusCode($status);

		// Set the minetype
		$this->response()->headers->set('Content-Type', $this->request()->getMimeType($minetype));
		
		// Set the template file
		$this->response()->template = $template;
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

		// Add request informationen
		$jsonResponse->prepare($this->application->requestStack->getCurrentRequest());

		// Replace response object
		$this->application->response = $jsonResponse;
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
		$fileResponse->prepare($this->application->requestStack->getCurrentRequest());

		// Replace response object
		$this->application->response = $fileResponse;
	}
	
	/**
	 * renderText(string $text[, int $status = 200, string $minetype = 'html'])
	 *
	 */
	protected function renderText($text, $status = 200, $minetype = 'html') {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();

		// Set template settings
		$this->useTemplate(false, $status, $minetype);

		return $text;
	}
	
	/**
	 *
	 */
	protected function renderError($status, $parameters = array()) {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();

		// Set templating
		$this->useTemplate("errors/" . $status, $status);
		
		// Set parameters
		$this->request()->attributes = new \Symfony\Component\HttpFoundation\ParameterBag($parameters);

		// Run error action
		return $this->application->performAction($this->application->defaultRoute['_controller'], $status);
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
		
		// Create a redirect
		$redirectResponse = \Symfony\Component\HttpFoundation\RedirectResponse::create($location, $status, $headers);

		// Add request informationen
		$redirectResponse->prepare($this->application->requestStack->getCurrentRequest());

		// Replace response object
		$this->application->response = $redirectResponse;

		// Don't use a template
		$this->useTemplate(false, $status);
	}

	/**
	 *
	 */
	protected function cookie($name, $default = null) {
		// Get cookie
		return $this->request()->cookie->get($name, $default);
	}

	/**
	 *
	 */
	protected function setCookie($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true) {
		// Create cookie
		$cookie = new \Symfony\Component\HttpFoundation\Cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);

		// Add cookie to response
		$this->response()->headers->addCookie($cookie);
	}

	/**
	 *
	 */
	protected function clearCookie($name, $path = '/', $domain = null) {
		// Clear cookie
		$this->response()->headers->clearCookie($name, $path, $domain);
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
		return $this->application->session->getFlashBag();
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
		return $this->application->session;
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
		return $this->application->requestStack->getCurrentRequest();
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
		return $this->application->response;
	}

	/**
	 * url(string $name[, array $parameters = array()])
	 *
	 * Generates a URL or path for a specific route based on the given parameters. Parameters
	 * that reference placeholders in the route pattern will substitute them in the path or
	 * hostname. Extra params are added as query string to the URL.
	 */
	protected function url($name, $parameters = array(), $schemeRelative = false) {
		// Generate URL
		return $this->application->routing->generate(
			$name,
			$parameters,
			$schemeRelative ?
				\Symfony\Component\Routing\Generator\UrlGeneratorInterface::NETWORK_PATH :
				\Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
		);
	}

	/**
	 * path(string $name[, array $parameters = array()])
	 *
	 * Generates a URL or path for a specific route based on the given parameters. Parameters
	 * that reference placeholders in the route pattern will substitute them in the path or
	 * hostname. Extra params are added as query string to the URL.
	 */
	protected function path($name, $parameters = array(), $relative = false) {
		// Generate URL
		return $this->application->routing->generate(
			$name,
			$parameters,
			$relative ?
				\Symfony\Component\Routing\Generator\UrlGeneratorInterface::RELATIVE_PATH :
				\Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_PATH
		);
	}

	/**
	 *
	 */
	protected function get($name, $default = null) {
		return $this->request()->query->get($name, $default);
	}

	/**
	 *
	 */
	protected function post($name, $default = null) {
		return $this->request()->get($name, $default);
	}

	/**
	 *
	 */
	protected function attr($name, $default = null) {
		return $this->request()->attributes->get($name, $default);
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
		return $this->request()->isXmlHttpRequest();
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
