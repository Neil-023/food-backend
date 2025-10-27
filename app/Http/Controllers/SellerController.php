<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SellerController extends Controller
{

  public function __construct()
  {
    // apply sanctum auth only on index()
    $this->middleware('auth:sanctum')->only('index');
  }
  // GET /api/sellers
  public function index(Request $request)
  {
    // this will always be non‑null because the route is sanctum‑protected
    $myId = $request->user()->user_id;

    $sellers = User::where('role', 'seller')
      // remove the current user
      ->where('user_id', '!=', $myId)
      ->select('user_id', 'username as seller_name', 'shop_name', 'image', 'shop_tagline')
      ->get()
      ->map(function ($seller) {
        $seller->logo_url = $seller->image
          ? url('images/logos/' . $seller->image)
          : null;
        return $seller;
      });

    return response()->json($sellers);
  }


  // GET /api/sellers/{seller}/products
  public function products($sellerId)
  {
    $products = Product::where('seller_id', $sellerId)
      ->select('product_id', 'product_name', 'product_desc', 'product_img as image', 'price', 'avail_stocks as stock', 'category_id')
      ->get();

    // Debugging: Log the products being returned
    Log::info('Products for seller ' . $sellerId . ':', $products->toArray());

    return response()->json($products);
  }

  public function applySeller(Request $request)
  {
    $user = $request->user();

    $data = $request->validate([
      'shop_name'    => 'required|string|max:255',
      'shop_tagline' => 'required|string|max:500',
      'logo'         => 'required|image|',
    ]);

    // store the uploaded logo into storage/app/public/logos
    $path = $request->file('logo')->store('logos', 'public');

    // update the user record
    $user->role         = 'seller';
    $user->shop_name    = $data['shop_name'];
    $user->shop_tagline = $data['shop_tagline'];
    $user->image        = $path;
    $user->save();

    return response()->json([
      'message'  => 'Application successful',
      'user'     => $user->fresh(),
    ]);
  }
}
