<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Post;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    #[Post('/api/transactions', name: 'transaction.store')]
    public function store(Request $request)
    {
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
                'total_amount' => 'required|numeric|min:0.01',
                'amount_received' => 'required|numeric|min:0.01',
                'change' => 'required|numeric|min:0'
            ]);

            // Use the validated cashier_id from request, not auth()->id()
            $transaction = Transaction::create([
                'cashier_id' => (int)$validated['cashier_id'], // Force integer type
                'transaction_code' => $validated['transaction_code'],
                'items' => json_encode($validated['items']),
                'total_amount' => $validated['total_amount'],
                'amount_received' => $validated['amount_received'],
                'change' => $validated['change'],
            ]);

            return response()->json([
                'success' => true,
                'id' => $transaction->id,
                'message' => 'Transaction completed'
            ]);

        } catch (\Exception $e) {
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