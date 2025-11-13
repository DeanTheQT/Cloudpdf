<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
   public function index() {
        return response()->json([
        ['id' => 1, 'title' => 'First Post', 'content' => 'This is from Laravel'],
        ['id' => 2, 'title' => 'Second Post', 'content' => 'Laravel React Integration Works!']
        ]);
    }
}
