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
 * @uses		documentElement
 */	 



/** 
 * PHPFrontNodeList.
 * Smartly orders a list of DOMNode elements right on the main template,
 * Able to duplicate elements to render extra data items where data items outnumbers number of elements.
 */
 
 Class PHPFrontNodeList
{
	/**
     * Holds a list of DOMNode elements.
	 *
	 * A combination of elements from retrieved DOMNodeList on the container class PHPFront and any extra ones auto-generated
	 * to complete the idea number for the current data stack.
     *
     * @var array $nodeList
	 *
	 * @see createNodeListFromElement().
	 *
	 * @access private
     */
	private $nodeList = array();
	
	/**
     * An internal counter use by seek().
	 *
	 * Initialized to 0; increased each time seek() is called.
     *
     * @var int $cursor
	 *
	 * @see seek().
	 *
	 * @access private
     */
	private $cursor = null;
	
	/**
     * Holds list of element selectors provided by the container class PHPFront.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var array $calls_list
	 *
	 * @see createNodeListFromElement().
	 *
	 * @access private
     */
	private $calls_list = array();
	
	/**
     * Total count of the number of calls expected.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var int $num_node_calls_expected
	 *
	 * @see createNodeListFromElement().
	 *
	 * @access private
     */
	private $num_node_calls_expected = 0;
	
	/**
     * Total count of calls list provided.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var int $num_nodes_found
	 *
	 * @see createNodeListFromElement().
	 *
	 * @access private
     */
	private $num_nodes_found = 0;
	
	/**
     * Half of total count of calls list provided.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var int $half_num_nodes_found
	 *
	 * @see createNodeListFromElement().
	 *
	 * @access private
     */
	private $half_num_nodes_found;
	
	/**
     * The remainder after dividing total number of calls list provided with 2. That is, modulo 2 of count calls list.
	 *
	 * This will be an offset integer added to second half of the total count of calls list provided.
     *
     * @var int $half_num_nodes_found_offset
	 *
	 * @see createNodeListFromElement().
	 * 
     * This is used internally
	 *
	 * @access private
     */
	private $half_num_nodes_found_offset;

	/**
     * An instance of the class PHPFrontDom.
	 *
	 * We'll be accessing the DOM to duplicate elements on it.
     *
     * @var object $documentElement
	 *
	 * @see __constructor().
	 *
	 * @access private
     */
	public $documentElement;
	
	/**
     * A string name of the algarithm to use when ordering elements in list.
	 *
	 * This is obtained from the current state of PHPFront.
     *
     * @var string $repeat_fn
	 *
	 * @see __constructor().
	 *
	 * @access private
     */
	public $repeat_fn;

	
	
	/**
     * Fill PHPFrontNodeList::$nodeList with DOMNodeList objects or arrays of DOMNode objects. This function is usually called by PHPFront.
     *
	 * @param array 	$element_selectors 			A list of element selectors provided by PHPFront.
     * @param DOMNode 	$source_element 			A DOMNode element from which to source elements for the population.
     * @param bool	 	$repeat_fn		 			The repeat function to use.
     * @param bool	 	$self_repeat				Sets whether to fill up nodeList with repeatitions of $source_element itself or child elements.
     * 
	 * @see				PHPFront::setElementData().
	 *
	 * @return void
     */
	public function __construct(array $element_selectors, $source_element, $repeat_fn = null, $self_repeat = false)
	{
		// Initially available elements have been populated.
		if (!empty($repeat_fn))
		{
			// Add initial comma, remove all spaces
			$this->repeat_fn = ','.str_replace(' ', '', strtolower($repeat_fn)).',';
		}
		
		$this->self_repeat = $self_repeat;
														
		// Set these before any duplication begins... may be needed
		$this->element_selectors = $element_selectors;
		$this->ownerDocument = $source_element instanceof PHPFrontDom ? $source_element : $source_element->ownerDocument;
		
		if ($this->ownerDocument)
		{
			# 1. Attempt to populate nodeList
			// Default is to fill up nodeList with elements corresponding to items in $element_selectors - children of $source_element
			// But if $self_repeat is true, fill up nodeList with repeatitions of $source_element.
			
			if (!$this->self_repeat)
			{
				if (is_string($element_selectors[0]))
				{
					$source_element_node_path = $source_element instanceof PHPFrontDom ? '//' : $source_element->getNodePath();
					
					# A. We populate chidren of element
					for($i = 0; $i < count($element_selectors); $i ++)
					{
						# a. This element has specified which named children to populate
						# We're working with named children
						
						// Sanitize...
						$element_selectors[$i] = str_replace(array('::before', '::after'), '', $element_selectors[$i]);
						// Query...
						$elements = $this->ownerDocument->getElementsBySelector($element_selectors[$i], $source_element_node_path);
						
						// Add the result as is... whether empty or not. Seek() will fetch this value for this selector. Parser will ofcourse test non-emptinesss
						$this->addToNodeList($elements);
					}
				}
				else
				{
					# b. This element does not specify named children.
					# So we populate all children except SPACES and COMMENTS
					$children = $source_element->childNodes;
					foreach($children as $key => $node)
					{
						if(!($node instanceof DOMText || $node instanceof DOMComment))
						{
							$this->addToNodeList(array($node));
						}
					}
					
					# 2. IF the above didn't produce anything, that is, the provided element cannot produce a child,
					// duplicate it to get a child 
					if (empty($this->nodeList) && !empty($source_element))
					{
						$node = $this->ownerDocument->duplicateNode($source_element, 'sub_child');
						$this->addToNodeList(array($node));
					}
				}
				
				// In case number of items populated is smaller than expected number of calls, then we fill the list up
				$this->completeNodeList();
			}
			else
			{
				// Repeat self
				// This element does not want to populate its children.
				$this->addToNodeList(array($source_element));
				
				// In case number of items populated is smaller than expected number of calls, then we fill the list up
				$this->completeNodeList();
			}
		}
	}
	
	
		
		
	/**
     * Destructor for PHPFrontNodeList.
     *
     * Frees up all memory used. May not be neccessary.
     * 
	 * @return void
     */
	public function __destruct()
	{
        // Free memory associated with the nodeList
		unset($this->nodeList);
    }
	
	
	/**
     * Add a DOMNode item to the list.
     *
     * @param object 	$node 			A DOMNode element.
	 * @param bool 		$before_cursor 	The numeric key location of an item in list before which to add this.
     * 
	 * @return void
     */
	private function addToNodeList($node, $before_cursor = null)
	{
		if ($before_cursor && isset($this->nodeList[$before_cursor]))
		{
			$nodeList_second_half = array_splice($this->nodeList, $before_cursor);
			$this->nodeList[] = $node;
			$this->nodeList = array_merge($this->nodeList, $nodeList_second_half);
		}
		else
		{
			$this->nodeList[] = $node;
		}
	}
	
	
	
	/**
     * Completes PHPFrontNodeList::$nodeList with auto generated (duplicated) DOMNode elements.
	 *
	 * @return void
     */
	private function completeNodeList()
	{
		$this->num_node_calls_expected = count($this->element_selectors);
		
		$this->num_nodes_found = count($this->nodeList);
		$this->half_num_nodes_found = round($this->num_nodes_found / 2);
		$this->half_num_nodes_found_offset = ($this->half_num_nodes_found * 2 < $this->num_nodes_found ? 1 : ($this->half_num_nodes_found * 2 > $this->num_nodes_found ? -1 : 0));
		
		if ($this->num_nodes_found < $this->num_node_calls_expected && !empty($this->repeat_fn))
		{
			$cursor = null;
			$next_cursor_move = null;
			
			for($current_node_key = $this->num_nodes_found; $current_node_key < $this->num_node_calls_expected; $current_node_key ++)
			{
				// The default
				$node_list_relative_placement = 'after-self'; // After self
				
				// --------------------------------------------------------
				// Handle repeat functions
				// --------------------------------------------------------
				
				if (strpos($this->repeat_fn, ",repeat-all,") !== false)
				{
					// The cursor movement
					// Increment or decrement the cursor
					if ($next_cursor_move == "+" && $cursor < $this->num_nodes_found)
					{
						// Cursor can well increment to exactly total $this->num_nodes_found which will be a node key that is beyond range
						// But this helps indicate end of $this->nodeList, which the next section handles with a repeat
						$cursor ++;
					}
	
					if ($next_cursor_move == "-" && $cursor > -1)
					{
						// Cursor may well decrement to exactly 0, which is the minimum valid node key
						// But this '0' condition will be detected on the next call to seek and a repeat will begin
						$cursor --;
					}
					
					// ---------------------
					// Handle the two $cursor extreme states: too high, too low
					// ---------------------
					
					// 1. When the highest end of nodeList is reached. Or $cursor is yet to be initialized
					// Use the repeat function defined Or if this is first run where $cursor is null
					if ($cursor === null || (int)$cursor === (int)$this->num_nodes_found)
					{
						// Repeat from end back to begining
						if (strpos($this->repeat_fn, ",alternate,") !== false)
						{
							// 1. Everytime nodelist end is reached and the 'once' flag is not set
							// 2. Or when the 'once' flag is set, begins from the very point where repeating backwards will just satisfy the total num_node_calls_expected.
							if ((strpos($this->repeat_fn, ",once,") === false)
							|| (strpos($this->repeat_fn, ",once,") !== false && $current_node_key + $this->num_nodes_found >= $this->num_node_calls_expected))
							{
								$cursor = $this->num_nodes_found - 1;
								$next_cursor_move = "-";
							} 
						}
						// Simply repeat from begining
						else//if (in_array("simple", $this->repeat_fn))
						{
							// 1. Everytime nodelist end is reached and the 'once' flag is not set
							// 2. Or when the 'once' flag is set, begins from the very point where repeating backwards will just satisfy the total num_node_calls_expected.
							if ((strpos($this->repeat_fn, ",once,") === false)
							|| (strpos($this->repeat_fn, ",once,") !== false && $current_node_key + $this->num_nodes_found >= $this->num_node_calls_expected))
							{
								$cursor = 0;
								$next_cursor_move = "+";
							}
						}
						
						// If $cursor is still null, it means none of the two conditions above has been met
						// Initialize $cursor
						if ($cursor === null)
						{
							$cursor = $this->num_nodes_found - 1;
							$next_cursor_move = "+";
						}
					}
					// 2. When the lowest end of nodeList is reached.
					elseif ($cursor === -1)
					{
						$cursor = 0;
						$next_cursor_move = "+";
					}
					
					// Last item in NodeList
					$node_list_relative_placement = 'last-item';
				}
				elseif (strpos($this->repeat_fn, ",repeat-middle,") !== false || (strpos($this->repeat_fn, ",repeat-nth(") !== false && $nth = substr($this->repeat_fn, strpos($this->repeat_fn, '('), strpos($this->repeat_fn, '),'))/*assignment*/ && is_numeric($nth) ))
				{
					if (strpos($this->repeat_fn, ",repeat-middle,") !== false)
					{
						$middle_point = $this->half_num_nodes_found + $this->half_num_nodes_found_offset;
						
						// Inner_padded must always use the middle element for padding
						// If inner_padded is set to use the LEFT next element for padding can only work when $this->num_nodes_found must be EVEN.
						if (in_array("left", $this->repeat_fn) && $this->num_nodes_found % 2 == 0 && $middle_point > 0)
						{
							$middle_point --;
						}
						
						$cursor = $middle_point;
					}
					else
					{
						$cursor = (int)$nth - 1; // Its not zero-based
					}
				}
				elseif (strpos($this->repeat_fn, ",repeat-first,") !== false)
				{
					$cursor = 0;
					//$next_cursor_move = "+";
				}
				else//if (strpos($this->repeat_fn, ",repeat-last,") !== false)
				{
					$cursor = $this->num_nodes_found - 1;
					//$next_cursor_move = "+";
				}
				
				
				
				// --------------------------------------------------------
				// Compute placement and duplicate
				// --------------------------------------------------------
				
				// The node to be duplicated
				$node = $this->nodeList[$cursor][0]/*Has been a single node in array*/;
				
				// Where to place it
				if ($node_list_relative_placement === 'before-self')
				{
					$nodelist_before_cursor = $cursor;
					$dom_insert_before = $node;
				}
				elseif ($node_list_relative_placement === 'after-self')
				{
					$nodelist_before_cursor = $cursor + 1;
					$dom_insert_before = is_object($this->nodeList[$cursor][0]) ? $this->nodeList[$cursor][0]->nextSibling : null;
				}
				else//if ($node_list_relative_placement === 'last-item')
				{
					$nodelist_before_cursor = null;
					$key_last = count($this->nodeList) - 1;
					$dom_insert_before = is_object($this->nodeList[$key_last]) ? $this->nodeList[$key_last]->nextSibling : null;
				}
				
				$duplicate = $this->ownerDocument->duplicateNode($node, 'immediate_sibling', $dom_insert_before);
				$this->addToNodeList(array($duplicate), $nodelist_before_cursor);
			}
		}
		
		// Shuffle
		if (strpos($this->repeat_fn, ",shuffle-data,") !== false)
		{
			shuffle($this->nodeList);
		}
	}		
		
		
		
		
	/**
     * Get the next item in PHPFrontNodeList::$nodeList
     * 
	 * @return array|DOMNodeList
     */
	public function seek()
	{
		// Must be type comparison since 0 could be also be regarded null
		if ($this->cursor === null)
		{
			$this->cursor = -1;
		}
		
		$this->cursor ++;

		return isset($this->nodeList[$this->cursor]) ? $this->nodeList[$this->cursor] : null;
   }
}
