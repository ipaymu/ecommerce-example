<?php

namespace App\Http\Controllers\Front;

use App\OrderItems;
// use Cartalyst\Stripe\Laravel\Facades\Stripe;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mockery\Exception;
use App\Order;
use Carbon\Carbon;

class CheckoutController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }

    public function index(){
         return view('front.checkout.index');
    }

    public function store(Request $request) {

        $contents = Cart::instance('default')->content()->map(function($item) {
            return $item->model->name . ' ' . $item->qty;
        })->values()->toJson();
        // $test = Cart::instance('default')->content();
        // session()->flush();
        // dd(Cart::content());


        try { 

            // Insert into orders table
            $order = Order::create([
                'user_id' => auth()->user()->id,
                'date' => Carbon::now(),
                'address' => $request->address,
                'status' => 0
            ]);

            // Insert into order items table
            foreach (Cart::instance('default')->content() as $item) {

                OrderItems::create([
                    'order_id' => $order->id,
                    'product_id' => $item->model->id,
                    'quantity' => $item->qty,
                    'price' => $item->price
                ]);

            }


            $product = array();
            $quantity = array();
            $prices = array();
            $comments = array();
            $weight = array();
            $dimensi = array();

            foreach (Cart::instance('default')->content() as $item) {
                array_push($product,$item->name);
                array_push($quantity,$item->qty);
                array_push($prices, $item->price);
                array_push($comments,'Optional');
                array_push($weight,1);
                array_push($dimensi,'1:1:1');

            }
            
            // iPaymu

            $url = 'http://sandbox.ipaymu.com/payment';  // URL Payment iPaymu           
            $params = array(   // Prepare Parameters            
                'key'      => env('API_KEY'), // API Key Merchant / Penjual
                'action'   => 'payment',
                'product'  => $product,
                'price'    => $prices, // Total Harga
                'quantity' => $quantity,
                'comments' => $comments, // Optional
                'ureturn'  => 'http://websiteanda.com/return.php?q=return',
                'unotify'  => 'http://websiteanda.com/notify.php',
                'ucancel'  => 'http://websiteanda.com/cancel.php',
                'format'   => 'json', // Format: xml atau json. Default: xml
                
                // COD
                'weight'     => $weight, // Berat barang (satuan kilo, menerima array)
                'dimensi'    => $dimensi, // Dimensi barang (format => panjang:lebar:tinggi, menerima array)
                'postal_code'=> '80361',  // Kode pos untuk custom pikcup
                'address'    => 'Jalan Raya Kuta, No 88R, Badung, Bali', // Alamat untuk custom pickup
                );
            
            $params_string = http_build_query($params);


            //open connection
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            
            //execute post
            $request = curl_exec($ch);
            
            if ( $request === false ) {
                echo 'Curl Error: ' . curl_error($ch);
            } else {
              
                Cart::destroy();

                $result = json_decode($request, true);
                if( isset($result['url']) ){
                    header('location: '. $result['url']);
                }
                else {
                    echo "Error ". $result['Status'] .":". $result['Keterangan'];
                }
            }
            
            //close connection
            curl_close($ch);
            exit;

            // Success
            return redirect()->back()->with('msg','Success Thank you');

        } catch (Exception $e) {

            // Code
            return redirect()->back()->withErrors('msg','Failed Try Again');

        }

    }

}
