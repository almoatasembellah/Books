<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Resources\BookAdminResource;
use App\Http\Resources\BookResource;
use App\Http\Resources\SerialResource;
use App\Http\Traits\HandleApi;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookImage;
use App\Models\BookPdf;
use App\Models\BookVideo;
use App\Models\Category;
use App\Models\Serial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;



class BookController extends Controller
{
    use HandleApi;

    public function index()
    {
        $user = request()->user();
        if ($user->hasRole('admin')) {
            return $this->sendResponse(BookAdminResource::collection(Book::paginate(25)), 'All Books are fetched for admin');
        }
    }

    //books for user
    public function getAllBooksForUsers()
    {
        $books = Book::paginate(25);
        return $this->sendResponse(BookResource::collection($books),'All Books are fetched for users');
    }


    public function store(BookRequest $request)
    {
        $data = $request->validated();

        $category = Category::findOrFail($data['category_id']);

        if (!$category) {
            return self::sendError('Category not found.', [], 404);
        }

        // Store the cover image
        $coverPath = $request->file('cover_image')->store('book-covers', 'public');
        $data['cover_image'] = asset('storage/' . $coverPath); // Get the full URL for the cover image

        // Create the book with validated data
        $data['serial_code'] = \Str::uuid();
        $book = Book::create($data);

        // Now, create the BookCategory record using the existing category_id
        BookCategory::create([
            'category_id' => $category->id,
            'book_id' => $book->id
        ]);

        // Store the videos
        if ($request->hasFile('video')) {
            foreach ($request->file('video') as $video) {
                $videoPath = $video->store('book-videos', 'public');
                BookVideo::create([
                    'path' => asset('storage/' . $videoPath), // Get the full URL for each video
                    'book_id' => $book->id,
                ]);
            }
        }

        // Store the PDFs
        if ($request->hasFile('pdf')) {
            foreach ($request->file('pdf') as $pdf) {
                $pdfPath = $pdf->store('book-pdfs', 'public');
                BookPdf::create([
                    'path' => asset('storage/' . $pdfPath), // Get the full URL for each PDF
                    'book_id' => $book->id,
                ]);
            }
        }

        // Store the images
        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('book-images', 'public');
                BookImage::create([
                    'path' =>  $imagePath, // Get the full URL for each image
                    'book_id' => $book->id
                ]);
            }
        }

//        return 'x';
        return self::sendResponse(BookAdminResource::make($book), 'Book is created successfully');
    }



    //Admin get book by id

    public function show(string $id)
    {
        $book = Book::findOrFail($id);
        return $this->sendResponse(BookResource::make($book), 'Book data is fetched successfully');
    }

    //User Get book by id

    public function showSpecificBook(string $id)
    {
        $book = Book::findOrFail($id);
        return $this->sendResponse(BookResource::make($book), 'Book data is fetched successfully');
    }


    public function update(BookRequest $request, Book $book)
    {
        $data = $request->validated();

        $category = Category::findOrFail($data['category_id']);

        if (!$category) {
            return self::sendError('Category not found.', [], 404);
        }

        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('book-covers', 'public');
            $data['cover_image'] = asset('storage/' . $coverPath); // Get the full URL for the updated cover image
        }

        if ($request->has('pdf')) {
            $pdfPaths = [];
            foreach ($request->file('pdf') as $pdf) {
                $pdfPath = $pdf->store('book-pdfs', 'public');
                $pdfPaths[] = $pdfPath;
            }
            $data['pdf_path'] = $pdfPaths;
        }

        if ($request->has('video')) {
            $videoPaths = [];
            foreach ($request->file('video') as $video) {
                $videoPath = $video->store('book-videos', 'public');
                $videoPaths[] = $videoPath;
            }
            $data['video'] = $videoPaths; // Get the full URL for the updated videos
        }

        $book->update($data);

        // Update the BookCategory record using the existing category_id
        $book->categories()->sync([$category->id]);

        if ($request->has('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('book-images', 'public');
                BookImage::create([
                    'path' => asset('storage/' . $imagePath), // Get the full URL for the image
                    'book_id' => $book->id
                ]);
            }
        }

        return self::sendResponse(BookAdminResource::make($book), 'Book is updated successfully');
    }



    public function downloadFile(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $isTeacher = $book->categories()->whereBookHeaderId(4)->exists();

        if ($isTeacher) {
            $this->validate(request(), [
                'name' => 'required|string',
                'email' => 'required|email',
                'phone' => 'required|string',
                'position' => 'required|string',
            ]);

            $phone = request('phone');
            $user = User::where('phone', $phone);

            if (!$user->exists()) {
                $userData = User::create($request->all());
            }
        } else {
            $this->validate(request(), [
                'material_code' => 'required|string',
            ]);

            $check = Serial::where('material_code', $request->input('material_code'))->firstOrFail();
            $serialCode = $request->input('material_code');

            if ($serialCode !== $check->material_code) {
                return $this->sendError('Serial Error', 'Invalid Serial Code, Try another one.');
            }
        }

        // Create an array to store the links
        $links = [];

        // Add the PDF link if it exists
        if ($book->pdf_path) {
            $pdfLink = asset('storage/'.$book->pdf_path);
            $links['pdf'] = $pdfLink;
        }

        // Add the video link if it exists
        if ($book->video) {
            $videoLink = asset($book->video);
            $links['video'] = $videoLink;
        }

        // Check if any links were found, and send the response accordingly
        if (empty($links)) {
            return $this->sendError('File not found', 'No PDF or video link available.', 404);
        }

        return $this->sendResponse($links, 'Download links for PDF and video');
    }




    //Serial Code Generation and Fetching them


    public function generateSerialCodes(Request $request)
    {
        $bookId = $request->input('book_id');
        $quantity = $request->input('quantity');
//        $book = Book::findOrFail($bookId);
        $serialCodes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $serialCode = $this->generateUniqueSerialCode();

            // Associate serial code with the book
            $serialCodes[] = [
                'book_id' => $bookId,
                'material_code' => $serialCode
            ];
        }

        // Insert serial codes into the database
        Serial::insert($serialCodes);
        return response()->json($serialCodes);
    }


    private function generateUniqueSerialCode()
    {
        do {
//            $serialCode = Str::random(10);
            $serialCode = random_int(100000000,999999999);
        } while (Serial::where('material_code', $serialCode)->exists());
        return $serialCode;
    }

    public function specificGeneratedCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors()->first(), 400);
        }

        $bookId = $request->input('book_id');
        $serialCodes = Serial::where('book_id', $bookId)->get(['material_code']);

        $response = [
          'book_id' => $bookId,
            'serial_codes' => $serialCodes
        ];

        return $this->sendResponse($response, 'Generated serial codes for the book');
    }


    public function generatedCodes()
    {
        return self::sendResponse(SerialResource::collection(Serial::paginate(25)),'all Serials are fetched.');
    }

    public function destroy($id)
    {
         Book::findOrFail($id)->delete();
        return $this->Sendresponse([], 'Book has been deleted successfully');
    }
}
