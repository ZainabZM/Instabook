<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Author;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\Note;
use App\Models\Synopsis;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $book = Book::all()->sortBy('name');
        $book = Book::paginate(8); // Paginer les résultats
        return view('book.index', compact('book'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = Genre::all()->sortBy('name');
        $author = Author::all()->sortBy('name');
        return view('book.create', compact('genres', 'author'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Book $book)
    {

        $request->validate([
            'title' => 'required',
            'author' => 'required|exists:authors,id',
            'year' => 'required|numeric|integer',
            'genre' => 'required|exists:genres,id',
            'synopsis' => 'required',
            'note' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:3000'
        ]);

        $fileName = time() . '.' . $request->image->extension();
        $request->image->storeAs('public/images', $fileName);

        $user_id = Auth::id();



        $note = Note::create([
            'note' => $request->note,
            'user_id' => $user_id,
            'book_id' => $request->book_id
        ]);

        $synopsis = Synopsis::create([
            'synopsis' => $request->synopsis
        ]);


        $book =  Book::create([
            'title' => $request->title,
            'author_id' => $request->author,
            'year' => $request->year,
            'genre_id' => $request->genre,
            'user_id' => $user_id,
            'synopsis_id' => $synopsis->id,
            'book_id' => $note->book_id,
            'note_id' => $note->id,
            'image' => $fileName
        ]);

        return redirect(route('book.show', $book->id))
            ->with('success', "Le book a été créé");
    }

    public function show(Book $book)
    {
        $book['genre_id'] = $book->getGenre();
        $book['author_id'] = $book->getAuthor();
        $book['note_id'] = $book->getNote();
        $book['synopsis_id'] = $book->getSynopsis();
        // $book['comment_id'] = $book->getComment();

        $note = Note::where('book_id', $book->id)->avg('note');

        return view('book.show')->with([
            'book' => $book,
            'moyenne' => $note
        ]);
    }

    public function edit(Book $book)
    {
        $genres = Genre::all()->sortBy('name');
        $author = Author::all()->sortBy('name');

        return view('book.edit', compact('book', 'genres', 'author'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        $request->validate([
            'title' => 'required',
            'author_id' => 'required|exists:authors,id',
            'year' => 'required',
            'genre_id' => 'required|exists:genres,id',
            'synopsis_id' => 'required',
            'comment_id' => 'required',
            'note_id' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);


        $book->title = ucwords(strtolower($request->title));
        $book->author = $request->author;
        $book->year = $request->year;
        $book->genre = $request->genre;
        $book->synopsis = $request->synopsis;
        $book->comment = $request->comment;
        $book->note = $request->note;
        $book->image = $request->image;


        $book->save();

        return redirect(route('book.show', compact('book')));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();
        return redirect(route('book.index'))
            ->with(['success', 'Livre supprimé avec succès']);
    }

    public function store_note(Request $request, Int $id)
    {
        $book = Book::find($id);
        $request->validate([
            'note' => 'required|integer'
        ]);

        $user_id = Auth::id();
        $book['genre_id'] = $book->getGenre();
        $book['author_id'] = $book->getAuthor();
        $book['note_id'] = $book->getNote();
        $book['synopsis_id'] = $book->getSynopsis();

        $book['note_id'] = $book->getNote();

        $note = Note::create([
            'note' => $request->note,
            'user_id' => $user_id,
            'book_id' => $book->id
        ]);

        $note = Note::where('book_id', $book->id)->avg('note');

        return view('book.show')->with([
            'book' => $book,
            'moyenne' => $note
        ]);
    }
}
