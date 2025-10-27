<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = auth()->user();

        // Only return products where the seller_id matches the logged-in user
        $products = Product::with('category')
            ->where('seller_id', $user->user_id)
            ->get();

        return response()->json($products);
    }

    public function store(Request $r)
    {
        try {
            $data = $r->validate([
                'product_name' => 'required',
                'product_desc' => 'required',
                'category_id'  => 'required|exists:category_tbl,category_id',
                'price'        => 'required|numeric',
                'avail_stocks' => 'required|integer|min:0',
                'product_img'  => 'nullable|image',
            ]);

            $data['seller_id'] = $r->user()->user_id;

            if ($r->hasFile('product_img')) {
                $path = $r->file('product_img')->store('products', 'public');
                $data['product_img'] = $path;
            }

            $p = Product::create($data);
            return response()->json($p, 201);
            Log::info('CREATED PRODUCT', $data);
        } catch (\Exception $err) {
            return response()->json(['error' => $err->getMessage()], 400);
        }
    }

    public function destroy(Product $product)
    {
        // ensure the logged‑in seller actually owns this product
        if ($product->seller_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $product->delete();
            return response()->json(['message' => 'Deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStock(Request $request, $id)
    {
        $validated = $request->validate([
            'operation' => 'required|in:add,subtract',
            'amount' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($id);

        if ($validated['operation'] === 'add') {
            $product->avail_stocks += $validated['amount'];
        } else {
            $product->avail_stocks = max(0, $product->avail_stocks - $validated['amount']);
        }

        $product->save();

        return response()->json($product);
    }
}
