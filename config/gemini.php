<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    |
    | Your Google Gemini API key used to authenticate requests.
    | Get your key at: https://aistudio.google.com/apikey
    |
    */

    'api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Model
    |--------------------------------------------------------------------------
    |
    | The default Gemini model to use for API requests.
    | Gemini 2.0 Flash is the recommended model for multimodal tasks.
    |
    */

    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a Gemini API response.
    |
    */

    'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | AI Server URL (Python FastAPI)
    |--------------------------------------------------------------------------
    |
    | Base URL for the Python AI server that performs room analysis.
    |
    */

    'ai_server_url' => env('AI_SERVER_URL', 'http://127.0.0.1:8001'),

];
