<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;

class DashboardController extends Controller
{
    /**
     * Renders sales metrics dashboard.
     */
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'shipped_orders' => Order::where('order_status', 'shipped')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('net_amount'),
            'pending_revenue' => Order::where('payment_status', 'pending')->sum('net_amount'),
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
        ];

        // Recent bookings
        $recentOrders = Order::orderBy('created_at', 'desc')->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
