<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use GuzzleHttp\Client;

class AssemblyController extends Controller
{

    public function __construct()
    {
    }

    // Welcome to AssemblyAI! Get started with the API by transcribing
    // a file using PHP.
    //
    // In this example, we'll transcribe a local file. Get started by
    // downloading the snippet, then update the 'filename' variable
    // to point to the local path of the file you want to upload and
    // use the API to transcribe.
    //
    // IMPORTANT: Update line 101 to point to a local file.
    //
    // Have fun!

    // Function to upload a local file to the AssemblyAI API
    public function upload_file($api_token, $path) {
        $url = 'https://api.assemblyai.com/v2/upload';
        $data = file_get_contents($path);

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/octet-stream\r\nAuthorization: $api_token",
                'content' => $data
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($http_response_header[0] == 'HTTP/1.1 200 OK') {
            $json = json_decode($response, true);
            return $json['upload_url'];
        } else {
            echo "Error: " . $http_response_header[0] . " - $response";
            return null;
        }
    }

    // Function to create a transcript using AssemblyAI API
    public function create_transcript($api_token, $audio_url) {
        $url = "https://api.assemblyai.com/v2/transcript";

        $headers = array(
            "authorization: " . $api_token,
            "content-type: application/json"
        );

        // The start time of the transcription in milliseconds
        $audio_start_from = 5000;
        // The end time of the transcription in milliseconds
        $audio_end_at = 15000;

        $data = array(
            "audio_url" => $audio_url,
            "custom_spelling" => array(
                array(
                    "from" => array("information"),
                    "to" => "Information",
                )
            ),
            "filter_profanity" => true,
            //"audio_start_from" => $audio_start_from,
            //"audio_end_at" => $audio_end_at,
            //"speaker_labels" => true,  // Speaker Diarization
            //"speakers_expected" => 3,
            "summarization" => True,
            "summary_model" => "informative",
            "summary_type" => "bullets",
            "content_safety" => true, // identify hate speech
            //"sentiment_analysis" => true,
            //"entity_detection" => true,
            "redact_pii" => true,
            "redact_pii_policies" => array("us_social_security_number", "credit_card_number"),
            "iab_categories" => true, // topic detection
        );

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = json_decode(curl_exec($curl), true);

        curl_close($curl);

        $transcript_id = $response['id'];

        $polling_endpoint = "https://api.assemblyai.com/v2/transcript/" . $transcript_id;

        while (true) {
            $polling_response = curl_init($polling_endpoint);

            curl_setopt($polling_response, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($polling_response, CURLOPT_RETURNTRANSFER, true);

            $transcription_result = json_decode(curl_exec($polling_response), true);

            if ($transcription_result['status'] === "completed") {
                return $transcription_result;
            } else if ($transcription_result['status'] === "error") {
                throw new Exception("Transcription failed: " . $transcription_result['error']);
            } else {
                sleep(3);
            }
        }
    }

    // Convert video URL to audio using FFmpeg
    private function convertVideoUrlToAudio($videoUrl)
    {

        $videoFilePath = '/home/targetbay/Downloads//Benefits-of-Learning-a-New-Language-__-Motivational-English-Speech-by-Jack-Ma-_shorts720P_HD.mp4';
        $videoFilePath = $videoUrl;

        $audioOutputPath = storage_path('app/audio/' . time() . '.mp3');

        // Command to convert video to audio using ffmpeg
        $ffmpegCommand = "ffmpeg -i $videoFilePath -vn -acodec mp3 $audioOutputPath 2>&1";

        // Execute the ffmpeg command
        exec($ffmpegCommand, $output, $returnCode);

        // Check if the conversion was successful
        if ($returnCode === 0) {
            return "Video to audio conversion successful.";
        } else {
            return  "Conversion failed. Error output: " . implode(PHP_EOL, $output);
        }

        return $audioOutputPath;

    }

    public function transcript() {
        // Upload a file and create a transcript using the AssemblyAI API
        try {
            // Your API token is already set in this variable
            $api_token = "a3950d7660bb4d9dae24a149402c7141";

            // -----------------------------------------------------------------------------
            // Update the file path here, pointing to a local audio or video file.
            // If you don't have one, download a sample file: https://storage.googleapis.com/aai-web-samples/espn-bears.m4a
            // You may also remove the upload step and update the 'audio_url' parameter in the
            // 'create_transcript' function to point to a remote audio or video file.
            // -----------------------------------------------------------------------------

            // $videoPath = 'https://www.youtube.com/watch?v=fCF8I_X1qKI';
            // $path = $this->convertVideoUrlToAudio($videoPath);


            $path = "/home/targetbay/Downloads/Benefits-of-Learning-a-New-Language-__-Motivational-English-Speech-by-Jack-Ma-_shorts720P_HD.mp4";


            $upload_url = $this->upload_file($api_token, $path);

            $transcript = $this->create_transcript($api_token, $upload_url);
            //echo $transcript['text'];
            return $transcript;
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

    }

    public function qaPrompt() {
        $questions = [
            [
                "question" => "Is this caller a qualified buyer?",
                "answer_options" => [
                    "Yes",
                    "No"
                ]
            ],
            [
                "question" => "What is the caller's mood?",
                "answer_format" => "Short sentence"
            ]
        ];

        $apiToken = 'a3950d7660bb4d9dae24a149402c7141'; 
        $url = "https://api.assemblyai.com/v2/generate/question-answer";

        $headers = [
            "Authorization" => $apiToken,
            "Content-Type" => "application/json",
        ];

        $transcriptIds = ["67wg1aflma-1bc9-4f3a-a9d6-f239e0764766", "67wyd9wkej-5ab8-437a-8f86-e83203e4d4f1"];

        $data = [
            "transcript_ids" => $transcriptIds,
            "questions" => $questions,
            "model" => "basic",
        ];

        try {
            $client = new Client();
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $data,
            ]);

            $lemurResponse = $response->getBody()->getContents();
            return $lemurResponse;
        } catch (\Exception $e) {
            return response()->json(["error" => $e->getMessage()], 500);
        }
    }
  

}
