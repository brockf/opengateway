<?php

class Notifications {
	function QueueNotification ($url, $variables) {
		$CI =& get_instance();
		
		$insert = array(
						'notification_id' => null,
						'url' => $url,
						'variables' => serialize($variables)
				);
				
		$CI->db->insert('notifications',$insert);
		
		return true;
	}
	
	function ProcessQueue () {
		$CI =& get_instance();
		
		$queue_limit = ($CI->config->item('queue_process_limit')) ? $CI->config->item('queue_process_limit') : 20;
		
		$CI->db->limit($queue_limit);
		$result = $CI->db->get('notifications');
		
		$count = 0;
		foreach ($result->result_array() as $item) {
			$postfields = '';
			
			$item['variables'] = unserialize($item['variables']);
			
			while (list($k,$v) = each($item['variables'])) {
				$postfields .= urlencode($k) . '=' . urlencode($v) . '&';
			}
			
			$postfields = rtrim($postfields, '&');
			
			// are we sending this to ExpressionEngine? If so, let's convert to a GET request...
			// this is so Membrr and EE Donations continue to work with OG after EE 2.7
			// made all POST requests require XID's...
			if (strpos($item['url'], 'ACT=') !== FALSE) {
				$is_ee = TRUE;
			}
			else {
				$is_ee = FALSE;
			}
			
			if ($is_ee == TRUE) {
				$item['url'] = $item['url'] . '&' . $postfields;
			}
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt($ch, CURLOPT_URL,$item['url']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			
			if ($is_ee == FALSE) {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields); 
			}
			
			curl_exec($ch); 
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if ($http_code >= 200 or $http_code < 300) {
				$CI->db->where('notification_id',$item['notification_id']);
				$CI->db->delete('notifications');
			}
			
			$count++;
		}
		
		return $count;
	}
}