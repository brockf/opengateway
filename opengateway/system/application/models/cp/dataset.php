<?php

class Dataset extends Model {
	var $columns;
	var $base_url;
	var $filters;
	var $filter_values;
	var $rows_per_page;
	var $offset;

    function Dataset() {
        parent::Model();
        
        $this->rows_per_page = 50;
    }
    
    function Initialize ($data_model, $data_function, $columns, $base_url = false) {
    	$CI =& get_instance();
    	
    	$CI->load->library('asciihex');
    	
    	// prep columns
	    foreach ($columns as $column) {
	    	$this->columns[] = array(
	    					'name' => $column['name'],
	    					'sort_column' => isset($column['sort_column']) ? $column['sort_column'] : false,
	    					'width' => isset($column['width']) ? $column['width'] : false,
	    					'type' => isset($column['type']) ? $column['type'] : false,
	    					'filters' => (!isset($column['filter'])) ? false : true,
	    					'filter_name' => (!isset($column['filter'])) ? false : $column['filter'],
	    					'field_start_date' => isset($column['field_start_date']) ? $column['field_start_date'] : '',
	    					'field_end_date' => isset($column['field_end_date']) ? $column['field_end_date'] : '',
	    					'options' => isset($column['options']) ? $column['options'] : array(),
	    				);
	    			
	    	// error checking			
	    	if ($column['type'] == 'date' and (!isset($column['field_start_date']) or !isset($column['field_end_date']))) {
	    		die(show_error('Unable to create a "date" filter without field_start_date and field_end_date.'));
	    	}
	    	elseif ($column['type'] == 'select' and !isset($column['options'])) {
	    		die(show_error('Unable to create a "select" filter without options.'));
	    	}
	    	
	    	// so do we have filters?			
	    	if (isset($column['filter'])) {
	    		$this->filters = true;
	    	}	
    	}
    	reset($this->columns);
    	
    	$has_filters = ($CI->uri->segment(4) != '' or (strlen($CI->uri->segment(3)) > 10)) ? '1' : '0';
    	
    	// get filter values
		$this->filter_values = ($has_filters) ? unserialize(base64_decode($CI->asciihex->HexToAscii($CI->uri->segment(3)))) : false;
    	
    	// get data
    	$params = array();
    	
    	$params['limit'] = $this->rows_per_page;
    	$this->offset = ($has_filters) ? $CI->uri->segment(4) : $CI->uri->segment(3);
    	$this->offset = (empty($this->offset)) ? '0' : $this->offset;
    	
    	$params['offset'] = $this->offset;
    	
    	if ($this->filters == true) {
    		foreach ($this->columns as $column) {
    			if ($column['filters'] == true) {
    				if (($column['type'] == 'select' or $column['type'] == 'text' or $column['type'] == 'id') and isset($this->filter_values[$column['filter_name']])) {
    					$filter_params[$column['filter_name']] = $this->filter_values[$column['filter_name']];
    				}
    				elseif ($column['type'] == 'date' and (isset($this->filter_values[$column['filter_name'] . '_start']) or isset($this->filter_values[$column['filter_name'] . '_end']))) {
    					$filter_params[$column['field_start_date']] = (empty($this->filter_values[$column['filter_name'] . '_start'])) ? '2009-01-01' : $this->filter_values[$column['filter_name'] . '_start'];
    					$filter_params[$column['field_end_date']] = (empty($this->filter_values[$column['filter_name'] . '_end'])) ? '2020-12-31' : $this->filter_values[$column['filter_name'] . '_end'];
    				}
    			}
    		}
    		reset($this->columns);
    	}
    	
    	$params = (!empty($filter_params)) ? array_merge($params, $filter_params) : $params;
    	
    	// do an XML export?
    	if ($CI->uri->segment(5) == 'export') {
    		$xml_params = '';
			while (list($name,$value) = each($params)) {
				if ($name != 'limit' and $name !='offset') {
					$xml_params .= "<$name>$value</$name>";
				}
			}
			reset($params);
    	
			$postfields = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>' . $CI->user->Get('api_id') . '</api_id>
		<secret_key>' . $CI->user->Get('secret_key') . '</secret_key>
	</authentication>
	<type>' . $data_function . '</type>
' . $xml_params . '
</request>';
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt($ch, CURLOPT_URL,base_url() . 'api');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			
			$result = curl_exec($ch);
			curl_close($ch);
			
			header("Content-type: text/xml");
			header("Content-length: " . strlen($result));
			header('Content-Disposition: attachment;filename="export.xml"');
			echo $result;
			die();
    	}
    
    	$CI->load->model($data_model,'data_model');
    	$this->data = $CI->data_model->$data_function($CI->user->Get('client_id'),$params);
    	
    	unset($params);
    	$params = array();
    	
		$params = (!empty($filter_params)) ? array_merge($params, $filter_params) : $params;
    	
    	$total_rows = count($CI->data_model->$data_function($CI->user->Get('client_id'),$params));
    	
    	$this->total_rows = $total_rows;
    	
    	// set $url_filters if they exist
    	$url_filters = (!empty($this->filter_values)) ? '/' . $CI->asciihex->AsciiToHex(base64_encode(serialize($this->filter_values))) . '/' : '';
		$this->base_url = ($base_url == false) ? site_url($CI->router->fetch_class() . '/' . $CI->router->fetch_method() . $url_filters) : $base_url;
		
		$config['base_url'] = $this->base_url;
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $this->rows_per_page;
		$config['uri_segment'] = ($has_filters) ? 4 : 3;
		$config['num_links'] = '10';
		
		$CI->load->library('pagination');
		$CI->pagination->initialize($config); 
    }
    
    function TableHead () {
    	$CI =& get_instance();
    	
    	$output = '';
    	
    	$output .= '<form id="dataset_form" method="get" action="' . $this->base_url . '">
    				<div class="pagination">';
    	$output .= $CI->pagination->create_links();
    	
    	if ($this->filters == true) {
    		$output .= '<div class="dataset_export"><input id="dataset_export_button" type="button" name="" value="Export" /></div><div class="apply_filters"><input type="submit" name="" value="Filter Dataset" />&nbsp;&nbsp;<input id="reset_filters" type="reset" name="" value="Clear Filters" /></div>';
    	}
    	
    	$output .= '</div>
    				<table class="dataset" cellpadding="0" cellspacing="0">
    				<thead><tr>';
    	
    	while (list(,$column) = each($this->columns)) {
    		$output .= '<td style="width:' . $column['width'] . '">' . $column['name'] . '</td>';
    	}
    	reset($this->columns);
    	
    	$output .= '</tr></thead><tbody>';
    	
    	if ($this->filters == true) {
    		$output .= '<tr class="filters">';
    		
    		while (list(,$column) = each($this->columns)) {
				if ($column['filters'] == true) {
					$output .= '<td class="filter">';
					
					if ($column['type'] == 'text') {
						$value = (isset($this->filter_values[$column['filter_name']])) ? $this->filter_values[$column['filter_name']] : '';
						$output .= '<input type="text" class="text" name="' . $column['filter_name'] . '" value="' . $value . '" />';
					}
					elseif ($column['type'] == 'id') {
						$value = (isset($this->filter_values[$column['filter_name']])) ? $this->filter_values[$column['filter_name']] : '';
						$output .= '<input type="text" class="text id" name="' . $column['filter_name'] . '" value="' . $value . '" />';
					}
					elseif ($column['type'] == 'date') {
						$value = (isset($this->filter_values[$column['filter_name'] . '_start'])) ? $this->filter_values[$column['filter_name'] . '_start'] : '';
						$output .= '<input type="text" class="date_start datepick" name="' . $column['filter_name'] . '_start" value="' . $value . '" />';
						
						$value = (isset($this->filter_values[$column['filter_name'] . '_end'])) ? $this->filter_values[$column['filter_name'] . '_end'] : '';
						$output .= '<input type="text" class="date_end datepick" name="' . $column['filter_name'] . '_end" value="' . $value . '" />';
					}
					elseif ($column['type'] == 'select') {
						$output .= '<select name="' . $column['filter_name'] . '"><option value=""></option>';
						
						while (list($value,$name) = each($column['options'])) {
							$selected = (isset($this->filter_values[$column['filter_name']]) and $this->filter_values[$column['filter_name']] == $value) ? ' selected="selected"' : '';
							$output .= '<option value="' . $value . '"' . $selected . '>' . $name . '</option>';
						}
						
						$output .= '</select>';
					}
					
					$output .= '</td>';
				}
				else {
					$output .= '<td></td>';
				}
    		}
    		
    		$output .= '</tr>';
    	}
    	
    	return $output;
    }
    
    function TableClose () {
    	$CI =& get_instance();
    	
    	$output = '</table>';
    	
    	$output .= '<div class="pagination">';
    	$output .= $CI->pagination->create_links();
    	$output .= '</div></form>
			    	<div class="hidden" id="class">' . $CI->router->fetch_class() . '</div>
					<div class="hidden" id="method">' . $CI->router->fetch_method() . '</div>
					<div class="hidden" id="page">' . $this->offset . '</div>';
    	
    	return $output;
    }
}