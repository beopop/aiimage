<?php
namespace WC_Fabric_Mockups;

class ApiAdapter {
    protected $api_key;
    protected $master_image;
    protected $mask_image;

    public function __construct( $api_key, $master_image, $mask_image ) {
        $this->api_key      = $api_key;
        $this->master_image = $master_image;
        $this->mask_image   = $mask_image;
    }

    public function generate( $texture_path ) {
        $prompt = 'High-end studio photo of the same dining chair model, replace upholstery with fabric from the reference texture image, seamless light gray background with soft shadows.';
        $body   = [
            'model'  => 'gpt-image-1',
            'prompt' => $prompt,
            'size'   => '1024x1024',
        ];

        $boundary = wp_generate_password( 24, false );
        $headers  = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type'  => 'multipart/form-data; boundary=' . $boundary,
        ];

        $files = [
            'image'       => $this->master_image,
            'mask'        => $this->mask_image,
            'image[]'     => $texture_path,
        ];

        $payload = self::build_multipart( $body, $files, $boundary );
        Logger::info( 'Calling OpenAI API' );
        Logger::info( 'OpenAI request payload: ' . wp_json_encode( $body ) );
        Logger::info( 'OpenAI request images: master=' . basename( $this->master_image ) . ', mask=' . basename( $this->mask_image ) . ', texture=' . basename( $texture_path ) );

        $response = wp_remote_post( 'https://api.openai.com/v1/images/edits', [
            'headers' => $headers,
            'body'    => $payload,
            'timeout' => 60,
        ] );

        if ( ! is_wp_error( $response ) ) {
            $code    = wp_remote_retrieve_response_code( $response );
            $body_snippet = substr( wp_remote_retrieve_body( $response ), 0, 200 );
            Logger::info( 'OpenAI response code: ' . $code );
            Logger::info( 'OpenAI response body: ' . $body_snippet );
        }

        if ( is_wp_error( $response ) ) {
            Logger::error( 'OpenAI API error: ' . $response->get_error_message() );
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['data'][0]['b64_json'] ) ) {
            Logger::error( 'OpenAI API returned no image data' );
            return false;
        }
        Logger::info( 'Received image data' );
        return base64_decode( $data['data'][0]['b64_json'] );
    }

    protected static function build_multipart( $fields, $files, $boundary ) {
        $eol = "\r\n";
        $data = '';
        foreach ( $fields as $name => $value ) {
            $data .= "--{$boundary}{$eol}";
            $data .= "Content-Disposition: form-data; name=\"{$name}\"{$eol}{$eol}{$value}{$eol}";
        }
        foreach ( $files as $name => $path ) {
            $filename = basename( $path );
            $contents = file_get_contents( $path );
            $mime     = mime_content_type( $path );
            if ( ! $mime ) {
                $mime = 'application/octet-stream';
            }
            $data .= "--{$boundary}{$eol}";
            $data .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"{$eol}";
            $data .= "Content-Type: {$mime}{$eol}{$eol}";
            $data .= $contents . $eol;
        }
        $data .= "--{$boundary}--{$eol}";
        return $data;
    }
}
