<?php

namespace App\Http\Resources\Api\Integration\TransferApproval;

use Illuminate\Http\Resources\Json\JsonResource;

/** La aprobación se encoló en ImportMasiveTransferSiigoJob; el resultado llega por correo a la bodega. */
class TransferApprovalQueuedResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'approval' => [
                'code' => $this->resource['code'],
                'email' => $this->resource['email'],
                'units' => $this->resource['units'],
            ],
        ];
    }
}
