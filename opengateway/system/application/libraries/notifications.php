<?php

class Notifications {
	function QueueNotification ($url, $variables) {
		$insert = array(
						'notification_id' => '',
						'url' => $url,
						'variables' => serialize($variables)
				);
				
		$this->db->insert('notifications',$insert);
		
		return true;
	}
	
	function ProcessQueue () {
		$result = $this->db->get('notifications');
		
		foreach ($result->result_array() as $item) {
			$postfields = '';
			
			while (list($k,$v) = each($item['variables'])) {
				$postfields .= urlencode($k) . '=' . urlencode($v) . '&';
			}
			
			$postfields = rtrim($postfields, '&');
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt($ch, CURLOPT_URL,$item['url']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields); 
			curl_exec($ch); 
			curl_close($ch);
		}
	}
}