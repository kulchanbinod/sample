<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Contact;

class ContactController extends Controller
{
	/**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function add(Request $request){
    	
    	$contact = auth()->user()->contacts()->create([
    		'name'=>$request->input('name'),
    		'email'=>$request->input('email')
    	]);

    	return auth()->user()->contacts;
    }

    public function delete(Contact $contact){

        if(Auth()->user()->id != $contact->user_id)
            echo "no permission";exit;
        
        $contact->delete();
        
        return Redirect::to(url('/home'));

    }

	public function importGoogleContact(Request $resquest)
	{
	    // get data from request
		$code = $resquest->get('code');

	    // get google service
		$googleService = \OAuth::consumer('Google');

	    // check if code is valid

	    // if code is provided get user data and sign in
		if ( ! is_null($code)) {
	        // This was a callback request from google, get the token
			$token = $googleService->requestAccessToken($code);

	        // Send a request with it
			$result = json_decode($googleService->request('https://www.google.com/m8/feeds/contacts/default/full?alt=json&max-results=400'), true);

	        // Going through the array to clear it and create a new clean array with only the email addresses
	        $emails = []; // initialize the new array
	        $i = 0;
	        foreach ($result['feed']['entry'] as $contact) {
	            if (isset($contact['gd$email'])) { // Sometimes, a contact doesn't have email address
	            	$emails[$i]['name'] = $contact['title']['$t'];
	            	$emails[$i++]['email'] = $contact['gd$email'][0]['address'];
		        }
		    }

		    foreach ($emails as $key => $email) {
		    	
		    	$contact = auth()->user()->contacts()->where(['email'=>$email['email']])->get();

		    	if(count($contact) == 0){
		    		\App\Contact::create([
		    			'name' => $email['name'],
		    			'email' => $email['email'],
		    			'user_id' => auth()->user()->id
	    			]);
		    	}

		    }

			return redirect(url('home'));
		}

	    // if not ask for permission first
		else {
	        // get googleService authorization
			$url = $googleService->getAuthorizationUri();

	        // return to google login url
			return redirect((string)$url);
		}
	}

}
