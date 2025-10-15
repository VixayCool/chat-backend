<?php
namespace App\Services;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class GeminiSummarizationService
{
    protected $client;
    protected $maxAttempts = 2; 

    public function __construct()
    {
    //    $this->client = new Client(env('GEMINI_API_KEY'));
    }

    public function summarize(string $text, string $lang = 'english'): string
    {
        $attempt = 0;
        $delay = 9000;

        while ($attempt < $this->maxAttempts) {
            try {
                
                $prompt = "Summarize the following text in $lang. For each key action or event, clearly mention who did what. 
                Present it concisely and in a readable form, without any introduction or bold formating:\n\n" . $text;

                $response = Gemini::generativeModel(model: 'gemini-2.5-flash-lite')->generateContent($prompt);

                Log::info($response->text());
                return $response->text();
            } catch (RateLimitException $e) {
                $attempt++;
                usleep($delay * 1000); // wait before retry
                $delay *= 2; // exponential backoff
            } catch (\Exception $e) {
                // Log other exceptions if needed
                return "Error: " . $e->getMessage();
            }
        }

        return "Error: Rate limit exceeded. Please try again later.";
    }
}
