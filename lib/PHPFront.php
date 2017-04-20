<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 * @package		PHPFront
 * @author		Oxford Harrison <phpfront@gmail.com>
 * @copyright	2016 Ox-Harris Creative
 * @license		GPL-3.0
 * @version		Release: 1.0.0
 * @link		https://www.facebook.com/PHPFront/
 * @uses		PHPFrontNodelist	to retrieve elements of a Nodelist or auto-generated elements in a particular order.
 * @see			PHPFrontNodelist
 */	 



/**
 * Try loading the PHPFrontDom class.
 * If found, then there may be some global autoloader like Composer.
 * If not, we manually include the core lib classess.
 */
if (!class_exists('PHPFrontDom'))
{
     require_once dirname(__FILE__) . '/PHPFrontDom.php';
     require_once dirname(__FILE__) . '/PHPFrontElement.php';
     require_once dirname(__FILE__) . '/PHPFrontNodeList.php';
}







/** 
 * The PHPFront Machine.
 * Builds up data stack with every data associated with an element,
 * accepts a HTML template and renders data into it.
 * Built upon the PHP DOMDocument.
 *
 * @todo	Some regex operations within getElementsBySelector().
 *			Currently, they intentional throw Exception as reminders.
 */
Class PHPFront
{
	const FN_PREPEND = 'prepend';
	const FN_APPEND = 'append';
	const FN_REPLACE = 'replace';
	
	const FN_BEFORE = 'before';
	const FN_AFTER = 'after';
	const FN_AS_CHILD = 'as_child';

	/**
     * Holds the current PHPFront version.
     *
     * @var string $version
	 *
	 * This is used internally. But readonly via magic __get()
	 *
	 * @access private
     */
	private static $version = '1.0.1';
	
	/**
     * Holds the mininum required PHP version.
     *
     * @var string $min_php_version
	 *
	 * This is used internally. But readonly via magic __get()
	 *
	 * @access private
     */
	private static $min_php_version = '5.3.0';	
	
	
	
	private $PHPFrontDom;
	
	private $is_template_rendered;
	private $allow_html_formatting;
	
	/**
     * Gives information about the current installation of PHPFront.
     *
	 * @api
     * 
	 * @return void
     */
    public static function info()
	{
		echo 'PHPFront - v'.self::$version."<br />\r\n";
		echo 'Current PHP version - v'.PHP_VERSION."<br />\r\n";
		echo 'Min PHP version - v'.self::$min_php_version."<br />\r\n";
    }
	
	/**
     * Constructor for PHPFront.
     *
     * Checks version compatibilty.
     * 
	 * @triggers E_USER_WARNING
	 *
	 * @return void
     */
	public function __construct()
	{
		//Check the application's minimum supported PHP version.
		if (version_compare(PHP_VERSION, self::$min_php_version, '<'))
		{
			die('You need to use PHP '.self::$min_php_version.' or higher to run this version of PHPFront!');
		}
		
		// Loads the PHPFrontDom 
		$this->PHPFrontDom = new PHPFrontDom('1.0'); 
		$this->PHPFrontDom->registerNodeClass('DOMElement', 'PHPFrontElement');
		//$this->PHPFrontDom->registerNodeClass('DOMNodeList', 'PHPFrontNodeList');

		$this->PHPFrontDom->formatOutput = $this->allow_html_formatting;
		
		//$doc->preserveWhiteSpace = false;
		//$doc->recover = true;
		$this->PHPFrontDom->substituteEntities = false;
	}

	/**
     * Destructor for PHPFront.
     *
     * Frees up all memory used. May not be neccessary.
     * 
	 * @return void
     */
    public function __destruct()
	{
        // Frees memory associated with the PHPFrontDom
		unset($this->PHPFrontDom);
    }
	
	/**
     * Magic get for PHPFront.
     *
     * Retrieves a private of protected property. This makes them readonly.
     * 
	 * @throws Exception
     * 
	 * @return void
     */
    public function __get($param_name)
	{
		if (isset($this->{$param_name}))
		{
			return $this->{$param_name};
		}
		
		throw new Exception('Property '.$param_name.' not found in PHPFront!');
    }
	
	/**
     * Give PHPFront a template to use for rendering.
     *
     * Ok to have used the assign() method before calling this.
	 * But be sure that setTemplate() has been called before the render() method;
	 * this makes the template available for render()
     * 
     * @param string	$template A valid HTML markup string, or a relative or absolute path to a HTML file
	 *
	 * @see PHPFront::assign()
	 * @see PHPFront::render()
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @triggers E_USER_WARNING
	 *
	 * @uses PHPFrontDom to load a HTML template.
	 * @return void
     */
	public function setTemplate($template)
	{
		libxml_use_internal_errors(true);
		
		// Load template
		if (preg_match("/<[^<].*>/", $template))
		{
			// $template is a markup string
			$this->PHPFrontDom->loadHTML($template);
		}
		elseif (!empty($template))
		{
			if (is_file($template))
			{
				// $template is a file
				$this->PHPFrontDom->loadHTMLFile($template);
			}
			else
			{
				trigger_error(__METHOD__ .': Template file not found! '.$template.'', E_USER_WARNING);
				return;
			}
		}
		else
		{
			trigger_error(__METHOD__ .': No HTML data provided');
			return;
		}
		
		libxml_clear_errors();
		$this->is_template_rendered = false; 
	}
		
	
	public function find($selector)
	{
		return $this->PHPFrontDom->getElementsBySelector($selector);
	}
	
	
	/**
     * Render the template, get it whole or part.
	 * 
     * @param bool		$print			Whether to print rendered document or not.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
     *
     * @access public
	 *
	 * @return	void|string				void if the rendered template is echoed, depending on PHPFront::$auto_print_output_on_render. String if returned.
     */
	public function render($print = true)
	{
		// Set the 'rendered' flag
		$this->is_template_rendered = true;
		
		// Normalize
		$this->PHPFrontDom->normalizeDocument();
		// $HTMLDoc
		$HTMLDoc = $this->PHPFrontDom->saveHTML();
		
		if (!$print)
		{
			return $HTMLDoc;
		}
		
		echo $HTMLDoc;
	}
	
	
	
	/**
     * Return part of the template after rendering.
	 * 
     * @param string	$element_selector	An element selector specifying the element to return.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
     *
     * @access public
	 *
	 * @return	string				The specified element if available.
     */
	public function getRendered($element_selector = null)
	{
		if (!$this->is_template_rendered)
		{
			$HTMLDoc = $this->render(false);
			if (empty($element_selector))
			{
				// We already have all we need
				return $HTMLDoc;
			}
		}
		
		return $this->PHPFrontDom->saveHTML($element_selector);
	}
}



