<?php

namespace App\Http\Controllers;

use Mail;
use Session;
use App\Doc;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
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

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if(Session::has('redirect')){
            $redirect = session()->get('redirect');
            session()->forget('redirect');
            return redirect($redirect);
        }

        $docs = Auth()->user()->docs()->orderBy('id', 'desc')->get();
        if(Auth()->user()->id == 1){
            $blogs = \App\Blog::all();
            return view('admin.home',compact('blogs'));
        }else{
            $shared = \App\Share::where('user_id',Auth()->user()->id)->get()->all();
            return view('home',compact('docs','shared'));
        }
    }

    public function setting(){
        return view('setting');
    }

    public function update(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'username' => 'required',
            'old_password' => 'required',
            'new_password' => 'required',
            're_password' => 'required',
            ]);

        if ($validator->fails()) {
            return view('setting')
            ->withInput($request->all())
            ->withErrors($validator, 'errors');
        }

        Auth()->user()->name = $request->input('name');
        Auth()->user()->username = $request->input('username');
        
        Auth()->user()->update();
        
        if(!Hash::check($request->input('old_password'), Auth()->user()->password)){            
            $validator->errors()->add('old_password', 'Invalid Password');
            return view('setting')
            ->withInput($request->all())
            ->withErrors($validator, 'errors');
        }

        Auth()->user()->password = Hash::make($request->input('new_password'));
        Auth()->user()->update();

        session()->put('message', 'Setting Updated Successfully!');
        return Redirect::to(url('/setting'));
        
    }

    public function upload(Request $request){

        $validator = Validator::make($request->all(), [
            'tags' => 'required_without:file',
            ]);

        $tags = str_replace("\r\n","<br />",$request->input('tags') );

        if ($validator->fails()) {
            $docs = Auth()->user()->docs()->orderBy('id', 'desc')->get();;
            $shared = \App\Share::where('user_id',Auth()->user()->id)->get()->all();
            return view('home',compact('docs','shared'))
            ->withInput($request->all())
            ->withErrors($validator, 'errors');
        }

        if($request->hasFile('file')){

            $file = $request->file('file');
            $destinationPath = 'uploads';
            $file->move($destinationPath,$file->getClientOriginalName());

            $doc = \App\Doc::create([
                'name' => $file->getClientOriginalName(),
                'filename' => $file->getClientOriginalName(),
                'user_id' => Auth()->user()->id,
                'tags' => (null == $tags) ? '' : $tags
                ])->save();
        }else{
            $doc = \App\Doc::create([
                'name' => $tags,
                'filename' => '',
                'user_id' => Auth()->user()->id,
                'tags' => (null == $tags) ? '' : $tags
                ])->save();
        }

        return Redirect::to(url('/home'));

    }

    public function share(Request $request){

        $doc = \App\Doc::find($request->input('doc_id')); 
        $contacts = explode(",", $request->input('contacts'));
        foreach ($contacts as $key => $contact) {
            $contact = \App\Contact::find($contact);
            $contact->name = ($contact->name == '') ? $contact->email : $contact->name;
            Mail::send('emails.share', 
                [
                'doc_name' => $doc,
                'user' => auth()->user()->name,
                'contact_name' => $contact->name,
                'link' => url('/share/accept/'.$doc->id.'/'.$contact->id)  
                ], function($message) use ($contact) {
                    $message->from(auth()->user()->email);
                    $message->to($contact->email, $contact->name)->subject('Share Request !!!');
                });
        }

        if (Mail::failures()) {
            return Mail::failures();
        }

        return [];

    }

    public function edit(Doc $doc,Request $request){

        // if(Auth()->user()->id != $doc->user_id){
        //     echo "no permission";
        //     exit();
        // }

        $doc->tags = $request->tags;
        $doc->save();

        return ['tags'=>$doc->tags];

    }

    public function delete(Doc $doc){

        if(Auth()->user()->id != $doc->user_id){
            echo "no permission";
            exit(); 
        }

        $doc->delete();
        
        session()->put('success', 'Your file has been deleted.');

        return Redirect::to(url('/home'));

    }

    public function printAll(Request $request){
        $type = $request->input('type');
        switch ($type) {
            case 'all':
            $docs = auth()->user()->docs()->orderBy('id', 'desc')->get();
            break;

            case 'notes':
            $docs = auth()->user()->docs()->orderBy('id', 'desc')->get();
            break;

            case 'att':
            $docs = auth()->user()->docs()->orderBy('id', 'desc')->get();
            break;

            default:
            $docs = auth()->user()->docs()->orderBy('id', 'desc')->get();
            break;
        }
        

        return view('print.print',compact('docs','type'));

    }
}
