# Assembly AI


Access powerful AI models to transcribe and understand speech. Their simple API exposes AI models for speech recognition, speaker detection, speech summarization, and more. (https://www.assemblyai.com/docs/).


## Examples
------------

AssemblyAI provides AI models to transcribe and analyze audio and speech data through our production-ready, scalable web API. Our models are customizable and enable features such as content moderation, sentiment analysis, PII redaction, key phrase identification, and speaker diarization.

## Step-by-step instructions:

1. Create a new file and import the necessary libraries for making an HTTP request.

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

2. Set up the API endpoint and headers. The headers should include your API token.

        $base_url = "https://api.assemblyai.com/v2";

        $headers = array(
          "authorization: {your_api_token}" ,
          "content-type: application/json"
        );

3. Upload your local file to the AssemblyAI API.

        $path = "/my_audio.mp3" ;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $base_url . "/upload");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($path));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        $response_data = json_decode($response, true);
        $upload_url = $response_data["upload_url"];

        curl_close($ch);

4. Use the upload_url returned by the AssemblyAI API to create a JSON payload containing the audio_url parameter.

        $data = array(
            "audio_url" => upload_url // You can also use a URL to an audio or video file on the web
        );

5. Make a POST request to the AssemblyAI API endpoint with the payload and headers.

        $url = $base_url . "/transcript";
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        $response = json_decode($response, true);

        curl_close($curl);

6. After making the request, you will receive an ID for the transcription. Use it to poll the API every few seconds to check the status of the transcript job. Once the status is completed, you can retrieve the transcript from the API response.

        $transcript_id = $response['id'];
        $polling_endpoint = "https://api.assemblyai.com/v2/transcript/" . $transcript_id;

        while (true) {
        $polling_response = curl_init($polling_endpoint);

        curl_setopt($polling_response, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($polling_response, CURLOPT_RETURNTRANSFER, true);

        $transcription_result = json_decode(curl_exec($polling_response), true);

        if ($transcription_result['status'] === "completed") {
            break;
        } else if ($transcription_result['status'] === "error") {
            throw new Exception("Transcription failed: " . $transcription_result['error']);
        } else {
            sleep(3);
        }
    }

## Understanding the response

    {
    id:"6rlr37h8f4-e310-4e23-bbf3-ea5f347dc684",
    language_model:"assemblyai_default",
    acoustic_model:"assemblyai_default",
    language_code:"en_us",
    status:"completed",
    audio_url:"https://cdn.assemblyai.com/upload/83bdd119-9099-46c9-8845-50c3ec ...",
    text:"You. Runner's Knee runner's knee is a condition characterized by ...",
    words:[...],
    utterances:NULL,
    confidence:0.9112455882352947,
    audio_duration:200,
    punctuate:true
    }

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Revathi Stalin](https://github.com/revathi-stalin)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
