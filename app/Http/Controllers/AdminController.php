<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\admin;
use App\Models\product;
use App\Models\category;
use App\Models\image;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AdminController extends Controller
{

    public function register(){
        return view('admin.register');
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|unique:admins,email',
            'password' => 'required|min:8|max:15',
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput(); 
        }
        
        if($request->key === 'code'){
        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->password = Hash::make($request->password);

        $admin->save();

        return redirect()->route('adminlogin')->with('success', 'Admin added successfully');
    }
    else{
        return redirect()->route('adminregister')->with('warning','You have not access to register');
    }

    }
    public function login(){
        return view('admin.login');
    }

    public function authentication(Request $request){

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|max:15',
        ]);
        
        $admin = admin::where('email', $request->email)->first();
        
        if ($admin && Hash::check($request->password, $admin->password)) {
            Session::put('admin', $admin);
            return redirect('/admin/dashboard');
        }

        else{
            
            return redirect()->back()->withErrors(['login_error' => 'Invalid credentials']);
        }
    }

    public function dashboard(){
        return view('admin.dashboard');
    }

    public function logout()
    {
        Session::forget('admin');
        return redirect('/admin/login');
    }

    public function category(){
        $categories = Category::all();
        return view('admin.add_category',compact('categories'));
    }

    public function addCategory(Request $request){

        $request->validate([
            'category_name' => 'required|string|max:255',
            'parent_category_id' => 'nullable|exists:categories,id',
        ]);

        Category::create($request->all());

        return redirect()->route('admin.category')->with('success', 'Category created successfully.');

    }

    public function addproduct($id = null){

        $products = $id ? Product::findOrFail($id) : null;
        // $selectedCategoryId = $products->category_id;
        $categories = Category::all();
        return view('admin.add_product',compact('categories'),compact('products'));
    }

    public function storeproduct(Request $request){
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'basic_price' => 'required|numeric',
            'discounted_price' => 'nullable|numeric',
            'small_description' => 'nullable|string',
            'detail_description' => 'nullable|string',
            // 'product_images' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput(); 
        }

        // Create a new Product instance and fill it with the validated data
        $product = new Product();
        $product->product_name = $request->input('product_name');
        $product->category_id = $request->input('category_id');
        $product->basic_price = $request->input('basic_price');
        $product->discounted_price = $request->input('discounted_price');
        $product->small_description = $request->input('small_description');
        $product->detail_description = $request->input('detail_description');
        // Save the product to the database
        $product->save();

        $productId = $product->id;

        if ($request->hasFile('product_images')) {
            $images = $request->file('product_images');

            foreach ($images as $image) {
                // Generate a unique image name using Str::uuid()
                $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads'), $imageName);

                // Save each image to the 'images' table
                $imageModel = new Image();
                $imageModel->product_id = $productId;
                $imageModel->image_path = 'uploads/' . $imageName;
                $imageModel->save();
            }
        }

        // Redirect back or to a success page
        return redirect()->back()->with('success', 'Product added successfully');

    }
    public function updateproduct(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'basic_price' => 'required|numeric',
            'discounted_price' => 'nullable|numeric',
            'small_description' => 'nullable|string',
            'detail_description' => 'nullable|string',
            // 'product_images' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput(); 
        }

        // Create a new Product instance and fill it with the validated data
        $product = Product::find($id);
        $product->product_name = $request->input('product_name');
        $product->category_id = $request->input('category_id');
        $product->basic_price = $request->input('basic_price');
        $product->discounted_price = $request->input('discounted_price');
        $product->small_description = $request->input('small_description');
        $product->detail_description = $request->input('detail_description');
        // Save the product to the database
        $product->save();

        $productId = $product->id;

        if ($request->hasFile('product_images')) {
            $images = $request->file('product_images');

            foreach ($images as $image) {
                // Generate a unique image name using Str::uuid()
                $imageName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads'), $imageName);

                // Save each image to the 'images' table
                $imageModel = new Image();
                $imageModel->product_id = $productId;
                $imageModel->image_path = 'uploads/' . $imageName;
                $imageModel->save();
            }
        }

        // Redirect back or to a success page
        return redirect('/admin/viewproduct')->with('success', 'Product edited successfully');

    }

    public function viewproduct(){
       

        $products = product::with('category')->orderBy('created_at', 'desc')->get();
        $data = compact("products");
        return view('admin.view_product')->with($data);
    }

    public function deleteproduct($id){
        $product = product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    
        $product->delete();
    
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
