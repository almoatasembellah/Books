<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'title' => $this['title'],
            'description' => $this['description'],
            'level' => 'Level ' .  $this['level'],
            'cover_image' => asset($this['cover_image']),
            'pdf' => $this['pdf_path'],
            'video' => asset($this['video']),
//            'video_url' => $this['video_url'],
            'categories' => BookCategoryResource::collection($this['categories']),
            'images' => BookImageResource::collection($this['images']),
            'book_header_id' => $this['book_header_id'],
        ];
    }
}
