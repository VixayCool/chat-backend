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

    public function translate($text, $toLanguage = 'en'){
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Ocp-Apim-Subscription-Region' => 'eastasia',
            'Content-Type' => 'application/json',
        ])->post("{$this->endpoint}/translate?api-version=3.0&to={$toLanguage}",
            [['Text' => $text]]);

        $data = $response->json();
        return $data[0]['translations'][0]['text'] ?? $text;
    }
}