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
 * @used-by		PHPFront
 */	 




/** 
 * The PHPFrontDom.
 * Extends the PHP DOMDocument and provides methods that are natively available.
 *
 * @todo	Some regex operations within getElementsBySelector().
 *			Currently, they intentional throw Exception as reminders.
 */
Class PHPFrontDom extends DOMDocument
{
	/**
     * Handle to all XPath queries on the currently loaded template.
     *
     * @var object $xpath
	 *
	 * @see setTemplate()
	 * @see getElementsBySelector()
	 * 
     * This is used internally
     *
     * @access private
     */
	private $xpath;
	
	
	/**
     * Stores all XPath queries ever run on the DOM. This includes CSS selectors that were converted to XPath.
     *
     * @var array $queries
	 *
	 * @see getQueries()
	 * @see getElementsBySelector()
	 * 
     * This is used internally
     *
     * @access private
     */
	private $queries = array();


	/**
     * A configuration property that tells PHPFrontDom how to interprete element selectors.
	 * Options are: css (the default), xpath.
     *
	 * Element selector string must be a valid css selector if this setting is 'css', or a valid xpath query if otherwise.
	 * To override this global setting and interprete certain selectors as a different type, selector strings must
	 * be enclosed in parentheses prefixed by the selector type, as if a function call to the type. css(#valid-css-selector), xpath(/valid/xpath/query).
	 *
     * @var string $default_element_selector_type
	 *
	 * @see getElementsBySelector()
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $default_element_selector_type = 'css';
	
	
	
	
	/**
     * Loads a HTML string into DOMDocument
     * 
     * @param string	$source A valid HTML markup string
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @throws DOMException
	 *
	 * @uses DOMDocument::loadHTML().
	 * @return bool TRUE on success or FALSE on error.
     */
	public function loadHTML($source, $options = null)
	{
		// Loads a HTML string
		$return = parent::loadHTML($source);
		
		// xpath handle on this document
		$this->xpath = new DOMXpath($this);
		
		// Return original return value
		return $return;
	}



	/**
     * Loads a HTML file into DOMDocument
     * 
     * @param string	$source A relative or absolute path to a HTML file
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @throws DOMException
	 *
	 * @uses DOMDocument::loadHTMLFile().
	 * @return bool TRUE on success or FALSE on error.
     */
	public function loadHTMLFile($filename, $options = null)
	{
		// Loads a HTML file
		$return = parent::loadHTMLFile($filename);
		
		// xpath handle on this document
		$this->xpath = new DOMXpath($this);
		
		// Return original return value
		return $return;
	}
	
	
	
	/**
     * Get a DOMNodelist of element in the currently loaded template using an element selector.
	 *
	 * Also used by PHPFrontNodeList::populateNodeList().
	 * Notice how css selectors are converted to respective xpath query equivalent.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see PHPFront::$default_element_selector_type}
     * @param string	$element_path		An xpath query string specifying from which element to find requested element.
     * @param string	$evaluate_query		In case a css selector was provide, whether or not to run the xpath query it was translated into or return xpath equivalent string.
	 *
	 * @throws Exception
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	string|DOMNodelist			XPath query string or DOMNodelist depending on the $evaluate_query flag.
     */
    public function getElementsBySelector($element_selector, $element_path = '//', $evaluate_query = true)
	{
		// Clean up
		$element_selector = strtolower(trim($element_selector));
       
		$selector_build = '';
		// Returns an array of matched elements
		if (($this->default_element_selector_type == 'xpath' && substr($element_selector, 0, 4) != 'css:') || (/*$this->default_element_selector_type == 'css' && */substr($element_selector, 0, 6) == 'xpath:'))
		{
			if (substr($element_selector, 0, 6) == 'xpath:')
			{
				$query = substr(trim($element_selector), 6);
			}
			
			$selector_build = $query;
		}
		elseif (($this->default_element_selector_type == 'css' && substr($element_selector, 0, 6) != 'xpath:') || (/*$this->default_element_selector_type == 'xpath' && */substr($element_selector, 0, 4) == 'css:'))
		{
			if (substr($element_selector, 0, 4) == 'css:')
			{
				$query = substr(trim($element_selector), 4);
			}

			// Sanitize string
			$element_selector = str_replace(array('  ', '::'/**/), array(' ', ':'/**/), trim($element_selector));
			// Ususally found in attributes...
			$element_selector = str_replace(array(' = ', '= ', ' ='), '=', $element_selector);
			
			$selector = $element_selector;
			
			/**
			  * Regex to split css expressions by spaces, ignoring spaces within brackets, even nested.
			  * Old Expression: [ ](?=[^\]\)"\']*?(?:\[\(|$))
			  * Fails with the combiniation of words + brackets
			  *
			  * New Expression: \[[^]]*\](*SKIP)(*F)|\s+
			  * Thanks to http://stackoverflow.com/questions/1209415/regex-to-remove-all-whitespaces-except-between-brackets - zx81
			  * Works for square brackets '[]' - even nested
			  * Obtained 27-07-16
			  * 
			  * Now modified to also support parenthesis '()'
			  * Modified 27-07-16
			  *
			  */
			$match_spaces_between_whole_element_selector = '/[\(|\[][^]]*[\]|\)](*SKIP)(*F)|\s+/i';
			// Split
			$whole_element_selectors = preg_split($match_spaces_between_whole_element_selector, $selector);
				
			for($i = 0; $i < count($whole_element_selectors); $i ++)
			{
				$selector = $whole_element_selectors[$i];
				
				$psuedo_modifier = null;
				// Capture the last evaluated item in $whole_element_selectors... we'll test if it was a relationship_modifier modifying the current item.
				$relationship_modifier = isset($whole_element_selectors[$i - 1]) ? $whole_element_selectors[$i - 1] : null;
				
				// If this current item is a relationship_modifier, ignore. Next item evaluation will handle this as previous item.
				if (!in_array($selector, array('~', '+', '>')))
				{
					# 1. Work with :psuedo_modifiers
					if (strpos($selector, ':') !== false)
					{
						// This regex may be needed later: get everything between the first and last parenthesis; whether nested or not.
						// (?<=\().*(?=\))
						$selector_and_psuedo = explode(':', $selector);
						$psuedo_modifier = isset($selector_and_psuedo[1]) ? $selector_and_psuedo[1] : null;
						// $selector changes from now on
						$selector = $selector_and_psuedo[0];
						
						if (strpos($psuedo_modifier, '(') !== false)
						{
							$sub_selector = substr($psuedo_modifier, strpos($psuedo_modifier, '(') + 1, -1);
							// $psuedo_modifier changes from now on
							$psuedo_modifier = substr($psuedo_modifier, 0, strpos($psuedo_modifier, '('));
						}
						
						if (!empty($psuedo_modifier))
						{
							if ($psuedo_modifier == 'first-child')
							{
								// Get first node (relative to parent - not relative to result set, as in first-of-type()),
								// test that it is self of this selector
								$selector = '*[1]/self::'.$this->getElementsBySelector($selector, null, false);
							}
							elseif ($psuedo_modifier == 'last-child')
							{
								// Get last node (relative to parent - not relative to result set, as in last-of-type()),
								// test that it is self of this selector
								$selector = '*[last()]/self::'.$this->getElementsBySelector($selector, null, false);
							}
							elseif ($psuedo_modifier == 'nth-child')
							{
								// Get nth node (relative to parent - not relative to result set, as in nth-of-type()),
								// test that it is self of this selector
								$selector = '*['.(int)$sub_selector.']/self::'.$this->getElementsBySelector($selector, null, false);
							}
							
							elseif ($psuedo_modifier == 'first-of-type')
							{
								$selector = $this->rewrite($selector).'[1]';
							}
							elseif ($psuedo_modifier == 'last-of-type')
							{
								$selector = $this->rewrite($selector).'[last()]';
							}
							elseif ($psuedo_modifier == 'nth-of-type')
							{
								$selector = $this->rewrite($selector).'['.(int)$sub_selector.']';
							}
							elseif ($psuedo_modifier == 'not')
							{
								$sub_selector = $this->getElementsBySelector($sub_selector, null, false);
								
								// Sanitize...
								// If it's something like *[...] now going to be [:not(*[...])] - not what we want, let strip off the beginning *
								/*if (substr($sub_selector, 0, 1) == '*')
								{
									$sub_selector = 'self::*'.substr($sub_selector, 1);
								}*/
								
								// Don't Sanitize (27-07-16)
								$sub_selector = 'self::'.$sub_selector;
								
								$selector = $this->rewrite($selector).'[not('.$sub_selector.')]';
							}
							elseif ($psuedo_modifier == 'empty')
							{
								$selector = $this->rewrite($selector).'[count(*)=0 and not(text())]';
								//$selector = $this->rewrite($selector).'[count(*)=0 or not(text())]';
							}
							/*else
							{
								// $psuedo_modifier must have been some unrecognized modifier erronously provided by user, like :before or :after.
								// Whatever the case... discard $psuedo_modifier and proceed to rewrite $selector.
								$selector = $this->rewrite($selector);
							}
							CSS parsers don't tolerate such malformed selectors*/
						}
					}
					else
					{
						$selector = $this->rewrite($selector);
					}
					
					# 2. If no tagname is present, use a wildcard *. So we get *[...].
					if (substr($selector, 0, 1) === '[')
					{
						$selector = '*'.$selector;
					}
					
					# 3. Work with relationship_modifiers
					
					// Parent > Direct Child
					if ($relationship_modifier === '>')
					{
						$selector_build .= '/'.$selector;
					}
					// Element + immediate sibling
					elseif ($relationship_modifier === '+')
					{
						$selector_build .= '/following-sibling::'.$selector.'[1]';
					}
					// Element ~ any sibling
					elseif ($relationship_modifier === '~')
					{
						$selector_build .= '/following-sibling::'.$selector;
						//$selector_build .= '/../'.$selector;
					}
					else
					{
						if (!empty($selector_build))
						{
							// If previous evaluated $selector (prev_selector [tested as $modifier]) was an actual selector and not a modifier, there should be double slash before this current selector (cur_selector)
							// prev_selector//cur_selector i.e all elements that are descendants of prev_selector
							$selector_build .= '//'.$selector;
						}
						else
						{
							// If it's not subquery - e.g queries enclosed in ::not()...
							// Return as is!
							$selector_build .= $selector;
						}
					}
				}
			}
			
			if (!$evaluate_query)
			{
				return $selector_build;
			}
		}
		
		$query_string = '>> '.$element_path.'/'.$selector_build;
		
		$selector_build = $selector_build."[not(@data-phpfront-no_parse)]";
		
		$elements = $this->xpath->query($element_path.'/'.$selector_build);
		
		if ($elements)
		{
			// Save to list
			$query_string .= ' -> ('.$elements->length.')';
			$this->queries[] = $query_string;
			
			return $elements;
		}
		else
		{
			throw new Exception('Malformed XPath query: '.$element_path.'/'.$selector_build.' !');
		}
    }
	
	
		
	/*
	 *-----------------------
	 * PRIVATE WORKING METHODS
	 *-----------------------
	*/
	
	
	
	/**
     * Rewrite a unit of css selector into a valid XPath query.
	 *
     * @param string	$css_selector		A css selector to be translated into xpath.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	string			XPath query string.
     */
	public function rewrite($css_selector)
	{
		// Simple Attribute First Before introducing complex brackets
		if (strpos($css_selector, '[') !== false && strpos($css_selector, ']') !== false && strpos($css_selector, '=') === false)
		{
			$css_selector = str_replace('[', '[@', $css_selector);
		}
		// ID selector
		if (strpos($css_selector, '#') !== false)
		{
			// Attr Is Word
			//$css = '/\#(\w+)/i'; // The (\w+) fails on dash-separated-words
			$css = '/\#(.*)/i';
			$xpath = '[@id="$1"]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Class
		if (strpos($css_selector, '.') !== false)
		{
			// Attr Contains Word
			//$css = '/\.(\w+)/i'; // The (\w+) fails on dash-separated-words
			$css = '/\.(.*)/i';
			$xpath = '[contains(concat(" ", @class, " "), " $1 ")]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
			/// Consider doing normalize-space(@class)
		}
		// Complex Attribute
		if (strpos($css_selector, '[') !== false && strpos($css_selector, ']') !== false && strpos($css_selector, '=') !== false)
		{
			// Attr Starts With
			if (strpos($css_selector, '^=') !== false)
			{
				//$css = '/\[(.*)\^=[\'"]?([^\'"]+)[\'"]?\]/i';
				$css = '/\[@?([^\^]*)\^=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat(" ", @$1), " $2")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Ends With
			if (strpos($css_selector, '$=') !== false)
			{
				//$css = '/\[(.*)\$=[\'"]?([^\'"]+)[\'"]?\]/i';
				$css = '/\[@?([^\&]*)\$=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat(@$1, " "), "$2 ")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Contains String
			if (strpos($css_selector, '*=') !== false)
			{
				//$css = '/\[(.*)\*=(.*)\]/i';
				$css = '/\[@?([^\*]*)\*=(.*)\]/i';
				$xpath = '[contains(@$1, $2)]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Has Dash-delimited Word
			if (strpos($css_selector, '|=') !== false)
			{
				//$css = '/\[(.*)\|=[\'"]?([^\'"]+)[\'"]?\]/i';
				$css = '/\[@?([^\|]*)\|=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat("-", @$1, "-"), "-$2-")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Contains Word
			if (strpos($css_selector, '~=') !== false)
			{
				//$css = '/\[(.*)\~=[\'"]?([^\'"]+)[\'"]?\]/i';
				$css = '/\[@?([^\~]*)\~=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat(" ", @$1, " "), " $2 ")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Is Word
			if (strpos($css_selector, '=') !== false)
			{
				//$css = '/\[(.*)=[\'"]?([^\'"]+)[\'"]?\]/i';
				
				// 27-07-16:
				// Start with [.
				// Ignore any @ following [. e.g [@
				// Begin finding characters that are not =.
				// Stop at = if available. Or just stop if that's what ends it.
				$css = '/\[@?([^=]*)=?[\'"]?([^\'"]+)[\'"]?\]/i';
				
				$xpath = '[@$1="$2"]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
		}
		
		return $css_selector;
	}
	
	
	/**
     * Returns all XPath queries ever run on the DOM. This includes CSS selectors that were converted to XPath.
     *
     * @return array
     */
	public function getQueries()
	{
		return implode("\r\n", $this->queries);
	}
	
	
	/**
     * Duplicate an element in the main document. Mainly used to complete calls_list
     *
     * @param DOMNode 	$node 						A DOMNode element.
	 * @param string 	$insert_duplicate_as 		Specifies how to place the duplicated element relative to its source.
	 * @param bool 		$insert_before 				The numeric key location of an item in list before which to add this newly duplicated $node.
     * 
	 * @return void
     */
	public function duplicateNode($node, $insert_duplicate_as = 'immediate_sibling', $insert_before = false)
	{
		$parent = $node->parentNode;
		if ($insert_duplicate_as == 'sub_child')
		{
			$parent = $node;
		}
		elseif ($insert_before === false/*DON'T RUN THIS BLOCK EVEN WITH NULL... NULL MEANS THERE WAS AN ATTEPT TO PROVIDE THE RIGHT $insert_before*/ && !empty($node->nextSibling))
		{
			$insert_before = $node->nextSibling;
		}
		
		// Copy and insert the main node
		$node_copy = $node->cloneNode(true);
		
		if ($insert_before)
		{
			// Insert $node_copy
			$parent->insertBefore($node_copy, $insert_before);
		}
		else
		{
			// Insert $node_copy as last child
			$parent->appendChild($node_copy);
		}
		
		// See if we can first add a contextual line break or space. Not always the case anyways
		if ($this->formatOutput && is_object($parent->firstChild) && $parent->firstChild->nodeName == '#text' && is_null(trim($parent->firstChild->nodeValue)))
		{
			$white_space = $parent->firstChild->cloneNode(true);
			$parent->insertBefore($white_space, $node_copy);
		}
		
		return $node_copy;
	}
	
	
	
	/**
     * Fill PHPFrontDom::$node_list with DOMNodeList objects. This is usually called by PHPFront.
     *
	 * @param array 	$element_selectors 	A list of element selectors provided by PHPFront.
     * @param DOMNode 	$source_element 	A DOMNode element from which to source elements for the population.
     * @param bool	 	$repeat_fn		 	The repeat function to use.
     * @param bool	 	$is_switch_to_self	Sets whether to populate data on element itself or child elements.
     * 
	 * @see				PHPFront::setElementData().
	 *
	 * @return void
     */
	public function createNodeListFromSelectors(array $element_selectors, $source_element = null, $repeat_fn = null, $switch_population_target = false)
	{
		$source_element = !empty($source_element) ? $source_element : $this;
		return new PHPFrontNodeList($element_selectors, $source_element, $repeat_fn, $switch_population_target);
	}
	
	
	
	
	public function saveHTML($element_selector = null)
	{
		if (!empty($element_selector))
		{
			if (is_string($element_selector))
			{
				$markup_strings = array();
				
				$elements = $this->getElementsBySelector($element_selector);
				if ($elements)
				{
					foreach($elements as $element)
					{
						$markup_strings[] = parent::saveHTML($element);
					}
				}
				
				return implode(($this->formatOutput ? "\r\n" : ""), $markup_strings);
			}
			
			return parent::saveHTML($element_selector);
		}
		
		return parent::saveHTML();
	}
}
