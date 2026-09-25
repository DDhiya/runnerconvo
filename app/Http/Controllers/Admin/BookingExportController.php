<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\BookingFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingExportController extends Controller
{
    private const HEADER = [
        'Reference', 'Created', 'Status', 'Full name', 'Matric', 'Phone', 'Email', 'Programme level',
        'Faculty', 'Robe size', 'Session', 'Delivery', 'Address', 'Runner', 'Paid',
        'Paid at', 'Payment method', 'Payment reference', 'Amount (RM)', 'Notes', 'Admin notes', 'Language',
    ];

    public function __invoke(Request $request): StreamedResponse
    {
        $filters = BookingFilters::fromRequest($request);
        $query = $filters->apply(Booking::query())
            ->with(['faculty', 'robeSize', 'convocationSession', 'runner'])
            ->latest()->latest('id');

        // Every admin can see everything, so this record of who took a copy is the cheap
        // part of accountability. No personal data goes into the log line itself.
        Log::notice('bookings.exported', [
            'user' => $request->user()->email,
            'filters' => $filters->active(),
            'rows' => (clone $query)->count(),
        ]);

        $timezone = config('jubahrunner.timezone');
        $filename = 'jubahpanda-bookings-'.now()->timezone($timezone)->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query, $timezone) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Malay names correctly.
            fputcsv($out, self::HEADER, ',', '"', '');

            foreach ($query->lazy(200) as $booking) {
                fputcsv($out, array_map(self::safe(...), $this->row($booking, $timezone)), ',', '"', '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return list<mixed> */
    private function row(Booking $b, string $timezone): array
    {
        return [
            $b->reference,
            $b->created_at->timezone($timezone)->format('Y-m-d H:i'),
            $b->status->label(),
            $b->full_name,
            $b->matric_no,
            $b->phone,
            $b->email,
            $b->programme_level,
            $b->faculty->label_en,
            $b->robeSize->label_en,
            $b->convocationSession->label_en,
            $b->delivery_method === 'cod' ? 'Kuantan COD' : 'Self-pickup',
            $b->delivery_address,
            $b->runner?->name,
            $b->paid_at ? 'Yes' : 'No',
            $b->paid_at?->timezone($timezone)->format('Y-m-d H:i'),
            $b->payment_method,
            $b->payment_reference,
            number_format($b->amount_sen / 100, 2, '.', ''),
            $b->notes,
            $b->admin_notes,
            $b->locale,
        ];
    }

    /**
     * Spreadsheet formula injection: names and notes are typed by the public, and Excel runs
     * a cell that starts with = + - @ (or a tab or CR) as a formula. Prefix those with a quote.
     */
    private static function safe(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
