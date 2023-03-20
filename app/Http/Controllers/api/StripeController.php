<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

use Stripe\Stripe;

use App\Models\Bookings;
use App\Models\Products;
use App\Models\Transactions;
use App\Models\User;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StripeController extends Controller
{
    private $stripe;
    private $stripe_key;

    public function __construct() {
        // Multiligual
        $lang = (isset($_POST['language']) && !empty($_POST['language'])) ? $_POST['language'] : 'en';
        App::setlocale($lang);

         // Create a new instance of the Stripe client using the Stripe API key obtained from the 'STRIPE_SECRET' environment variable
         $this->stripe = new \Stripe\StripeClient(
            env('STRIPE_SECRET')
        );

        // Set the Stripe API key globally using the 'setApiKey' method from the 'Stripe' class
        $this->stripe_key = Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function payment(Request $request)
    {
        // Input validation
        $validator = Validator::make($request->all(), [
            'language' => [
                'required' ,
                Rule::in(['en','hi','ur','bn','ar','in','ms','tr','fa','fr','de','es']),
            ],
            'user_id'   => 'required||numeric',
            'product_id' =>'required',
            'unit_amount'     => 'required',
            'quantity'   => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.Validation Failed!'),
                'errors'    => $validator->errors()
            ],400);
        }

        try {

            $user_id = $request->user_id;
            $product_id = $request->product_id;
            $unit_amount = $request->unit_amount;
            $quantity = $request->quantity;

            // Fetch a Product object based on the given 'product_id' from the 'Products' model
            $product = Products::where('id', '=', $product_id)->first();
            
            // if product does not exists, throw an erorr
            if (!$product || empty($product)) {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.stripe.session.invalid'),
                ],400);
            }
          
            // Fetch a user based on the given 'user_id' from the 'Users' model
            $user = User::where('id', '=', $user_id)->first();

            // if user does not exists, throw an erorr
            if (!$user || empty($user)) {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.stripe.session.not-found'),
                ],400);
            }
            
            // Set the success and cancel URLs for the checkout session
            $success_url = url('api/stripe/paymentSuccess');
            $cancel_url = url('api/stripe/paymentFail');

            // Create a new Stripe checkout session object 
            $session = \Stripe\Checkout\Session::Create([
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                      'price_data'=> [
                        'currency'=> env('STRIPE_CURRENCY'),
                        'unit_amount'=> $unit_amount * 100,
                        'product_data'=> [
                            'name'=> $product->name,
                            'images' => [$product->image]
                            ],
                        ],
                      'quantity'=> $quantity,
                    ],
                ],
                'mode' => 'payment',
                'currency' => env('STRIPE_CURRENCY'),
            ]);

             // if session does not exists, throw an erorr
            if (!$session || empty($session)) {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.stripe.session.failure'),
                ],400);
            }

            $booking_data = [
                'session_id' => $session->id,
                'user_id' => $user_id,
                'product_id' => $product_id,
                'product_price' => $product->price,
                'amount_paid' => $session->amount_total/100,
                'currency' => $session->currency,
                'quantity' => $quantity,
                'payment_status' => $session->payment_status,
                'session_status' => $session->status,
                'created_at' => Carbon::now()
            ];

            // Insert the booking data into the booking table
            $booking_id = Bookings::insertGetId($booking_data);

            $session_data = [
                'session_id'  => $session->id,
                'success_url' => $session->success_url,
                'cancel_url'  => $session->cancel_url,
                'stripe_url'  => $session->url
            ];

            if($booking_id){
                return response()->json([
                    'status'    => 'success',
                    'message'   => trans('msg.stripe.session.success'),
                    'data'      => $session_data
                ],200);
            }else{
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.stripe.session.failure'),
                ],400);
            }
        } catch (\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            $err  = 'Status:' . $e->getHttpStatus() . '<br>';
            $err  .= 'Type:' . $e->getError()->type . '<br>';
            $err  .= 'Code:' . $e->getError()->code . '<br>';
            // param is '' in this case
            $err  .= 'Param:' . $e->getError()->param . '<br>';
            $err  .= 'Message:' . $e->getError()->message . '<br>';
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $err
            ],500);
            // $this->session->set_flashdata('error',  $err);
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
            // $this->session->set_flashdata('error',  $err);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
             return response()->json([
                 'status'    => 'failed',
                 'message'   => trans('msg.error'),
                 'error'     => $e->getMessage()
             ],500);
         } catch (\Throwable $e) {
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        }
    }

    public function paymentSuccess(Request $request){
        // input validation
        $validator = Validator::make($request->all(), [
            'language' => [
                'required' ,
                Rule::in(['en','hi','ur','bn','ar','in','ms','tr','fa','fr','de','es']),
            ],
            'session_id'   => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.Validation Failed!'),
                'errors'    => $validator->errors()
            ],400);
        }

        try{

            $session_id = $request->session_id;

            // Fetch payment details based on the given 'session_id' from the 'Bookings' table
            $payment_details = Bookings::where('session_id', '=', $session_id)->first();

            if (!empty($payment_details)) {
                $user_id = $payment_details->user_id ? $payment_details->user_id : '';
                $booking_id = $payment_details->id;

                // retrive session from stripe using session_id
                $session = \Stripe\Checkout\Session::Retrieve(
                    $payment_details->session_id,
                    []
                );

                if($session->payment_status == "paid" && $session->status == "complete"){

                    $booking_data  =  [
                        'payment_id' => $session->payment_intent,
                        'amount_paid' => $session->amount_total/100,
                        'payment_status' => $session->payment_status,
                        'session_status' => $session->status,
                        'updated_at'     => Carbon::now(),
                    ];

                    // update data in booking table
                    $update = Bookings::where('session_id', '=', $payment_details->session_id)->update($booking_data);

                    $transaction_data = [
                        'booking_id' => $booking_id,
                        'user_id' => $user_id,
                        'product_id' => $payment_details->product_id,
                        'payment_id' => $session->payment_intent,
                        'product_price' => $payment_details->product_price,
                        'quantity' => $payment_details->quantity,
                        'amount_paid' => $session->amount_total/100,
                        'currency' => $session->currency,
                        'payer_email' => $session->customer_details->email,
                        'payment_status' => $session->payment_status,
                        'created_at' => Carbon::now()
                    ];

                    // insert transaction details in Transactions table
                    $trxn = Transactions::insertGetId($transaction_data);

                    if ($trxn) {
                        $user = User::find($user_id);
                        $product = Products::find($payment_details->product_id);

                        // generate invoice pdf and send to customer
                        $invoice_data = [
                            'trxn_id' => $trxn,
                            'invoice_number' => (string)rand(10000, 20000),
                            'user_name' => $user ? $user->name : '',
                            'user_email' => $user ? $user->email : '',
                            'product_name' => $product ? $product->name : '',
                            'product_price' => $product ? $product->price : '',
                            'quantity' => $payment_details->quantity,
                            'amount_paid' => $session->amount_total/100,
                            'currency' => $session->currency,
                            'date' => Carbon::now()->format('d.m.Y')
                        ];
                        
                        // helper function tp generate and send invoice
                        generateInvoicePdf($invoice_data);
                    }
                    return response()->json([
                        'status'    => 'success',
                        'message'   => trans('msg.stripe.success'),
                    ],200);
                }elseif ($session->payment_status == "unpaid" && $session->status == "open") {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.stripe.failure'),
                        'stripe'  => [
                            'session_id'  => $session->id,
                            'stripe_url'  => $session->url,
                        ],
                    ],400);
                } else {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.stripe.failure'),
                    ],400);
                }
            }else{
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.stripe.invalid'),
                ],400);
            }
        } catch (\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            $err  = 'Status:' . $e->getHttpStatus() . '<br>';
            $err  .= 'Type:' . $e->getError()->type . '<br>';
            $err  .= 'Code:' . $e->getError()->code . '<br>';
            // param is '' in this case
            $err  .= 'Param:' . $e->getError()->param . '<br>';
            $err  .= 'Message:' . $e->getError()->message . '<br>';
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $err
            ],500);
            // $this->session->set_flashdata('error',  $err);
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
            // $this->session->set_flashdata('error',  $err);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
           // Network communication with Stripe failed
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Throwable $e) {
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        }
    }

    public function paymentFail(Request $request){
        // input validation
        $validator = Validator::make($request->all(), [
            'language' => [
                'required' ,
                Rule::in(['en','hi','ur','bn','ar','in','ms','tr','fa','fr','de','es']),
            ],
            'session_id'   => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.Validation Failed!'),
                'errors'    => $validator->errors()
            ],400);
        }

        try{

            $session_id = $request->session_id;

            // Fetch payment details based on the given 'session_id' from the 'Bookings' table
            $payment_details = Bookings::where('session_id', '=', $session_id)->first();
            if (!empty($payment_details)) {
                $user_id = $payment_details->user_id ? $payment_details->user_id : '';
                $booking_id = $payment_details->id;

                // retrive session from stripe using session_id
                $session = \Stripe\Checkout\Session::Retrieve(
                    $payment_details->session_id,
                    []
                );

                if($session->status == "open"){

                    // if session status is open, expire that session using session_id
                    $expire = $this->stripe->checkout->sessions->expire(
                        $payment_details->session_id,
                        []
                    );

                    $booking_data  =  [
                        'payment_status' => $expire->payment_status,
                        'session_status' => $expire->status,
                        'updated_at'     => Carbon::now(),
                    ];

                    // update status in booking table
                    $update = Bookings::where('session_id', '=', $payment_details->session_id)->update($booking_data);

                    $transaction_data = [
                        'booking_id' => $booking_id,
                        'user_id' => $user_id,
                        'product_id' => $payment_details->product_id,
                        'product_price' => $payment_details->product_price,
                        'quantity' => $payment_details->quantity,
                        'amount_paid' => 00,
                        'currency' => $session->currency,
                        'payment_status' => $session->payment_status,
                        'created_at' => Carbon::now()
                    ];

                    // insert subscription details in subscriptions table
                    $insert = Transactions::insert($transaction_data);

                    if($update){
                        return response()->json([
                            'status'    => 'failed',
                            'message'   => trans('msg.stripe.failure'),
                        ],400);
                    }
                } else if ($session->status == "complete") {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.stripe.paid'),
                    ],400);
                } else if ($session->status == "expired") {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.stripe.expaired'),
                    ],400);
                } else {
                    return response()->json([
                        'status'    => 'failed',
                        'message'   => trans('msg.stripe.failure'),
                    ],400);
                }
            } else {
                return response()->json([
                    'status'    => 'failed',
                    'message'   => trans('msg.stripe.invalid'),
                ],400);
            }
        } catch (\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            $err  = 'Status:' . $e->getHttpStatus() . '<br>';
            $err  .= 'Type:' . $e->getError()->type . '<br>';
            $err  .= 'Code:' . $e->getError()->code . '<br>';
            // param is '' in this case
            $err  .= 'Param:' . $e->getError()->param . '<br>';
            $err  .= 'Message:' . $e->getError()->message . '<br>';
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $err
            ],500);
            // $this->session->set_flashdata('error',  $err);
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
            // $this->session->set_flashdata('error',  $err);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
           // Network communication with Stripe failed
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        } catch (\Throwable $e) {
            return response()->json([
                'status'    => 'failed',
                'message'   => trans('msg.error'),
                'error'     => $e->getMessage()
            ],500);
        }
    }
}
