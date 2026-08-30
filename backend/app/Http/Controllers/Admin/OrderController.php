<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display listings of orders.
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        
        $query = Order::orderBy('created_at', 'desc');
        
        if ($status) {
            $query->where('order_status', $status);
        }
        
        $orders = $query->get();
        return view('admin.orders.index', compact('orders', 'status'));
    }

    /**
     * Display details of specific order.
     */
    public function show(Order $order)
    {
        $order->load('items');
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order details (statuses and lorry logistics).
     */
    public function update(Request $request, Order $order)
    {
        $request->validate([
            'order_status' => 'required|in:pending,approved,processing,shipped,delivered,cancelled',
            'payment_status' => 'required|in:pending,paid,verified',
            'transport_name' => 'nullable|string|max:255',
            'lr_number' => 'nullable|string|max:255',
        ]);

        $order->update([
            'order_status' => $request->order_status,
            'payment_status' => $request->payment_status,
            'transport_name' => $request->transport_name,
            'lr_number' => $request->lr_number,
        ]);

        return redirect()->route('admin.orders.show', $order->id)->with('success', 'Order status updated successfully!');
    }

    /**
     * Show the edit items page for an order.
     */
    public function editItems(Order $order)
    {
        $order->load('items');
        
        // Fetch all categories with active products, sorted
        $categories = Category::active()
            ->with(['products' => function ($query) {
                $query->active()->orderBy('sort_order', 'asc');
            }])
            ->orderBy('sort_order', 'asc')
            ->get();
            
        // Map current order items
        $orderItems = $order->items->pluck('quantity', 'product_id')->toArray();
        
        return view('admin.orders.edit_items', compact('order', 'categories', 'orderItems'));
    }

    /**
     * Update the items in an order.
     */
    public function updateItems(Request $request, Order $order)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*' => 'required|integer|min:0',
        ]);

        $submittedItems = $request->input('items');
        $subtotal = 0; // MRP sum
        $netAmount = 0; // selling price sum
        $validatedItems = [];

        foreach ($submittedItems as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }

            $product = Product::find($productId);
            if (!$product) {
                return redirect()->back()->withErrors(['items' => "Product #{$productId} not found."]);
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
            return redirect()->back()->withErrors(['items' => 'The order must contain at least one item.'])->withInput();
        }

        // Fetch feature flags config
        $enableTaxDelivery = Setting::get('enable_tax_delivery', 'no') === 'yes';
        $taxPercent = (float) Setting::get('tax_percent', 18);
        $deliveryCharge = (float) Setting::get('delivery_charge', 150);

        // Calculate tax and delivery
        $taxAmount = 0;
        $deliveryChargeVal = 0;
        if ($enableTaxDelivery) {
            $taxAmount = $netAmount * ($taxPercent / 100);
            $deliveryChargeVal = $deliveryCharge;
        }

        $finalNet = $netAmount + $taxAmount + $deliveryChargeVal;
        $discountAmount = $subtotal - $netAmount; // MRP - selling price

        try {
            DB::beginTransaction();

            // 1. Delete existing items
            $order->items()->delete();

            // 2. Re-create new items
            foreach ($validatedItems as $vItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $vItem['product']->id,
                    'product_name' => $vItem['product']->name,
                    'pack_size' => $vItem['product']->pack_size,
                    'price' => $vItem['price'],
                    'quantity' => $vItem['qty'],
                    'total_price' => $vItem['total_price'],
                ]);
            }

            // 3. Update order totals
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'net_amount' => $finalNet,
            ]);

            DB::commit();

            return redirect()->route('admin.orders.show', $order->id)->with('success', 'Order items updated successfully!');

        } catch (\Exception $exception) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Admin Order Update Items failed: ' . $exception->getMessage());
            return redirect()->back()->withErrors(['error' => 'Something went wrong: ' . $exception->getMessage()])->withInput();
        }
    }

    /**
     * Print invoice for order.
     */
    public function printInvoice(Order $order)
    {
        $order->load('items');
        return view('admin.orders.invoice', compact('order'));
    }
}
