<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
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

    public function add(){
    	return view('admin.blog_add');
    }

    public function edit($id){
        $blog = \App\Blog::findOrFail($id);
        return view('admin.blog_edit',compact('blog'));
    }

    public function store(Request $request){

    	$validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return view('admin.blog_add')
            ->withInput($request->all())
            ->withErrors($validator, 'errors');
        }

        \App\Blog::create([
        	'title'=>$request->input('title'),
        	'content'=>$request->input('content'),
        	'image'=>''
        ]);

        return Redirect::to(url('/home'));
    }

    public function update(Request $request,$id){

        $blog = \App\Blog::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return view('admin.blog_edit',compact('blog'))
            ->withInput($request->all())
            ->withErrors($validator, 'errors');
        }
        $blog->update([
            'title'=>$request->input('title'),
            'content'=>$request->input('content'),
            'image'=>''
        ]);

        return Redirect::to(url('/home'));
    }

    public function delete($id){
        $blog = \App\Blog::findOrFail($id);
        $blog->delete();
        return Redirect::to(url('/home'));
    }
}
