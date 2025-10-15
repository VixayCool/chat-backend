<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AzureTranslatorService{
    protected $key;
    protected $endpoint;

    public function __construct(){
        $this->key =config('services.azure_translator.key');
        $this->endpoint = config('services.azure_translator.endpoint');
    }

    public function translate($text, $toLanguage = 'lo'){
        
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Ocp-Apim-Subscription-Region' => 'japanwest',
            'Content-Type' => 'application/json',
        ])->post("{$this->endpoint}/translate?api-version=3.0&to={$toLanguage}",
            [['Text' => $text]]);
        Log::info('Azure translation', [
            'text' => $text,
            'to language' => $toLanguage,
            'response' => $response->body(),
        ]);

        $data = $response->json();
        return $data[0]['translations'][0]['text'] ?? $text;
    }
}