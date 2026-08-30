<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    /**
     * Show the public order tracking search view.
     */
    public function index()
    {
        return view('track');
    }

    /**
     * Search orders based on query parameters.
     */
    public function search(Request $request)
    {
        $request->validate([
            'search_query' => 'required|string|max:255',
        ]);

        $query = $request->input('search_query');

        // Check if searching by order number or phone number
        $orders = Order::where('order_number', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('whatsapp', 'like', "%{$query}%")
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $searched = true;

        return view('track', compact('orders', 'query', 'searched'));
    }
}
