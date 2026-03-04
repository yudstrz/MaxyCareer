<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use Exception;
use Illuminate\Support\Facades\Log;

class JobMatchingController extends Controller
{
    public function index()
    {
        return view('job-matching');
    }

    public function extract(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        try {
            $file = $request->file('cv');
            $text = $this->extractText($file);

            if (empty(trim($text))) {
                return response()->json(['error' => 'Could not extract text from the file.'], 422);
            }

            return response()->json(['text' => $text]);
        }
        catch (Exception $e) {
            Log::error('Extraction Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Extraction failed: ' . $e->getMessage()], 500);
        }
    }

    private function extractText($file)
    {
        $extension = $file->getClientOriginalExtension();
        $text = '';

        if ($extension === 'pdf') {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();
        }
        elseif ($extension === 'docx') {
            $phpWord = IOFactory::load($file->getPathname());
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
        }

        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = str_replace("\0", '', $text);

        return $text;
    }
}
