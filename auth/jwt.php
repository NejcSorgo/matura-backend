<?php

class JWT  //Vir: https://dev.to/thedevdrawer/json-web-tokens-without-firebase-jwt-3mop
{

    private $headers;


    private $secret;

    public function __construct()
    {
        $this->headers = [
            "alg" => "HS256", //metoda kodiranja
            "typ" => "JWT", // tip JWT
            "iss" => "jwt.php", // izdajatelj
            "aud" => "localhost" // obcinstvo

        ];

        $this->secret = 'pepe'; //
    }

    public function generate(array $payload): string
    {  // Generira JWT z  Payloadom
        $headers = $this->encode(json_encode($this->headers)); // zakodira glavo
        $payload['exp'] = time() + 3600; // doda cas izteka  (60 min)
        $payload = $this->encode(json_encode($payload)); // zakodira payload
        $signature = hash_hmac('SHA256', "$headers.$payload", $this->secret, true); //ustvari SHA256 podpis
        $signature = $this->encode($signature); // zakodira podpis
        return "$headers.$payload.$signature"; /// vrne zakodiran token
    }
    private function encode(string $str): string
    { //  Zakodira JWT z base64
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }
    public function is_valid(string $jwt): bool
    { // Preveri ,  ce je JWT veljaven
        $token = explode('.', $jwt); // razdeli string v polje , kjer je pika
        if (!isset($token[1]) && !isset($token[2])) {
            echo "bruh";
            return false;
        }
        $headers = base64_decode($token[0]); // 
        $payload = base64_decode($token[1]);
        var_dump($payload);
        var_dump($headers);
        $clientSignature = $token[2];

        if (!json_decode($payload)) { // preveri ce payload obstaja
            echo "no payload"; // debug
            return false;
        }
        if ((json_decode($payload)->exp) - time() < 0) { /// preveri ce je cas izteka veljaven
            echo "expired token"; // debug
            return false;
        }
        if (isset(json_decode($payload)->iss)) { // preveri ce obstaja izdajatelj (issuer)
            if (json_decode($payload)->iss != json_decode($headers)->iss) { // preveri ce je izdajatelj veljaven
                echo "izdajatelj ni vredi"; // debug
                return false;
            }
        } else {
            echo "izdajatelj ne obstaja"; // debug
            return false;
        }

        $base64_header = $this->encode($headers);
        $base64_payload = $this->encode($payload);

        $signature = hash_hmac('SHA256', "$base64_header.$base64_payload", $this->secret, true);
        $base64_signature = $this->encode($signature);
        return ($base64_signature === $clientSignature);
    }
    public function decode(string $jwt)
    {
        $token = explode('.', $jwt); // razdeli string v polje , kjer je pika
        return base64_decode($token[1]); // vrne payload tokena
    }
}
