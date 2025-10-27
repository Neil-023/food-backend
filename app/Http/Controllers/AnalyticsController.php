<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    // 1. Top selling products
    public function topProducts(): JsonResponse
    {
        $results = DB::table('order_items_tbl as oi')
            ->join('product_tbl as p', 'oi.product_id', 'p.product_id')
            ->select(
                'p.product_id',
                'p.product_name',
                DB::raw('SUM(oi.quantity) as total_sold'),
                DB::raw('SUM(oi.quantity * oi.price_each) as total_revenue')
            )
            ->groupBy('p.product_id', 'p.product_name')
            ->orderByDesc('total_revenue')
            ->limit(4)
            ->get();

        return response()->json($results);
    }

    // 2. Top categories by revenue
    public function topCategories(): JsonResponse
    {
        $results = DB::table('order_items_tbl as oi')
            ->join('product_tbl as p', 'oi.product_id', 'p.product_id')
            ->join('category_tbl as c', 'p.category_id', 'c.category_id')
            ->select(
                'c.category_id',
                'c.category_name',
                DB::raw('SUM(oi.quantity * oi.price_each) as total_revenue')
            )
            ->groupBy('c.category_id', 'c.category_name')
            ->orderByDesc('total_revenue')
            ->limit(4)
            ->get();

        return response()->json($results);
    }

    // 3. Top customer per seller
    /**  
     * Return the top 3 customers (by total_spent) for this seller  
     */
    public function topCustomers(): JsonResponse
    {
        $sellerId = Auth::id();

        $results = DB::table('order_items_tbl as oi')
            ->join('product_tbl as p', 'oi.product_id', 'p.product_id')
            ->join('orders_tbl as o',   'oi.order_id',    'o.order_id')
            ->join('users_tbl as u',    'o.buyer_id',     'u.user_id')
            ->where('p.seller_id', $sellerId)
            ->select(
                'u.user_id as buyer_id',
                'u.full_name',
                DB::raw('SUM(oi.quantity * oi.price_each) as total_spent')
            )
            ->groupBy('u.user_id', 'u.full_name')
            ->orderByDesc('total_spent')
            ->limit(3)
            ->get();

        return response()->json($results);
    }

    // 4. Market‐basket (pairs frequently bought together)
    public function frequentCombos(): JsonResponse
    {
        $results = DB::select(
            'SELECT 
                 p1.product_id   AS product1_id,
                 p1.product_name AS product1_name,
                 p2.product_id   AS product2_id,
                 p2.product_name AS product2_name,
                 COUNT(*)        AS count
             FROM order_items_tbl AS oi1
             JOIN order_items_tbl AS oi2 
               ON oi1.order_id   = oi2.order_id
              AND oi1.product_id <  oi2.product_id
             JOIN product_tbl AS p1 
               ON oi1.product_id = p1.product_id
             JOIN product_tbl AS p2 
               ON oi2.product_id = p2.product_id
             GROUP BY 
               p1.product_id,
               p1.product_name,
               p2.product_id,
               p2.product_name
             ORDER BY count DESC
             LIMIT 4'
        );
        return response()->json($results);
    }

    public function recentOrders(): JsonResponse
    {
        $recentOrders = DB::table('order_items_tbl as oi')
            ->join('product_tbl as p', 'oi.product_id', 'p.product_id')
            ->join('orders_tbl as o', 'oi.order_id', 'o.order_id')
            ->join('users_tbl as u', 'o.buyer_id', 'u.user_id')
            ->select(
                'oi.quantity',
                'p.product_name',
                DB::raw("CONCAT('/storage/', p.product_img) as product_img"),
                'u.full_name'
            )
            ->orderByDesc('o.ordered_at')
            ->limit(4)
            ->get();


        return response()->json($recentOrders);
    }
}
