<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\RouteAttributes\Attributes\Post;
use Illuminate\Validation\Rule;

// class TransactionController extends Controller
// {
//     #[Post('/api/transactions', name: 'transaction.store')]
//     public function store(Request $request)
//     {
//         try {
//             $validated = $request->validate([
//                 'cashier_id' => [
//                     'required',
//                     'integer',
//                     Rule::exists('users', 'id')->where(function ($query) {
//                         $query->where('role', 'cashier'); // Ensure cashier exists
//                     })
//                 ],
//                 'transaction_code' => 'required|string|unique:transactions',
//                 'items' => 'required|array|min:1',
//                 'total_amount' => 'required|numeric|min:0.01',
//                 'amount_received' => 'required|numeric|min:0.01',
//                 'change' => 'required|numeric|min:0'
//             ]);

//             // Use the validated cashier_id from request, not auth()->id()
//             $transaction = Transaction::create([
//                 'cashier_id' => (int)$validated['cashier_id'], // Force integer type
//                 'transaction_code' => $validated['transaction_code'],
//                 'items' => json_encode($validated['items']),
//                 'total_amount' => $validated['total_amount'],
//                 'amount_received' => $validated['amount_received'],
//                 'change' => $validated['change'],
//             ]);

//             return response()->json([
//                 'success' => true,
//                 'id' => $transaction->id,
//                 'message' => 'Transaction completed'
//             ]);

//         } catch (\Exception $e) {
//             \Log::error('Transaction failed', [
//                 'error' => $e->getMessage(),
//                 'payload' => $request->all()
//             ]);
            
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage()
//             ], 500);
//         }
//     }
// }

class TransactionController extends Controller
{
    #[Post('/api/transactions', name: 'transaction.store')]
    public function store(Request $request)
    {
        // Start database transaction
        DB::beginTransaction();
        
        try {
            $validated = $request->validate([
                'cashier_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(function ($query) {
                        $query->where('role', 'cashier'); // Ensure cashier exists
                    })
                ],
                'transaction_code' => 'required|string|unique:transactions',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0.01',
                'items.*.total_price' => 'required|numeric|min:0.01',
                'total_amount' => 'required|numeric|min:0.01',
                'amount_received' => 'required|numeric|min:0.01',
                'change' => 'required|numeric|min:0'
            ]);

            // Check stock availability for all items first
            foreach ($validated['items'] as $item) {
                $productStock = ProductStock::where('product_id', $item['product_id'])->first();
                
                if (!$productStock) {
                    throw new \Exception("Product stock record not found for product ID: " . $item['product_id']);
                }
                
                if ($productStock->stock < $item['quantity']) {
                    // You might want to get product name for better error message
                    throw new \Exception("Insufficient stock for product ID: " . $item['product_id'] . 
                        ". Available: " . $productStock->stock . ", Required: " . $item['quantity']);
                }
            }

            // Create the transaction record
            $transaction = Transaction::create([
                'cashier_id' => (int)$validated['cashier_id'],
                'transaction_code' => $validated['transaction_code'],
                'items' => json_encode($validated['items']),
                'total_amount' => $validated['total_amount'],
                'amount_received' => $validated['amount_received'],
                'change' => $validated['change'],
            ]);

            // Deduct stock for each item
            foreach ($validated['items'] as $item) {
                $productStock = ProductStock::where('product_id', $item['product_id'])->first();
                
                // Deduct the quantity from stock
                $productStock->decrement('stock', $item['quantity']);
                
                // Optional: Log stock changes for audit trail
                \Log::info('Stock deducted', [
                    'product_id' => $item['product_id'],
                    'quantity_deducted' => $item['quantity'],
                    'remaining_stock' => $productStock->fresh()->stock,
                    'transaction_id' => $transaction->id
                ]);
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'id' => $transaction->id,
                'message' => 'Transaction completed and stock updated'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Transaction failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}