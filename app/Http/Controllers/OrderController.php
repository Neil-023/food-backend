<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use App\Models\OrderSeller;

class OrderController extends Controller
{
    public function index(Request $request)
    {

        $userId = $request->user()->user_id;
        $orders = Order::with(['items.product.seller', 'sellerStatuses'])
            ->where('buyer_id', $userId)
            ->orderBy('ordered_at', 'desc')
            ->get()

            ->map(function ($order) {
                $shops = $order->items->groupBy(fn($i) => $i->product->seller->user_id)
                    ->map(function ($items, $sellerId) use ($order) {
                        $sStatusRow = $order->sellerStatuses->firstWhere('seller_id', (int)$sellerId);
                        $sStatus = $sStatusRow ? $sStatusRow->status : null;
                        return [
                            'seller_id' => $sellerId,
                            'seller_name' => $items->first()->product->seller->shop_name,
                            'status' => $sStatus,
                            'items' => $items->map(fn($i) => [
                                'order_items_id' => $i->order_items_id,
                                'product_id'    => $i->product_id,
                                'product_name'  => $i->product->product_name,
                                'product_img' => $i->product->product_img,
                                'quantity'      => $i->quantity,
                                'price_each'    => $i->price_each,
                            ])->values(),
                        ];
                    });
            
                return [
                    'order_id' => $order->order_id,
                    'ordered_at' => $order->ordered_at->toDateTimeString(),
                    'overall_status' => $order->status,
                    'shops' => $shops->values(),
                ];
            });

        return response()->json($orders);
    }

    public function sellerOrders(Request $request)
    {
        $sellerId = $request->user()->user_id;

        $orders = Order::with(['items.product', 'sellerStatuses' => fn($q) => $q->where('seller_id', $sellerId)])
            ->whereHas('sellerStatuses', fn($q) => $q->where('seller_id', $sellerId))
            ->orderBy('ordered_at', 'desc')
            ->get()
            ->map(function ($order) use ($sellerId) {
                $items = $order->items->filter(fn($i) => $i->product->seller_id === $sellerId);

                return [
                    'order_id'     => $order->order_id,
                    'customer_name' => $order->buyer->full_name,
                    'ordered_at'   => $order->ordered_at->toDateTimeString(),
                    'status' => $order->sellerStatuses->firstWhere('seller_id', $sellerId)?->status ?? 'pending',
                    'isManager'     => true,
                    'items'        => $items->map(fn($i) => [
                        'order_items_id' => $i->order_items_id,
                        'product_id'    => $i->product_id,
                        'product_name'  => $i->product->product_name,
                        'product_img'   => $i->product->product_img,
                        'quantity'      => $i->quantity,
                        'price_each'    => $i->price_each,
                    ])->values(),
                ];
            });

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate(['status' => 'required|in:delivered,completed']);
        $sellerId = $request->user()->user_id;

        // update my pivot
        $pivot = OrderSeller::where([
            'order_id' => $orderId,
            'seller_id' => $sellerId
        ])->firstOrFail();
        
        $pivot->status = $request->status;
        $pivot->save();

        // if every seller for that order is now “completed”, finalize the order
        $all = OrderSeller::where('order_id', $orderId)->pluck('status')->unique();
        if ($all->count() === 1 && $all->first() === 'completed') {
            $order = Order::find($orderId);
            $order->status = 'completed';
            $order->save();
        }

        return response()->json(['message' => 'Status updated']);
    }
}
