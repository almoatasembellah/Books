<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'title' => $this['title'],
            'description' => $this['description'],
            'level' => 'Level ' . $this['level'],
            'cover_image' => asset($this['cover_image']),
            'pdf' => $this->getPdfLink(),
            'video' => $this->getVideoLink(),
            'categories' => BookCategoryResource::collection($this['categories']),
            'images' => BookImageResource::collection($this['images']),
            'book_header_id' => $this['book_header_id'],
        ];
    }

    private function getPdfLink()
    {
        if ($this['pdf_path']) {
            return asset('storage/' . $this['pdf_path']);
        }
        return null;
    }

    private function getVideoLink()
    {
        if ($this['video']) {
            return asset('storage/' . $this['video']);
        }
        return null;
    }
}
