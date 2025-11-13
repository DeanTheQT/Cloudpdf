<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Thesis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Http;

class ThesisController extends Controller
{
    public function upload(Request $request)
    {
        Log::info('Incoming request files:', $request->allFiles());
        Log::info('Incoming request all data:', $request->all());

        try {
            // 1️⃣ Validate uploaded file
            $request->validate([
                'pdf' => 'required|mimes:pdf|max:10240', // 10MB max
            ]);

            $file = $request->file('pdf');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('theses', $filename, 'public');


            // 2️⃣ Parse PDF text
            $text = '';
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($file->getPathname());
                $text = $pdf->getText();
            } catch (\Exception $e) {
                Log::error('PDF parsing failed: ' . $e->getMessage());
            }

            if (empty(trim($text))) {
                return response()->json([
                    'message' => 'No text could be extracted from PDF.'
                ], 400);
            }

            // 3️⃣ Use Gemini to validate, summarize, and title
            $geminiApiKey = env('GEMINI_API_KEY'); // 🔹 add this line to your .env file
            $model = 'models/gemini-2.5-flash-lite';
            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/$model:generateContent?key=$geminiApiKey";

            $trimmedText = substr($text, 0, 3000); // keep payload small

            // 🔸 Step 1: Check if the file is a thesis
            $checkPrompt = "You are an academic checker. Determine if the following text belongs to an academic thesis. Respond only 'yes' or 'no'. Text:\n$trimmedText";

            $checkResponse = Http::post($geminiUrl, [
                'contents' => [[
                    'parts' => [['text' => $checkPrompt]]
                ]]
            ]);

            $checkJson = $checkResponse->json();
            $checkText = strtolower($checkJson['candidates'][0]['content']['parts'][0]['text'] ?? 'no');

            if (strpos($checkText, 'yes') === false) {
                return response()->json([
                    'message' => 'This file does not appear to be a thesis document.',
                    'ai_response' => $checkText,
                ], 400);
            }

            // 🔸 Step 2: Generate title and summary
            // For title
            $titlePrompt = "Generate a concise academic thesis title (max 100 characters) for this text, keep in mind to only suggest one title:\n$trimmedText";

            // For summary
            $summaryPrompt = "Summarize this academic thesis text in 3-5 sentences, keep under 500 characters:\n$trimmedText";
            $summaryResponse = Http::post($geminiUrl, [
                'contents' => [[
                    'parts' => [['text' => $summaryPrompt]]
                ]]
            ]);
            $titleResponse = Http::post($geminiUrl, [
                'contents' => [[
                    'parts' => [['text' => $titlePrompt]]
                ]]
            ]);

            $summary = $summaryResponse->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'No summary generated.';
            $title = $titleResponse->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'Untitled Thesis';

            // 4️⃣ Save to DB
            $thesis = Thesis::create([
                'title' => substr($title, 0, 255),
                'summary' => trim($summary),
                'content' => $text,
                'file_path' => $filePath,
                'user_id' => 1, // static for now
            ]);

            return response()->json(['thesis' => $thesis]);

        } catch (\Exception $e) {
            Log::error('Thesis upload failed: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'message' => 'Upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
     public function index(Request $request)
    {
        // Optionally, fetch theses for logged-in user only
        $user = $request->user();
        if ($user) {
            $theses = Thesis::where('user_id', $user->id)->get();
        } else {
            $theses = Thesis::all(); // or empty array for guests
        }

        return response()->json(['theses' => $theses]);
    }

    public function download($id)
    {
        $thesis = Thesis::find($id);

        if (!$thesis) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $filePath = storage_path('app/public/' . $thesis->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($filePath, $thesis->title . '.pdf');
    }

}
