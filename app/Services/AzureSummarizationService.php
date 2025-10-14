<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AzureTranslatorService;
class AzureSummarizationService{
    protected $endpoint;
    protected $key;
    protected $translator;

    public function __construct(AzureTranslatorService $translator){
        $this->key = config('services.azure_summarization.key');
        $this->endpoint = config('services.azure_summarization.endpoint');
        $this->translator = $translator;

    }

    public function summarize($lang, $text){
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key'=>$this->key,
            'Ocp-Apim-Subscription-Region'=>'japanwest', 
            'Content-Type'=>"application/json"
            ])->post("{$this->endpoint}/language/analyze-text/jobs?api-version=2023-04-01", [
                "displayName"=> "Text Summarization Task",
                "analysisInput"=>[
                  "documents"=> [
                    [
                      "id"=> "1",
                      "language"=> "en",
                      "text"=> $text ]
                  ]
                ],
                "tasks"=> [
                  [
                    "kind"=> "AbstractiveSummarization",
                    "taskName"=> "Text Abstractive Summarization Task 1",
                    "parameters"=> [
                      "summaryLength"=> "short"
                  ]
                  ]
                ]
              ]);

        Log::info('Azure translation', [
            'text' => $text,
            'to language' => $lang,
            'response' => $response->body(),
        ]);

        $operationLocation = $response->header('Operation-Location');
        return $this->translator->translate($this->pollJobStatus($operationLocation), $lang);       
    }
    

    public function pollJobStatus($operationLocation){

        $maxAttempts = 10;
        $attempt = 0;
        $delay = 3000;
         while($attempt < $maxAttempts){

            $statusResponse = Http::withHeaders([
                'Ocp-Apim-Subscription-Key'=>$this->key,
            ])->get($operationLocation);

            if($statusResponse->successful()){
                $data = $statusResponse->json();
                $status = $data['status'];
                if($status === 'succeeded'){
                    return $data['tasks']['items'][0]['results']['documents'][0]['summaries'][0]['text'];
                }
                elseif ($status === 'failed') {
                    return ['error' => $statusData['error']['message'] ?? 'Job failed'];
                }

                usleep($delay * 1000);
                $attempt++;
            }
            else{
                return ['error' => 'Failed to retrieve job status'];
            }
         }
         return ['error' => 'Polling timeout'];
    }

}