<?php
/**
 * @package     Nautik
 * @version     1.0-$Id$
 * @link        http://github.com/gglnx/nautik
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2012, Dennis Morhardt
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

namespace Nautik\Action;

/**
 * The dispatcher coordinates the request of the user, sends data to the controller
 * and receive data from it to render a template and display the website
 */
class Dispatcher {
	/**
	 *
	 */
	public static $currentRoute;

	/**
	 *
	 */
	public static $templateRender;

	/**
	 * Starts the dispatcher and renders the page from the request
	 */
	public static function run($base = "/") {
		// Resolve the request through the router
		if ( false == ( self::$currentRoute = Router::resolve( Request::getRequestUri() ) ) ):
			// Not target for 404 errors defined
			if ( false == ( self::$currentRoute = Router::getTargetFor( 404 ) ) )
				throw new \Nautik\Core\Exception("No target for a 404 error page was defined.");
				
			// Set http status
			Response::setStatus(404);
		endif;

		// Set request parameters
		Request::setParameters(self::$currentRoute->parameters);
			
		// Try to load the requested controller and action
		$data = self::runAction(self::$currentRoute->controller, self::$currentRoute->action);
		
		// Generate the output if it is not disabled
		if ( Response::isOutput() ):
			// Send headers
			Response::sendHeaders();

			// Check if a template sould be rendered
			if ( Response::useTemplate() ):
				// Get the template
				if ( false == ( $template = Response::getTemplate() ) )
					$template = self::$currentRoute->controller . '/' . self::$currentRoute->action . '.' . Response::getMinetype();
				else
					$template = $template . '.' . Response::getMinetype();
					
				// Add .twig file extension
				$template = $template . '.twig';

				// Check if the template exists
				if ( false === file_exists( APP . 'views/' . $template ) )
					throw new \Nautik\Core\Exception("Template '$template' doesn't exists.");

				// Load and render template
				exit(self::$templateRender->loadTemplate($template)->render($data));
			endif;

			// Exit with the template
			exit($data);
		endif;
	}

	/**
	 *
	 */
	public static function runAction($controller, $action) {
		// Check if controller exists
		if ( false == is_file( $controllerLocation = APP . 'controllers/' . $controller . '.php' ) )
			throw new \Nautik\Core\Exception("Controller '$controller' doesn't exists.");
		
		// Include and init the controller
		include $controllerLocation;
		$controller = "\\App\\" . $controller . "Controller";
		$controller = new $controller;

		// Check if the action exists
		if ( false == method_exists($controller, $action.= "Action" ) )
			throw new \Nautik\Core\Exception("Action '$action' doesn't exists.");
		
		// Run the action
		$data = $controller->{$action}();

		// Data fetching
		if ( false === $controller->__returnActionPerformed ):
			// Format returned data
			if ( false == is_array( $data ) )
				$data = array('_output' => $data);
		
			// Start reflection for data loading
			$ref = new \ReflectionObject($controller);
			$properties = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
			$globalData = array();
			
			// Get public data
			foreach ( $properties as $property ):
				// Don't add internal variabels
				if ( '__returnActionPerformed' == $property->getName() )
					continue;
						
				// Get value of this property
				$value = $property->getValue($controller);

				// Leave models and stdClass, remove other objects
				if ( is_object( $value ) && false == ( $value instanceof \Nautik\Data\Query || $value instanceof \Nautik\Data\Model || $value instanceof \stdClass ) )
					continue;
						
				// Add property to available data
				$globalData[$property->getName()] = $value;
			endforeach;

			// Merge data from controller and action
			$data = array_merge($globalData, $data);

			// Add request information
			$data["_request"] = (array) self::$currentRoute;

			// Add flash message
			if ( isset( $_COOKIE["message"] ) && is_array( $message = @unserialize( $_COOKIE["message"] ) ) ):
				// Get message
				$data["_flash"] = array("type" => $message[0], "message" => $message[1]);

				// Remove old messages
				setcookie("message", "", time()-3600, "/");
			endif;
		endif;
		
		// Return the object
		return $data;
	}
}
