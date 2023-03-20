<?php

use App\Models\Transactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

     function generateInvoicePdf($invoice) {
        ini_set('memory_limit', '8G');

        // send data to invoice.blade.php and generate pdf using Barryvdh\DomPDF
        $pdf = Pdf::loadView('invoice', $invoice);

        // save pdf in storage path i.e. storage/app/invoices
        $pdf_name = 'invoice_'.time().'.pdf';
        $path = Storage::put('invoices/'.$pdf_name,$pdf->output());

        // update pdf_url in database subscriptions table
        $invoice_url = ('storage/app/invoices/'.$pdf_name);
        Transactions::where('id', '=', $invoice['trxn_id'])->update(['invoice_url' => $invoice_url, 'updated_at' => Carbon::now()]);

        // send invoice to customer
        $email = $invoice['user_email'];
        $data1 = ['salutation' => __('msg.Dear'),'name'=> $invoice['user_name'], 'msg'=> __('msg.This email serves to confirm the successful setup of your subscription with Us.'), 'msg1'=> __('msg.We are delighted to welcome you as a valued subscriber and are confident that you will enjoy the benefits of Premium Services.'),'msg2' => __('msg.Thank you for your trust!')];

        Mail::send('invoice_email', $data1, function ($message) use ($pdf_name, $email, $pdf) {
            $message->to($email)->subject('Invoice');
            $message->attachData($pdf->output(), $pdf_name, ['as' => $pdf_name, 'mime' => 'application/pdf']);
        });
        
        return $path;
    }
?>