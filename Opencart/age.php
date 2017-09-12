<?php
class ModelAccountAge extends Model {

	private $url = 'https://production.idresponse.com/process/5/gateway';
	
	public function verify($customer_id,$zone_id) {

		$this->load->model('localisation/zone');
		$this->load->model('account/customer');

		$zones_limit = $this->config->get('config_age_verify_limit');

		$ch = curl_init($this->url);

		$jsonData = array(
			'user' => '####',
			'pass' => '####',
			'reference' => $customer_id,
			'service'=>'AgeMatch5.0',
			'target'=>array(
				"fn"=> $this->request->post['firstname'],
				"ln"=> $this->request->post['lastname'],
				"addr"=> $this->request->post['address_1'],
				"city"=> $this->request->post['city'],
				"state"=> $this->model_localisation_zone->getZone($this->request->post['zone_id'])['code'],
				"zip"=> $this->request->post['postcode'],
				"dob"=>  str_replace('/', '', $this->request->post['dob'] ),
				"dob_type"=> "MMDDYYYY",
				"age"=> $zones_limit[$zone_id]."+"
				),
			);

		//Encode the array into JSON.
		$jsonDataEncoded = json_encode($jsonData);

		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);

		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

		//Set the content type to application/json
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//Execute the request
		$result = curl_exec($ch);

		curl_close($ch);

		$response = json_decode($result, true);

		//debug($response);

		if(isset($response['result'])){

			if($response['result']['action'] == 'FAIL'){
				$this->model_account_customer->updateAgeStatus($customer_id,2,$this->request->post['dob'],$response['meta']['confirmation'],$response['result']['detail']);
				return false;
			}else{
				$this->model_account_customer->updateAgeStatus($customer_id,3,$this->request->post['dob'],$response['meta']['confirmation'],$response['result']['detail']);
				return true;
			}

		}

		return false;
	}

}
