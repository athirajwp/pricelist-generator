<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    /**
     * Store a newly created order.
     */
    public function store(Request $request)
    {
        $phone = preg_replace('/[^0-9]/', '', $request->input('phone', ''));
        $whatsapp = preg_replace('/[^0-9]/', '', $request->input('whatsapp', ''));
        
        $request->merge([
            'phone' => $phone,
            'whatsapp' => $whatsapp ?: null,
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|digits:10',
            'whatsapp' => 'nullable|digits:10',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'landmark' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'promo_code' => 'nullable|string|max:50',
        ], [
            'phone.required' => 'Mobile number is required.',
            'phone.digits' => 'Mobile number must be exactly 10 digits.',
            'whatsapp.digits' => 'WhatsApp number must be exactly 10 digits.',
        ]);

        $cartItems = $request->input('items');
        $subtotal = 0; // MRP sum
        $netAmount = 0; // selling price sum
        $validatedItems = [];

        // Fetch products in 1 single bulk query for instant performance
        $productIds = array_filter(array_map(function ($i) { return $i['id'] ?? null; }, $cartItems));
        $productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($cartItems as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $product = $productsMap->get($item['id']);
            if (!$product) {
                return response()->json(['error' => 'Product not found!'], 422);
            }

            $itemSubtotal = $product->mrp * $qty;
            $itemNet = $product->selling_price * $qty;

            $subtotal += $itemSubtotal;
            $netAmount += $itemNet;

            $validatedItems[] = [
                'product' => $product,
                'qty' => $qty,
                'price' => $product->selling_price,
                'total_price' => $itemNet,
            ];
        }

        if (empty($validatedItems)) {
            return response()->json(['error' => 'Your cart is empty!'], 422);
        }

        // Fetch feature flags config
        $enableMinOrder = Setting::get('enable_min_order', 'yes') === 'yes';
        $enablePromoCodes = Setting::get('enable_promo_codes', 'yes') === 'yes';
        $enableTaxDelivery = Setting::get('enable_tax_delivery', 'no') === 'yes';
        $taxPercent = (float) Setting::get('tax_percent', 18);
        $deliveryCharge = (float) Setting::get('delivery_charge', 150);

        // Validate Minimum Purchase
        if ($enableMinOrder) {
            $minOrder = Setting::get('min_order_value', 3800);
            if ($netAmount < $minOrder) {
                return response()->json([
                    'error' => "Minimum order value is ₹{$minOrder}. Your current order is ₹{$netAmount}. Please add more items."
                ], 422);
            }
        }

        // Backend Promo Code validation & calculation
        $promoDiscount = 0;
        $appliedPromo = null;

        if ($enablePromoCodes && $request->filled('promo_code')) {
            $submittedCode = strtoupper(trim($request->input('promo_code')));
            for ($i = 1; $i <= 5; $i++) {
                $codeSetting = strtoupper(trim(Setting::get("promo_code_{$i}", '')));
                if (!empty($codeSetting) && $codeSetting === $submittedCode) {
                    $valueSetting = trim(Setting::get("promo_value_{$i}", ''));
                    $appliedPromo = $codeSetting;
                    
                    if (str_contains($valueSetting, '%')) {
                        $percentage = (float) str_replace('%', '', $valueSetting);
                        if ($percentage > 0) {
                            $promoDiscount = ($netAmount * $percentage) / 100;
                        }
                    } else {
                        $flat = (float) $valueSetting;
                        if ($flat > 0) {
                            $promoDiscount = min($flat, $netAmount);
                        }
                    }
                    break;
                }
            }
        }

        $originalNet = $netAmount;
        $postPromoNet = max(0, $originalNet - $promoDiscount);

        // Calculate tax and delivery
        $taxAmount = 0;
        $deliveryChargeVal = 0;
        if ($enableTaxDelivery) {
            $taxAmount = $postPromoNet * ($taxPercent / 100);
            $deliveryChargeVal = $deliveryCharge;
        }

        $finalNet = $postPromoNet + $taxAmount + $deliveryChargeVal;
        $discountAmount = ($subtotal - $originalNet) + $promoDiscount;

        $notes = $request->notes;
        if ($appliedPromo) {
            $notes = trim(($notes ? $notes . "\n" : "") . "[Applied Promo Code: {$appliedPromo} (Saved ₹" . number_format($promoDiscount, 2) . " extra discount)]");
        }
        if ($enableTaxDelivery) {
            $notes = trim(($notes ? $notes . "\n" : "") . "[Pricing Breakdown:\n- Net Amount: ₹" . number_format($postPromoNet, 2) . "\n- GST / Tax (" . $taxPercent . "%): ₹" . number_format($taxAmount, 2) . "\n- Delivery Fee: ₹" . number_format($deliveryChargeVal, 2) . "]");
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'whatsapp' => $request->whatsapp ?? $request->phone,
                'email' => $request->email,
                'address' => $request->address ?? '',
                'landmark' => $request->landmark ?? '',
                'city' => $request->city ?? '',
                'state' => $request->state ?? '',
                'pincode' => $request->pincode ?? '',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'net_amount' => $finalNet,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'notes' => $notes,
            ]);

            $now = now();
            $orderItemsToInsert = [];
            foreach ($validatedItems as $vItem) {
                $orderItemsToInsert[] = [
                    'order_id' => $order->id,
                    'product_id' => $vItem['product']->id,
                    'product_name' => $vItem['product']->name,
                    'pack_size' => $vItem['product']->pack_size,
                    'price' => $vItem['price'],
                    'quantity' => $vItem['qty'],
                    'total_price' => $vItem['total_price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            OrderItem::insert($orderItemsToInsert);

            // Deduct stock quantity for ordered products
            foreach ($validatedItems as $vItem) {
                $prodModel = $vItem['product'];
                if ($prodModel && ($prodModel->manage_stock ?? 'yes') !== 'no') {
                    $prodModel->stock_quantity = max(0, (int) $prodModel->stock_quantity - (int) $vItem['qty']);
                    $prodModel->updateStockStatus();
                    $prodModel->save();
                }
            }

            DB::commit();

            // Compute whatsappUrl for fast frontend handoff
            $whatsappNum = preg_replace('/[^0-9]/', '', Setting::get('store_whatsapp', '919998887776'));
            $storeName = Setting::get('store_name', 'Cracker Demo');
            $waMessage = "Hello " . $storeName . ", I have placed an order!\n\n"
                       . "*Order Number:* {$order->order_number}\n"
                       . "*Customer Name:* {$order->name}\n"
                       . "*Total Amount:* ₹" . number_format($order->net_amount, 2) . "\n\n"
                       . "Please confirm my booking and coordinate delivery details.";
            $whatsappUrl = "https://api.whatsapp.com/send?phone={$whatsappNum}&text=" . urlencode($waMessage);

            // Register email dispatch to run after HTTP response is flushed to client (<200ms response time!)
            \register_shutdown_function(function () use ($order) {
                if (\function_exists('fastcgi_finish_request')) {
                    @\fastcgi_finish_request();
                }
                self::sendOrderEmailsDirect($order);
            });

            return response()->json([
                'success' => true,
                'order' => $order->load('items'),
                'whatsappUrl' => $whatsappUrl,
                'redirect' => route('checkout.success', ['order_number' => $order->order_number])
            ]);

        } catch (\Throwable $exception) {
            try {
                DB::rollBack();
            } catch (\Throwable $rbEx) {}
            Log::error('Order placement failed: ' . $exception->getMessage());
            return response()->json(['error' => 'Order placement failed: ' . $exception->getMessage()], 500);
        }
    }

    /**
     * Send order email notifications directly, reliably, and within 1-2 seconds.
     */
    public static function sendOrderEmailsDirect($order)
    {
        try {
            $company = view()->shared('currentCompany');
            if ($company) {
                if (!empty($company->smtp_host) && !empty($company->smtp_user) && !empty($company->smtp_pass)) {
                    $sslVal = strtolower((string)$company->smtp_ssl);
                    $encryption = ($sslVal === 'true' || $sslVal === 'ssl' || $company->smtp_port == 465) ? 'ssl' : 'tls';
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.transport' => 'smtp',
                        'mail.mailers.smtp.host' => trim($company->smtp_host),
                        'mail.mailers.smtp.port' => (int) ($company->smtp_port ?: 587),
                        'mail.mailers.smtp.encryption' => $encryption,
                        'mail.mailers.smtp.username' => trim($company->smtp_user),
                        'mail.mailers.smtp.password' => trim(str_replace(' ', '', $company->smtp_pass)),
                        'mail.from.address' => trim($company->smtp_user),
                        'mail.from.name' => $company->name ?: config('mail.from.name'),
                    ]);
                }
            }

            $adminEmail = Setting::get('store_email', config('mail.from.address'));
            if (!empty($adminEmail)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\AdminInvoiceMail($order));
                    \Illuminate\Support\Facades\Log::info("Direct Admin email sent for order #{$order->id} to {$adminEmail}");
                } catch (\Throwable $e1) {
                    \Illuminate\Support\Facades\Log::error("Direct Admin Email SMTP failed for order #{$order->id}: " . $e1->getMessage() . " - Retrying via sendmail...");
                    try {
                        config(['mail.default' => 'sendmail']);
                        \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\AdminInvoiceMail($order));
                        \Illuminate\Support\Facades\Log::info("Sendmail fallback Admin email sent for order #{$order->id} to {$adminEmail}");
                    } catch (\Throwable $e1Fb) {
                        \Illuminate\Support\Facades\Log::error("Sendmail fallback Admin Email failed for order #{$order->id}: " . $e1Fb->getMessage());
                    }
                }
            }

            if (!empty($order->email)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($order->email)->send(new \App\Mail\CustomerOrderMail($order));
                    \Illuminate\Support\Facades\Log::info("Direct Customer email sent for order #{$order->id} to {$order->email}");
                } catch (\Throwable $e2) {
                    \Illuminate\Support\Facades\Log::error("Direct Customer Email SMTP failed for order #{$order->id}: " . $e2->getMessage() . " - Retrying via sendmail...");
                    try {
                        config(['mail.default' => 'sendmail']);
                        \Illuminate\Support\Facades\Mail::to($order->email)->send(new \App\Mail\CustomerOrderMail($order));
                        \Illuminate\Support\Facades\Log::info("Sendmail fallback Customer email sent for order #{$order->id} to {$order->email}");
                    } catch (\Throwable $e2Fb) {
                        \Illuminate\Support\Facades\Log::error("Sendmail fallback Customer Email failed for order #{$order->id}: " . $e2Fb->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("sendOrderEmailsDirect exception for order #{$order->id}: " . $e->getMessage());
        }
    }

    /**
     * Show checkout success page with payment information.
     */
    public function success($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();

        // Load payment details
        $upiId = Setting::get('store_upi', 'aathishacrackers@okaxis');
        $storeName = Setting::get('store_name', 'Cracker Demo');
        
        // Build raw UPI pay link for QR generation:
        // upi://pay?pa=address@bank&pn=Payee%20Name&am=Amount&cu=INR
        $encodedStoreName = urlencode($storeName);
        $upiPayUrl = "upi://pay?pa={$upiId}&pn={$encodedStoreName}&am={$order->net_amount}&cu=INR";
        
        // Check if custom uploaded UPI QR code exists, otherwise generate dynamically
        $customQr = Setting::get('store_upi_qr', '');
        if (!empty($customQr) && file_exists(public_path($customQr))) {
            $qrCodeUrl = '/' . $customQr;
        } else {
            $qrCodeUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($upiPayUrl) . "&choe=UTF-8";
        }

        $bankDetails = [
            'name' => Setting::get('bank_name', 'State Bank of India'),
            'acc_no' => Setting::get('bank_acc_no', '1234567890'),
            'ifsc' => Setting::get('bank_ifsc', 'SBIN0000123'),
            'holder' => Setting::get('bank_holder', 'Cracker Demo'),
        ];

        $whatsappNum = preg_replace('/[^0-9]/', '', Setting::get('store_whatsapp', '919998887776'));

        // Formulate pre-filled WhatsApp verification text
        $waMessage = "Hello " . $storeName . ", I have placed an order!\n\n"
                   . "*Order Number:* {$order->order_number}\n"
                   . "*Customer Name:* {$order->name}\n"
                   . "*Total Amount:* ₹" . number_format($order->net_amount, 2) . "\n\n"
                   . "Please confirm my booking and coordinate delivery details.";
        
        $whatsappUrl = "https://api.whatsapp.com/send?phone={$whatsappNum}&text=" . urlencode($waMessage);

        // Build the pre-filled invoice message for customer's WhatsApp
        $waMessageCustomer = "Hello *" . $order->name . "*,\n\n"
                           . "Here is the invoice summary for your order at *" . $storeName . "*:\n\n"
                           . "*Order Number:* " . $order->order_number . "\n"
                           . "*Order Date:* " . $order->created_at->format('d M Y, h:i A') . "\n"
                           . "*Net Amount:* ₹" . number_format($order->net_amount, 2) . "\n"
                           . "*Order Status:* " . ucfirst($order->order_status) . "\n"
                           . "*Payment Status:* " . ucfirst($order->payment_status) . "\n\n"
                           . "*Order Items Summary:*\n";
        
        foreach($order->items as $item) {
            $waMessageCustomer .= "• " . $item->product_name . " (Qty: " . $item->quantity . ") - ₹" . number_format($item->total_price, 2) . "\n";
        }
        
        $waMessageCustomer .= "\nTrack your order here: " . route('track.index', ['query' => $order->order_number]) . "\n\n"
                            . "Thank you for booking with us!";
        
        $customerPhone = preg_replace('/[^0-9]/', '', $order->whatsapp ?: $order->phone);
        if (strlen($customerPhone) === 10) {
            $customerPhone = '91' . $customerPhone;
        }
        $customerWhatsappUrl = "https://api.whatsapp.com/send?phone=" . $customerPhone . "&text=" . urlencode($waMessageCustomer);

        return view('checkout_success', compact('order', 'qrCodeUrl', 'bankDetails', 'whatsappUrl', 'whatsappNum', 'customerWhatsappUrl'));
    }

    /**
     * Download or stream the formal PDF invoice.
     */
    public function downloadInvoice($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.orders.invoice', [
            'order' => $order,
            'is_email_or_pdf' => true,
            'is_pdf_render' => true,
        ])->setPaper('a4', 'portrait')->setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => false,
            'isFontSubsettingEnabled' => false,
            'defaultFont' => 'sans-serif',
        ]);

        return $pdf->stream('enquiry-' . $order->order_number . '.pdf');
    }
}
