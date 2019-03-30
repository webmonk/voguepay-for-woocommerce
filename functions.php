<?php
if(!function_exists('file_get_contents_curl_voguepay')):
function file_get_contents_curl_voguepay($url, $fh = null, $fields = null)
{
	if ( function_exists('curl_init') )
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $url);
		if( $fields )
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		}
		$data = curl_exec($ch);
		/*
		if ($data === FALSE) {
			$data =  "cURL Error: " . curl_error($ch);
		}
		*/
		curl_close($ch);
	} else {
		$data = false;
	}
	return $data;
}
endif;