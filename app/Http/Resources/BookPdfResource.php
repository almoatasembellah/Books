<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookPdfResource extends JsonResource
{

    public function toArray(Request $request)
    {
        return [
            'path' =>$this->path,
        ];
    }
}
