<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print_receipt')
                ->label('Print Receipt')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(fn() => $this->printReceipt()),

            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn() => $this->downloadPdf()),
        ];
    }

    private function printReceipt()
    {
        $transaction = $this->record;
        $items = json_decode($transaction->items, true);

        // Get product details for each item
        $itemsWithDetails = collect($items)->map(function ($item) {
            $product = Product::find($item['product_id']);
            return [
                'product_name' => $product ? $product->name : 'Product Not Found',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'] ?? ($item['quantity'] * $item['unit_price']),
            ];
        });

        $receiptHtml = $this->generateReceiptHtml($transaction, $itemsWithDetails);

        // Use JavaScript to open a new window and print
        $this->js("
            const printWindow = window.open('', '_blank', 'width=300,height=600');
            printWindow.document.write(`{$receiptHtml}`);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        ");
    }

    private function generateReceiptHtml($transaction, $items): string
    {
        $storeName = config('app.name', 'Elizabeth Bakery');
        $storeAddress = 'Cordova Bangbang';
        $storePhone = '09248755563';

        $receiptHtml = "
        <html>
        <head>
            <title>Receipt - {$transaction->transaction_code}</title>
            <style>
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    width: 100%;
                    margin: 0;
                    padding: 10px;
                    line-height: 1.3;
                }
                .center { text-align: center; }
                .left { text-align: left; }
                .right { text-align: right; }
                .bold { font-weight: bold; }
                .line { border-bottom: 1px dashed #000; margin: 5px 0; }
                .double-line { border-bottom: 2px solid #000; margin: 5px 0; }
                .item-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 2px 0;
                }
                .no-margin { margin: 0; }
                @media print {
                    body { -webkit-print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            <div class='center bold'>
                <div>{$storeName}</div>
                <div style='font-size: 10px;'>{$storeAddress}</div>
                <div style='font-size: 10px;'>{$storePhone}</div>
            </div>
            
            <div class='line'></div>
            
            <div class='center'>
                <div>Receipt #: {$transaction->transaction_code}</div>
                <div>Date: {$transaction->created_at->format('M d, Y h:i A')}</div>
                <div>Cashier: {$transaction->cashier->name}</div>
            </div>
            
            <div class='double-line'></div>
            
            <div>
                <div class='bold' style='display: flex; justify-content: space-between;'>
                    <span>Item</span>
                    <span>Total</span>
                </div>";

        foreach ($items as $item) {
            $receiptHtml .= "
                <div style='margin: 3px 0;'>
                    <div>{$item['product_name']}</div>
                    <div style='display: flex; justify-content: space-between; font-size: 11px;'>
                        <span>{$item['quantity']} x PHP " . number_format($item['unit_price'], 2) . "</span>
                        <span>PHP " . number_format($item['total_price'], 2) . "</span>
                    </div>
                </div>";
        }

        $receiptHtml .= "
            </div>
            
            <div class='line'></div>
            
            <div style='display: flex; justify-content: space-between;'>
                <span class='bold'>TOTAL:</span>
                <span class='bold'>PHP " . number_format($transaction->total_amount, 2) . "</span>
            </div>
            
            <div style='display: flex; justify-content: space-between;'>
                <span>Cash:</span>
                <span>PHP " . number_format($transaction->amount_received, 2) . "</span>
            </div>
            
            <div style='display: flex; justify-content: space-between;'>
                <span>Change:</span>
                <span>PHP " . number_format($transaction->change, 2) . "</span>
            </div>
            
            <div class='double-line'></div>
            
            <div class='center' style='font-size: 10px; margin-top: 10px;'>
                <div>Thank you for your purchase!</div>
                <div>Come again soon!</div>
                <div style='margin-top: 10px;'>Powered by Your POS System</div>
            </div>
            
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                }
            </script>
        </body>
        </html>";

        // Escape for JavaScript
        return addslashes($receiptHtml);
    }
    private function downloadPdf()
    {
        $transaction = $this->record;
        $items = json_decode($transaction->items, true);

        $itemsWithDetails = collect($items)->map(function ($item) {
            $product = Product::find($item['product_id']);
            return [
                'product_name' => $product ? $product->name : 'Product Not Found',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'] ?? ($item['quantity'] * $item['unit_price']),
            ];
        });

        $html = $this->generateReceiptHtmlForPdf($transaction, $itemsWithDetails);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper([0, 0, 226.77, 600], 'portrait'); // ~80mm receipt size

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            "receipt-{$transaction->transaction_code}.pdf"
        );
    }

    private function generateReceiptHtmlForPdf($transaction, $items): string
    {
        $storeName = config('app.name', 'Elizabeth Bakery');
        $storeAddress = 'Cordova Bangbang';
        $storePhone = '09248755563';

        $html = "
    <html>
    <head>
        <style>
            body {
                font-family: 'Courier New', monospace;
                font-size: 12px;
                width: 100%;
                margin: 0;
                padding: 5px;
                line-height: 1.3;
            }
            .center { text-align: center; }
            .bold { font-weight: bold; }
            .line { border-bottom: 1px dashed #000; margin: 5px 0; }
            .double-line { border-bottom: 2px solid #000; margin: 5px 0; }
            .flex-between { display: flex; justify-content: space-between; }
        </style>
    </head>
    <body>
        <div class='center bold'>
            <div>{$storeName}</div>
            <div style='font-size: 10px;'>{$storeAddress}</div>
            <div style='font-size: 10px;'>{$storePhone}</div>
        </div>

        <div class='line'></div>

        <div class='center'>
            <div>Receipt #: {$transaction->transaction_code}</div>
            <div>Date: {$transaction->created_at->format('M d, Y h:i A')}</div>
            <div>Cashier: {$transaction->cashier->name}</div>
        </div>

        <div class='double-line'></div>

        <div class='bold flex-between'>
            <span>Item</span>
            <span>Total</span>
        </div>";

        foreach ($items as $item) {
            $html .= "
        <div>
            <div>{$item['product_name']}</div>
            <div class='flex-between' style='font-size: 11px;'>
                <span>{$item['quantity']} x PHP " . number_format($item['unit_price'], 2) . "</span>
                <span>PHP " . number_format($item['total_price'], 2) . "</span>
            </div>
        </div>";
        }

        $html .= "
        <div class='line'></div>

        <div class='flex-between bold'>
            <span>TOTAL:</span>
            <span>PHP " . number_format($transaction->total_amount, 2) . "</span>
        </div>

        <div class='flex-between'>
            <span>Cash:</span>
            <span>PHP " . number_format($transaction->amount_received, 2) . "</span>
        </div>

        <div class='flex-between'>
            <span>Change:</span>
            <span>PHP " . number_format($transaction->change, 2) . "</span>
        </div>

        <div class='double-line'></div>

        <div class='center' style='font-size: 10px; margin-top: 10px;'>
            <div>Thank you for your purchase!</div>
            <div>Come again soon!</div>
            <div style='margin-top: 10px;'>Powered by Your POS System</div>
        </div>
    </body>
    </html>";

        return $html;
    }


}

