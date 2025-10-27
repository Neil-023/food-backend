<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use App\Models\OrderSeller;
class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:product_tbl,product_id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $userId = $user->user_id;                // ← use user_id, not id

        // find or create the cart
        $cart = DB::table('cart_tbl')->where('user_id', $userId)->first();

        if (!$cart) {
            $cartId = DB::table('cart_tbl')->insertGetId([
                'user_id'    => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $cartId = $cart->cart_id;
        }

        $cartItem = CartItem::firstOrNew([
            'cart_id'    => $cartId,
            'product_id' => $request->product_id,
        ]);

        $cartItem->quantity = ($cartItem->exists ? $cartItem->quantity : 0) + $request->quantity;
        $cartItem->save();

        return response()->json([
            'message'   => 'Item added to cart successfully',
            'cart_id'   => $cartId,
            'cart_item' => $cartItem,
        ], 200);
    }

    public function viewCart(Request $request)
    {
        $user = $request->user();                // authenticated user
        $userId = $user->user_id;

        // find their cart
        $cart = DB::table('cart_tbl')
            ->where('user_id', $userId)
            ->first();

        if (!$cart) {
            return response()->json([], 200);
        }

        // join cart_items → products → sellers
        $rows = DB::table('cart_items_tbl as ci')
            ->join('product_tbl as p', 'ci.product_id', '=', 'p.product_id')
            ->join('users_tbl as s', 'p.seller_id', '=', 's.user_id')
            ->where('ci.cart_id', $cart->cart_id)
            ->select([
                's.user_id as seller_id',
                's.shop_name as seller_name',
                'p.product_id',
                'p.product_name',
                'p.product_img',
                'p.price',
                'ci.quantity',
            ])
            ->get();

        $grouped = $rows
            ->groupBy('seller_id')
            ->map(function ($items, $sellerId) {
                return [
                    'seller_id'   => (int)$sellerId,
                    'seller_name' => $items->first()->seller_name,
                    'items'       => $items->map(function ($i) {
                        return [
                            'product_id'   => $i->product_id,
                            'product_name' => $i->product_name,
                            // turn the stored path into a full URL:
                            'product_img'  => $i->product_img
                                ? URL::to('/storage/' . $i->product_img)
                                : null,
                            'price'        => (float)$i->price,
                            'quantity'     => (int)$i->quantity,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json($grouped, 200);
    }

    public function updateQuantity(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $user   = $request->user();
        $cart   = DB::table('cart_tbl')->where('user_id', $user->user_id)->first();
        if (!$cart) {
            return response()->json(['error' => 'no cart'], 404);
        }

        // Update or remove
        if ($request->quantity > 0) {
            DB::table('cart_items_tbl')
                ->where('cart_id', $cart->cart_id)
                ->where('product_id', $productId)
                ->update(['quantity' => $request->quantity]);
        } else {
            // if zero, delete the row
            DB::table('cart_items_tbl')
                ->where('cart_id', $cart->cart_id)
                ->where('product_id', $productId)
                ->delete();
        }

        // return the fresh grouped cart
        return $this->viewCart($request);
    }

    public function checkout(Request $request)
    {
        $userId = $request->user()->user_id;

        DB::beginTransaction();
        try {
            // find or create cart
            $cart = Cart::firstOrCreate(
                ['user_id' => $userId],
                ['created_at' => now(), 'updated_at' => now()]
            );

            // fetch cart items with product info
            $cartItems = CartItem::where('cart_id', $cart->cart_id)
                ->with('product')
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Your cart is empty, cannot checkout'], 400);
            }

            // Check stock for each item
            foreach ($cartItems as $item) {
                $product = $item->product;
                if ($product->avail_stocks < $item->quantity) {
                    throw new \Exception("Not enough stock for {$product->product_name}. Available: {$product->avail_stocks}, requested: {$item->quantity}");
                }
            }

            // Deduct stock
            foreach ($cartItems as $item) {
                $product = $item->product;
                $product->avail_stocks -= $item->quantity;
                $product->save();
            }

            $total = $cartItems->sum(fn($i) => $i->quantity * $i->product->price);

            // Create order
            $order = Order::create([
                'buyer_id'    => $userId,
                'status'      => 'Pending',
                'total_price' => $total,
                'ordered_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Move items to order_items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id'   => $order->order_id,
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'price_each' => $item->product->price,
                ]);
            }

            $sellerGroups = $cartItems->groupBy(fn($item) => $item->product->seller_id);

            // Insert into order_seller_tbl
            foreach ($sellerGroups as $sellerId => $items) {
                OrderSeller::create([
                    'order_id'  => $order->order_id,
                    'seller_id' => $sellerId,
                    'status'    => 'pending',
                ]);
            }
            // Clear cart
            CartItem::where('cart_id', $cart->cart_id)->delete();

            DB::commit();
            return response()->json(['message' => 'Checkout successful'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Checkout failed', [
                'exception' => $e,
                'user_id' => $userId,
            ]);
            return response()->json([
                'message' => 'Checkout failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
