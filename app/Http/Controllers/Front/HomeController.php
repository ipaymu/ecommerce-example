<?php

namespace App\Http\Controllers\Front;

use App\Product;
use Gloudemans\Shoppingcart\Cart;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index() {
        
        // Cart::instance('default')->destroy();

        

        // Cart::destroy();


        $products = Product::inRandomOrder()->take(4)->get();

        return view('front.index', compact('products'));
    }
}
