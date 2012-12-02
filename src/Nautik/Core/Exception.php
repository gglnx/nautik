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

namespace Nautik\Core;

/**
 * Exception handler
 */
class Exception extends \Exception {
	/**
	 *
	 */
	public static function handler($exception) {
		exit(self::formatException($exception));
	}
	
	/**
	 *
	 */
	public static function phpErrorHandler($errno, $errstr, $errfile, $errline) {
		throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
	}
	
	/**
	 *
	 */
	private static function formatException($exception) {
		// Generate html header
		$formatedException = "<h1>Exception at " . $_SERVER['REQUEST_URI'] . "</h1><h2>" . $exception->getMessage() . "</h2>";
		
		// Display the code trace
		if ( count( $backtrace = $exception->getTrace() ) > 1 ):
			$formatedException.= "<p><strong>Trace in execution order:</strong></p><pre><ol>";
			
			// Parse the backtrace
			foreach ( array_reverse( $backtrace ) as $trace ):   
				// Don't show the php error handler
				if ( "phpErrorHandler" == $trace['function'] )
					continue;

				// Parse and format the arguments
				$args = "";
				if ( isset( $trace['args'] ) && is_array( $trace['args'] ) ):
					foreach ( $trace['args'] as $index => $arg ):
						if ( is_null( $arg ) ) $trace['args'][$index] = 'null';
						elseif ( is_array( $arg ) ) $trace['args'][$index] = 'array[' . sizeof($arg) . ']';
						elseif ( is_object( $arg ) ) $trace['args'][$index] = get_class($arg) . ' Object';
						elseif ( is_bool( $arg ) ) $trace['args'][$index] = $arg ? 'true' : 'false';
						elseif ( is_int( $arg ) ) $trace['args'][$index] = $arg;
						else $trace['args'][$index] = preg_replace("/[\n\r]/", "", htmlspecialchars(substr($arg, 0, 64)));
					endforeach;
					$args = implode($trace['args'], ", ");
				endif;

				// Format the function trace
				$formatedException.= "<li>" . ( isset( $trace['class'] ) ? $trace['class'] . $trace['type'] : "" ) . $trace['function'] . "(" . $args . ")" . ( isset( $trace['line'] ) ? " on line " . $trace['line'] : "" ) . ( isset( $trace['file'] ) ? " in " . $trace['file'] : "" ) . "</li>";
			endforeach;
			
			$formatedException.= "</ol></pre>";
		endif;
		
		// Exception footer
		$formatedException.= "<h3>Error was thrown on line " . $exception->getLine() . " in <code>" . $exception->getFile() . "</code></h3>";
		
		return $formatedException;
	}
}
