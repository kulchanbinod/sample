<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Doc;
use App\Contact;

class ShareController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    public function share_accept(Doc $doc,Contact $contact,Request $request){

    	// remove session redirects
    	session()->forget('redirect');

        //check if contact user exist or not
        $user = \App\User::where('email',$contact->email)->get()->first();      

        if(null == $user){
            session()->put('redirect',$request->path());
            session()->put('redirect_message',"Please Register to accept this request");
            return redirect('/register');            
        }

    	if(auth()->guest()){
    		session()->put('redirect',$request->path());
    		session()->put('redirect_message',"Please Login to accept this request");
    		return redirect('/login');
    	}

    	//check if current user email is equal to contact email
    	if(auth()->user()->email != $contact->email){
    		session()->put('redirect_message',"Sorry you don't have valid permission. Please login with valid account");
    		return redirect('/home');
    	}

    	$shared = \App\Share::where([
    		'doc_id'=>$doc->id,
    		'contact_id'=>$contact->id,
    		'user_id'=>auth()->user()->id
		])->get();

    	if(count($shared) > 0){
    		session()->put('redirect_message',"Link already activated");
    		return redirect('/home');
    	}       

    	$share = \App\Share::create([
    		'doc_id'=>$doc->id,
    		'contact_id'=>$contact->id,
    		'user_id'=>auth()->user()->id
		]);

        return redirect('/home');

    }
}
