<?php

class Navigation extends Model {
	var $elements;
	var $children;
	var $pagetitle;

    function Navigation() {
        parent::Model();
    }
    
    function Add ($link, $name, $parent = false, $external = false) {
    	if ($parent == false) {
	    	$this->elements[] = array(
	    						'link' => $link,
	    						'name' => $name,
	    						'external' => $external
	    				);
	    }
	    else {
	    	$this->children[$parent][] = array(
	    						'link' => $link,
	    						'name' => $name,
	    						'external' => $external
	    				);	
	    }
    }
    
    function Output () {
    	$CI =& get_instance();
    	
    	$return = '';
    	
    	while (list(,$link) = each($this->elements)) {
    		$classes = array();
    		if (strstr($CI->router->fetch_class() . '/',$link['link'])) {
    			$classes[] = 'active';
    		}
    		
    		if (isset($this->children[$link['link']])) {
    			$classes[] = 'parent';
    		}
    		
    		$displaylink = ($link['external'] == false) ? site_url($link['link']) : $link['link'];
    		
    		$return .= '<li ' . $this->ArrayToClass($classes) . '><a href="' . $displaylink . '">' . $link['name'] . '</a>';
    		
    		if (isset($this->children[$link['link']])) {
    			$return .= '<ul class="children">';
    			
    			while (list(,$child) = each($this->children[$link['link']])) {
    				$displaylink = ($child['external'] == false) ? site_url($child['link']) : $child['link'];
    				$return .= '<li><a href="' . $displaylink . '">' . $child['name'] . '</a></li>';
    			}
    			
    			$return .= '<div style="clear:both"></div></ul>';
    		}
    		
    		$return .= '</li>';
    	}
    	
    	return $return;
    }
    
    function PageTitle ($set = false) {
    	if (!$set) {
    		return $this->pagetitle;
    	}
    	else {
    		$this->pagetitle = $set . ' | Control Panel';
    	}
    }
    
    function ArrayToClass ($array) {
    	$classes = implode(' ',$array);
    	
    	if (!empty($classes)) {
    		return 'class="' . $classes . '"';
    	}
    }
}