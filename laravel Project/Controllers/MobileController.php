<?php

namespace App\Http\Controllers;

use App\Doc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class MobileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('mobile',['except'=>'index','login']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth()->guest()){
            return redirect()->to('/mobile/login');
        }else{
            $docs = Auth()->user()->docs()->orderBy('id', 'desc')->get();
            return view('mobile.dashboard',compact('docs'));
        }
    }

    public function setting(){
        return view('mobile.setting');
    }

    public function showEdit(Doc $doc)
    {
        return view('mobile.edit',compact('doc'));
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
        return Redirect::to(url('/mobile/setting'));
        
    }

    public function upload(Request $request){

        $validator = Validator::make($request->all(), [
            'tags' => 'required',
            ]);

        if ($validator->fails()) {
            $docs = Auth()->user()->docs;
            return view('mobile.dashboard',compact('docs'))
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
                'tags' => $request->input('tags')
                ])->save();
        }else{
            $doc = \App\Doc::create([
                'name' => $request->input('tags'),
                'filename' => '',
                'user_id' => Auth()->user()->id,
                'tags' => $request->input('tags')
                ])->save();
        }

        return Redirect::to(url('mobile/home'));

    }

    public function edit(Doc $doc,Request $request){

        if(Auth()->user()->id != $doc->user_id)
            echo "no permission";

        $doc->tags = $request->tags;
        $doc->save();

        if($request->ajax()){
            return ['tags'=>$doc->tags];
        }

        session()->put('success-edit', 'Updated Successfully');
        return Redirect::to(url('mobile/home'));        

    }

    public function delete(Doc $doc){

        if(Auth()->user()->id != $doc->user_id)
            echo "no permission";
        
        $doc->delete();
        
        session()->put('success', 'Your file has been deleted.');

        return Redirect::to(url('mobile/home'));

    }

    public function logout(Request $request)
    {
        Auth()->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return redirect('/mobile');
    }
}
