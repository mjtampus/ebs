<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\TransactionResource;
use App\Models\StockMovements;
use Spatie\RouteAttributes\Attributes\Post;


class TransactionController extends Controller
{
    #[Post('/api/transactions', name: 'transaction.store')]

        public function store(Request $request)
        {
            DB::beginTransaction();

            try {
                    $validated = $request->validate([
                        'cashier_id' => [
                            'required',
                            'integer',
                            Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'cashier')),
                        ],
                        'transaction_code' => 'required|string|unique:transactions',
                        'items' => 'required|array|min:1',
                        'items.*.product_id' => 'required|integer|exists:products,id',
                        'items.*.quantity' => 'required|integer|min:1',
                        'items.*.unit_price' => 'required|numeric|min:0.01',
                        'items.*.total_price' => 'required|numeric|min:0.01',
                        'total_amount' => 'required|numeric|min:0.01',
                        'amount_received' => 'required|numeric|min:0.01',
                        'change' => 'required|numeric|min:0',
                    ]);

                // 🔍 Check stock availability
                foreach ($validated['items'] as $item) {
                    $stock = ProductStock::where('product_id', $item['product_id'])->first();

                    if (! $stock) {
                        throw new \Exception("No stock record for product ID: {$item['product_id']}");
                    }

                    if ($stock->stock < $item['quantity']) {
                        throw new \Exception("Insufficient stock for product ID: {$item['product_id']} — Available: {$stock->stock}, Required: {$item['quantity']}");
                    }
                }

                // 💾 Create Transaction
                $transaction = Transaction::create([
                    'cashier_id' => (int) $validated['cashier_id'],
                    'transaction_code' => $validated['transaction_code'],
                    'items' => json_encode($validated['items']),
                    'total_amount' => $validated['total_amount'],
                    'amount_received' => $validated['amount_received'],
                    'change' => $validated['change'],
                ]);

                // 📉 Deduct stock
                foreach ($validated['items'] as $item) {
                    $stock = ProductStock::where('product_id', $item['product_id'])->first();
                    $stock->decrement('stock', $item['quantity']);
                    $stock->sold($item['quantity']);

                    Log::info('Stock updated', [
                        'product_id' => $item['product_id'],
                        'quantity_deducted' => $item['quantity'],
                        'remaining_stock' => $stock->fresh()->stock,
                        'transaction_id' => $transaction->id,
                    ]);
                }

                DB::commit();

                // ✅ Return formatted response
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction completed successfully',
                    'data' => new TransactionResource($transaction),
                ], 201);

            } catch (\Illuminate\Validation\ValidationException $e) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);

            } catch (\Throwable $e) {
                DB::rollBack();

                Log::error('Transaction failed', [
                    'error' => $e->getMessage(),
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
    }
