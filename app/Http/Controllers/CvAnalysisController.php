<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;
use Exception;

class CvAnalysisController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,docx|max:10240',
            'job_description' => 'nullable|string'
        ]);

        try {
            $file = $request->file('cv');
            $text = $this->extractText($file);

            if (empty(trim($text))) {
                return response()->json(['error' => 'Could not extract text from the file.'], 422);
            }

            $jobDescription = $request->input('job_description', 'General modern tech roles (Software Engineering, Data, Product)');

            $analysis = $this->callGroq($text, $jobDescription);

            // Save to database for dynamic stats
            \App\Models\CvAnalysis::create([
                'overall_score' => $analysis['overall_score'],
                'industry' => $analysis['industry'] ?? 'General',
                'job_title' => $analysis['job_title'] ?? null,
            ]);

            return response()->json($analysis);
        }
        catch (Exception $e) {
            Log::error('Analysis Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Analysis failed: ' . $e->getMessage()], 500);
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'cv_text' => 'required|string',
            'analysis' => 'required|array'
        ]);

        try {
            $cvText = $request->input('cv_text');
            $analysis = json_encode($request->input('analysis'));

            $prompt = "You are a professional CV rewriter specializing in ATS optimization.
                Original CV Text:
                $cvText

                Analysis Results:
                $analysis

                TASK: Rewrite the CV to be 100% ATS-friendly. Follow these rules:
                1. Use clear, standard section headings (Professional Summary, Experience, Skills, Education).
                2. Use strong action verbs.
                3. Incorporate missing keywords identified in the analysis naturally.
                4. Maintain factual accuracy from the original CV.
                5. Keep the format clean and professional.

                RETURN ONLY A JSON OBJECT with this structure:
                {
                    \"full_name\": \"...\",
                    \"job_title\": \"...\",
                    \"contact\": {
                        \"email\": \"...\",
                        \"phone\": \"...\",
                        \"location\": \"...\",
                        \"linkedin\": \"...\"
                    },
                    \"summary\": \"...\",
                    \"experience\": [
                        {
                            \"company\": \"...\",
                            \"role\": \"...\",
                            \"duration\": \"...\",
                            \"bullets\": [\"...\", \"...\"]
                        }
                    ],
                    \"skills\": [\"...\", \"...\"],
                    \"education\": [
                        {
                            \"institution\": \"...\",
                            \"degree\": \"...\",
                            \"year\": \"...\"
                        }
                    ]
                }";

            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.groq.model'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert career consultant.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object']
            ]);

            if ($response->failed()) {
                Log::error('Groq Generation API Error: ' . $response->body());
                throw new Exception('Groq API error: ' . $response->body());
            }

            $result = json_decode($response->json('choices.0.message.content'), true);
            return response()->json($result);
        }
        catch (Exception $e) {
            Log::error('Generation Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Generation failed: ' . $e->getMessage()], 500);
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

        // Ensure valid UTF-8 encoding and remove null bytes to prevent JSON encoding errors
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = str_replace("\0", '', $text);

        return $text;
    }

    private function callGroq($text, $jobDescription)
    {
        $prompt = "Analyze this CV for ATS (Applicant Tracking System) compatibility for the following job description:
        JOB DESCRIPTION: $jobDescription

        CV TEXT:
        $text

        PROVIDE ANALYSIS IN JSON FORMAT ONLY with the following structure:
        {
            \"overall_score\": 0-100,
            \"score_label\": \"Excellent|Good|Average|Needs Work\",
            \"industry\": \"Technology|Finance|Healthcare|etc\",
            \"job_title\": \"Software Engineer|Data Analyst|etc\",
            \"summary\": \"...\",
            \"metrics\": {
                \"keyword_match\": 0-100,
                \"formatting\": 0-100,
                \"readability\": 0-100
            },
            \"matching_keywords\": [\"skill1\", \"skill2\"],
            \"missing_keywords\": [\"needed_skill1\", \"needed_skill2\"],
            \"insights\": [
                {\"type\": \"success|warning|error\", \"text\": \"...\"},
                ...
            ],
            \"raw_text\": \"Original CV text goes here (cleaned)\"
        }";

        $response = Http::withoutVerifying()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => config('services.groq.model'),
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert ATS analysis tool. Always return valid JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object']
        ]);

        if ($response->failed()) {
            Log::error('Groq Analysis API Error: ' . $response->body());
            throw new Exception('Groq API error: ' . $response->body());
        }

        $result = json_decode($response->json('choices.0.message.content'), true);
        $result['raw_text'] = $text; // Ensure we keep the raw text for generation later

        return $result;
    }
}
