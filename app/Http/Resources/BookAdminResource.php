<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookAdminResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'title' => $this['title'],
            'description' => $this['description'],
            'level' => 'Level ' .  $this['level'],
            'cover_image' => asset($this['cover_image']),
            'pdfs' => $this->getPdfLinks(),
            'videos' => $this->getVideoLinks(),
            'categories' => BookCategoryResource::collection($this['categories']),
            'images' => BookImageResource::collection($this['images']),
            'book_header_id' => $this['book_header_id'],
        ];
    }
    private function getPdfLinks()
    {
        if ($this->pdfs) {
            return BookPdfResource::collection($this->pdfs);
        }
        return null;
    }

    private function getVideoLinks()
    {
        if ($this->videos) {
            return BookVideoResource::collection($this->videos);
        }
        return null;
    }
}
