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
	protected function _renderAction($controller, $action, $parameter = array()) {
		// Run controller action partly
		Request::setParameters($parameter);
		return Dispatcher::runAction($controller, $action);
	}
	
	/**
	 *
	 */
	protected function _useTemplate($template, $data = array(), $code = 200) {
		// Set the HTTP header status
		Response::setStatus($code);
		
		// Set the template file
		Response::setTemplate($template);
		
		return $data;
	}
	
	/**
	 *
	 */
	protected function _renderJson($data, $code = 200) {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();

		// Set the HTTP header status
		Response::setStatus($code);

		// Set the minetype
		Response::setMinetype('json');

		// Set the json
		Response::disableTemplate();

		return json_encode($data);
	}
	
	/**
	 *
	 */
	protected function _renderText($text, $code = 200, $minetype = 'html') {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();

		// Set the HTTP header status
		Response::setStatus($code);
		
		// Set the minetype
		Response::setMinetype($minetype);

		// Disable templating
		Response::disableTemplate();

		return $text;
	}
	
	/**
	 *
	 */
	protected function _render404() {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();
		
		// Not target for 404 errors defined
		if ( false == ( $router = Router::getTargetFor( 404 ) ) )
			throw new \Nautik\Core\Exception("No target for a 404 error page was defined.");
				
		// Set http status
		Response::setStatus(404);
		
		// Run error action
		Request::setParameters($router->parameters);
		Dispatcher::$currentRoute = $router;
		return Dispatcher::runAction($router->controller, $router->action);
	}

	/**
	 * _redirect(string $location[, int $code, bool $sendIt])
	 *
	 * Wrapper for Response::redirect(). Sends a redirect with 302 HTTP status code (can
	 * be changed with the $code parameter, use 301 for permanent redirects). If $sendIt 
	 * is false the redirect will not exits the application.
	 */
	protected function _redirect($location, $code = 302, $sendIt = true) {
		// Check if a output action has be already performed
		$this->_checkIfPerformed();
		
		// Perform a redirect
		return Response::redirect($location, $code, $sendIt);
	}

	/**
	 * _message(string $type, string $type)
	 *
	 * Adds a flash message to show on the next request of the user. Use it
	 * with a redirect:
	 *
	 * return $this->_message('success', 'Yay!')->_redirect('/success');
	 */
	protected function _message($type, $message) {
		// Save message
		$_SESSION["message"] = array('type' => $type, 'message' => $message);

		return $this;
	}
	
	/**
	 *
	 */
	private function _checkIfPerformed() {
		// Check if a return action was already preformed
		if ( $this->__returnActionPerformed )
			throw new \Nautik\Core\Exception('Render and/or redirect actions can only called once in one action.');
	
		// Set return action preformed to true
		$this->__returnActionPerformed = true;
	}
}
