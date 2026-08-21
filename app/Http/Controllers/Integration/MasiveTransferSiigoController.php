<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Imports\MasiveTransferSiigoSheetsImport;
use App\Jobs\ImportMasiveTransferSiigoJob;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MasiveTransferSiigoController extends Controller
{
    public function masive_transfer()
    {
        return view('integration.masive_transfer');
    }

    public function masive_transfer_upload(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|mimes:xlsx,xls',
            'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@revent\.com\.co$/'],
        ], [
            'email.regex' => 'El correo :input debe pertenecer al dominio @revent.com.co',
        ]);

        $email = $request->input('email');

        $transfer = Excel::toCollection(new MasiveTransferSiigoSheetsImport, $request->file('file'));

        ImportMasiveTransferSiigoJob::dispatch($transfer, $email);

        return view('integration.masive_transfer_upload', compact('email'));
    }
}
