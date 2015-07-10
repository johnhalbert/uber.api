<?php


	session_destroy();
	//require_once('new-connection.php');

	// Get bearer token from Uber

	function get_token($param) {
		$token = curl_init();
		$postData = '';
		foreach($param as $key => $value)
			{
			   $postData .= $key . '='.urlencode($value).'&';
			}
		$postData = rtrim($postData, '&');
		curl_setopt($token, CURLOPT_URL, 'https://login.uber.com/oauth/token');
		curl_setopt($token, CURLOPT_HEADER, false);
		curl_setopt($token, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($token, CURLOPT_POST, true);
		curl_setopt($token, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($token, CURLOPT_HTTPHEADER, array('Content-Type=x-www-form-urlencoded'));
		$returned_token = curl_exec($token);
		return json_decode($returned_token, true);
	}

	// Use server token to access basic enpoints

	function use_server_token($version, $endpoint, $server_token, $param) {
		$servertoken = curl_init();
		if ($endpoint == 'products/') {
			curl_setopt($servertoken, CURLOPT_URL, 'https://sandbox-api.uber.com/'.$version.'/'.$endpoint.$param);
		} else {
			$getData = '';
			foreach($param as $k => $v)
				{
				   $getData .= $k . '='.urlencode($v).'&';
				}
			$getData = rtrim($getData, '&');
			curl_setopt($servertoken, CURLOPT_URL, 'https://sandbox-api.uber.com/'.$version.'/'.$endpoint.'?'.$getData);
		}
		curl_setopt($servertoken, CURLOPT_HEADER, false);
		curl_setopt($servertoken, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($servertoken, CURLOPT_HTTPHEADER, array("Authorization: Token ".$server_token)); // This is your server_token
		$returned_data = curl_exec($servertoken);
		return json_decode($returned_data, true);
	}

	// Use bearer token to access get endpoints

	function use_bearer_get($version, $endpoint, $token, $parameters = null) {
		$bearer = curl_init();
		if ($parameters == null) {
			curl_setopt($bearer, CURLOPT_URL, 'https://api.uber.com/'.$version.'/'.$endpoint);
		} else {
			$parsedParamters = '';
			foreach ($parameters as $key => $value) {
				{
				   $parsedParamters .= $k . '='.urlencode($v).'&';
				}
			}
			curl_setopt($bearer, CURLOPT_URL, 'https://api.uber.com/'.$version.'/'.$endpoint.'?'.$parsedParamters);
		}
		curl_setopt($bearer, CURLOPT_HEADER, false);
		curl_setopt($bearer, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($bearer, CURLOPT_POST, false);
		curl_setopt($bearer, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
		$returned_data = curl_exec($bearer);
		return json_decode($returned_data, true);
	}

	// Use sandbox to access production endpoints without creating actual requests.

	function use_bearer_sandbox($version, $endpoint, $parameters, $token, $product_id = false) {
		$bearer = curl_init();
		$parameters = json_encode($parameters);
		if ($product_id == true) {
			curl_setopt($bearer, CURLOPT_URL, 'https://sandbox-api.uber.com/'.$version.'/sandbox/'.$endpoint.'/{request_id: 53453453453}');
			curl_setopt($bearer, CURLOPT_HEADER, false);
		} else {
			curl_setopt($bearer, CURLOPT_URL, 'https://sandbox-api.uber.com/'.$version.'/sandbox/'.$endpoint);
			curl_setopt($bearer, CURLOPT_HEADER, true);
		}
		curl_setopt($bearer, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($bearer, CURLOPT_POST, true);
		curl_setopt($bearer, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($bearer, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($bearer, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer $token"));
		$returned_data = curl_exec($bearer);
		return json_decode($returned_data, true);
	}

	/**********************************
	  IMPORTANT!
	  Be sure that you're going through each of these functions and replacing the client_id, client_secret and server_token with your own,
	  taken from your account and configured in your Uber acccount with the appropriate endpoint!
	  Place this script at the endpoint you specified with Uber.
	*/

	// This will return an 'access_token' which we store in the database.

	if (isset($_GET['code'])) {
		$token = get_token(array('client_secret' => 'ASZAFzKz-GMCKty5SI3uklvcKween2svRO4_9vuy', 'client_id' => 'vF4UAYd4NszROsfq9AqNdSj1UfzsRRTb', 'grant_type' => 'authorization_code', 'redirect_uri' => 'http://localhost:8888/Uber/courseway/authorize.php?', 'code' => "{$_GET['code']}"));
		if ($token) {
			$query = "INSERT INTO schools (token) VALUES ($token)";
			run_mysql_query($query);
		}
	}




	// This can be used with endpoints: products, estimates/price, estimates/time, promotions - EX (input your server_key in appropriate place and uncomment.  Be aware of the parameters required by the endpoint you're using, each are different) - EX:
	// use_server_token('v1', 'estimates/price', array('start_latitude' => '37.386358', 'start_longitude' => '-121.927910', 'end_latitude' => '37.377536', 'end_longitude' => '-121.911915'));

	// use_bearer_get - can be used with endpoints: history, me, requests/{request_id}, requests/{request_id}/map, requests/{request_id}/receipt, requires a valid access_token retrieved with get_token.
	// use_bearer_get('v1.2', 'history');

	// var_dump($_SESSION['parsedData']);
	// var_dump($_SESSION['response']);
	// var_dump($_SESSION['history']);

	/* use_bearer_sandbox: endpoint not acting as expected. Cannot get API to return a request_id at the requests endpoint as expected.
	404 not found.  When supplying my own request_id the server responds with internal_server error.  When passing start lat/long end lat/long,
	product_id, server responds that those values are not allowed, only {status: value} is acceptable, which produces an internal server error.


	use_bearer_sandbox('v1', 'requests', array('product_id' => 'a1111c8c-c720-46c3-8534-2fcdd730040d', 'start_latitude' => '37.386358', 'start_longitude' => '-121.927910', 'end_latitude' => '37.377536', 'end_longitude' => '-121.911915' ));
	use_bearer_sandbox('v1', 'requests', array('product_id' => '', 'start_latitude' => '', 'start_longitude' => '', 'end_latitude' => '', ''));
	use_bearer_sandbox('v1', 'requests', array('status' => 'completed'), true);
	*/

	// use_bearer_sandbox('v1', 'requests', array('product_id' => 'a1111c8c-c720-46c3-8534-2fcdd730040d', 'start_latitude' => '37.386358', 'start_longitude' => '-121.927910', 'end_latitude' => '37.377536', 'end_longitude' => '-121.911915' ));
	// use_bearer_sandbox('v1', 'requests', array('status' => 'completed'), true);
	// var_dump($_SESSION);

?>
