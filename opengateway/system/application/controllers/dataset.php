<?php

class Dataset extends Controller {

	function Dataset()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();
	}
	
	function index()
	{	
		return $this->manage();	
	}