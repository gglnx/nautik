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

/**
 * Declare UTF-8 and the namespace
 */
declare(encoding='UTF-8');
namespace Nautik\Action;

/**
 * 
 */
class View {
	/**
	 *
	 */
	public static function ldate(\Twig_Environment $env, $date, $format = null, $locale = null, $timezone = null) {
		// Get the format
		if ( null === $format ):
			$formats = $env->getExtension('core')->getDateFormat();
			$format = $date instanceof \DateInterval ? $formats[1] : $formats[0];
		endif;

		// Get DateTime object
		$date = \twig_date_converter($env, $date, $timezone);

		// Convert format into a strftime string
		$format = strtr((string) $format, array(
			'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A',
			'N' => '%u', 'w' => '%w', 'z' => '%j', 'W' => '%V', 
			'F' => '%B', 'm' => '%m', 'M' => '%b', 'o' => '%G',
			'Y' => '%Y', 'y' => '%y', 'a' => '%P', 'A' => '%p',
			'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M',
			's' => '%S', 'O' => '%z', 'T' => '%Z', 'U' => '%s'
		));

		// Set locale
		if ( null !== $locale )
			setlocale(LC_TIME, $locale);
		
		// Format date
		return strftime($format, $date->format("U"));
	}
}
