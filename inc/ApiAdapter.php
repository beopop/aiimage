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

    public function generate( $texture_path, $angle ) {
        $prompt = sprintf( 'High-end studio photo of the same dining chair model, angle: %s, replace upholstery with fabric from the reference texture image, seamless light gray background with soft shadows.', $angle );
        $body   = [
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

        $response = wp_remote_post( 'https://api.openai.com/v1/images/edits', [
            'headers' => $headers,
            'body'    => $payload,
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['data'][0]['b64_json'] ) ) {
            return false;
        }
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
            $data .= "--{$boundary}{$eol}";
            $data .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"{$eol}";
            $data .= "Content-Type: image/png{$eol}{$eol}";
            $data .= $contents . $eol;
        }
        $data .= "--{$boundary}--{$eol}";
        return $data;
    }
}
