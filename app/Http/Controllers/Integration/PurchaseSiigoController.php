<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ExportPurchaseSiigoJob;
use Carbon\Carbon;

class PurchaseSiigoController extends Controller
{
    public function export_purchase()
    {
        return view('integration.export_purchase');
    }

    public function export_purchase_download(Request $request)
    {
        $validated = $request->validate([
            'emails' => 'nullable|array|min:1',
            'emails.*' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@revent\.com\.co$/'],
            'month' => 'nullable|date_format:Y-m',
            'created_start' => 'nullable|date|required_with:created_end',
            'created_end' => 'nullable|date|required_with:created_start|after_or_equal:created_start',
            'page_size' => 'nullable|integer|min:1|max:1000',
        ], [
            'emails.*.regex' => 'El correo :input debe pertenecer al dominio @revent.com.co',
        ]);

        $defaultEmails = [
            'operaciones@revent.com.co',
            'ingenieria@revent.com.co',
            'leanmanagement@revent.com.co',
            'tecnologia@revent.com.co'
        ];

        $emails = $request->input('emails', $defaultEmails);

        // Si mandan rango manual, ese tiene prioridad sobre el mes
        if (!empty($validated['created_start']) && !empty($validated['created_end'])) {
            $createdStart = Carbon::parse($validated['created_start'])->format('Y-m-d H:i:s');
            $createdEnd   = Carbon::parse($validated['created_end'])->format('Y-m-d H:i:s');
        } else {
            $month = $validated['month'] ?? null;

            if ($month) {
                $date = Carbon::createFromFormat('Y-m', $month);
            } else {
                $date = Carbon::now();
            }

            $createdStart = $date->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');
            $createdEnd = $date->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');
        }

        $filters = [
            'created_start' => $createdStart,
            'created_end' => $createdEnd,
            'page_size' => $validated['page_size'] ?? 100,
        ];

        ExportPurchaseSiigoJob::dispatch($filters, $emails);

        return view('integration.export_purchase_download', compact('emails'));
    }
}
