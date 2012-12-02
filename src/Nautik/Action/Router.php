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
 * Resolve the request to controller, action and parameters
 */
class Router {
	/**
	 * Standard conditions for the application routing
	 */
	protected static $standardConditions = array(
		"all" => "(.+)",
		"num" => "([0-9]+)",
		"id" => "([A-Fa-f0-9]{24})",
		"string" => "([A-Za-z0-9_-]{1,})",
		"controller" => "([a-z0-9_-]{1,})",
		"action" => "([A-Za-z0-9_-]{1,})",
		"year" => "([12][0-9]{3})",
		"month" => "(0[1-9]|1[012])",
		"day" => "(0[1-9]|[12][0-9]|3[01])"
	);

	/**
	 * Registered routes
	 */
	protected static $routes = array();
	
	/**
	 * Registered error handler
	 */
	protected static $targets = array();
	
	/**
	 * Register a new route
	 */
	public static function map($name, $rule, $target = array(), $conditions = array()) {
		// Add a new route
		if ( !isset( $target["action"] ) ) $target["action"] = "index";
		self::$routes[$name] = array("rule" => $rule, "target" => (object) $target, "conditions" => $conditions);
	}
	
	/**
	 * 
	 */
	public static function target($code, $target = array()) {
		// Add a new route
		self::$targets[$code] = (object) $target;
		self::$targets[$code]->parameters = array("code" => $code);
	}
	
	/**
	 * 
	 */
	public static function getTargetFor($code) {
		// Return target
		if ( isset( self::$targets[$code] ) )
			return self::$targets[$code];
		
		return false;
	}
	
	/**
	 *
	 */
	public static function register($tag, $condition) {
		// Add a condition
		self::$standardConditions[$tag] = $condition;
	}
	
	/**
	 * Resolves the request
	 */
	public static function resolve($requestUri) {		
		// No Routes? Return a 404
		if ( 0 === count( self::$routes ) )
			return false;
			
		// Process the routes and try to find a match
		foreach ( self::$routes as $name => $routing ):
			// Set the defaults
			$p_names = array();
			$p_values = array();
 
			// Try to get all @ varibales
			preg_match_all('@:([\w]+)@', $routing["rule"], $p_names, PREG_PATTERN_ORDER);
			$p_names = $p_names[0];
 
			// Get the conditions and prepare the route
			$conditions = self::$standardConditions + $routing["conditions"];
			$route = str_replace('*', '(.+)', $routing["rule"]);

			// Replace the @ varibales with correct regular expressions
			$url_regex = preg_replace_callback('@:[\w]+@', function($matches) use($conditions) {
				// Prepare the key
				$key = str_replace(':', '', $matches[0]);
				$condition = '([a-zA-Z0-9_\+\-%]{1,})';
				
				// Search condition
				if ( array_key_exists( $key, $conditions ) )
					$condition = $conditions[$key];

				// Return the condition
				return $condition;
			}, $route) . '/?';

			// Merge the @ varibales with theirs values
			if ( preg_match('@^' . $url_regex . '$@', $requestUri, $p_values ) ):
				// Shift the first value
				array_shift($p_values);
				
				// Merge
				$routing["target"]->parameters = array();
				foreach($p_names as $index => $value)
					$routing["target"]->parameters[substr($value, 1)] = urldecode($p_values[$index]);
				
				// Set the request details
				return $routing["target"];
			endif;
		endforeach;
		
		// Nothing found.
		return false;
	}
}
