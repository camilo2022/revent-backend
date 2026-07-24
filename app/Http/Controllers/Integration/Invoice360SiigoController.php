<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ExportInvoice360SiigoJob;
use Carbon\Carbon;

class Invoice360SiigoController extends Controller
{
    public function export_invoice_360(Request $request)
    {
        $emails = [
            'operaciones@revent.com.co',
            'ingenieria@revent.com.co',
            'leanmanagement@revent.com.co',
            'tecnologia@revent.com.co'
        ];

        $month = $request->input('month');

        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $createdStart = $date->copy()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');
            $createdEnd = $date->copy()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $createdStart = Carbon::now()->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');
            $createdEnd = Carbon::now()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');
        }

        $filters = [
            'created_start' => $request->input('created_start', $createdStart),
            'created_end' => $request->input('created_end', $createdEnd),
            'page_size' => $request->input('page_size', 100),
        ];

        ExportInvoice360SiigoJob::dispatch(
            $filters,
            $request->input('email', $emails)
        );

        return response()->json([
            'success' => true,
            'message' => '⏳ Exportación de facturas en proceso. Te notificaremos por email cuando esté lista.',
        ]);
    }
}
