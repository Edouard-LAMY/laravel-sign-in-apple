<?php

namespace Lamy\LaravelSignInApple\Classes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use phpseclib3\Crypt\RSA;
use Illuminate\Http\Request;
use phpseclib3\Math\BigInteger;
use Illuminate\Support\Facades\Http;

class LaravelSignInApple
{
    public static function generateToken(): string
    {   
        $team_id = config('services.apple.team_id');
        $client_id = config('services.apple.client_id');
        $key_id = config('services.apple.key_id');

        $ecdsa_key = openssl_pkey_get_private("file://" . base_path('key.pem'));
        
        $claims = [
            'iss' => $team_id,
            'iat' => time(),
            'exp' => time() + 86400 * 180,
            'aud' => 'https://appleid.apple.com',
            'sub' => $client_id,
        ];

        $headers = [
            'kid' => $key_id,
        ];

        $token =  $ecdsa_key != null ? JWT::encode($claims, $ecdsa_key, 'ES256', null, $headers) : '';

        return $token;
    }

    public static function decodeAppleToken(Request $request): object
    {
        $response = Http::asForm()->post('https://appleid.apple.com/auth/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $request->input('code'),
            'redirect_uri'  => route('apple-callback'),
            'client_id'     => config('services.apple.client_id'),
            'client_secret' => $this->generateToken(),
        ]);
        $data           = $response->json();

        $socialUser = new class {
            public bool $success = false;
            public string $message = 'Error during authentication via Apple.';
        };

        if (isset($data['id_token'])) {
            $idToken        = $data['id_token'];
    
            // Get kid (key_id)
            $kid            = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $idToken)[0]))->kid;
            
            // Get Apple public key 
            $appleKeys      = Http::get('https://appleid.apple.com/auth/keys')->json()['keys'];
    
            // Find the key
            $matchingKey = null;
            foreach ($appleKeys as $key) {
                if (isset($key['kid']) && $key['kid'] === $kid) {
                    $matchingKey = $key;
                    break;
                }
            }
    
            // Convert in PEM
            $publicKeyPem   = $this->buildPemFromModulusExponent($matchingKey['n'], $matchingKey['e']);
    
            // Decode token
            JWT::$leeway = 300; 
            $decoded = JWT::decode($idToken, new Key($publicKeyPem, 'RS256'));
            
            $firstName  = null;
            $lastName   = null;
            if ($request->input('user')) {
                $userData   = json_decode($request->input('user'), true);
                $firstName  = $userData['name']['firstName'];
                $lastName   = $userData['name']['lastName'];
            }
    
            // NEW CLASS for acces getId, getEmail, getName
            $socialUser = new class($decoded, $data, $firstName, $lastName) {
                public string $id;
                public ?string $email;
                /** @var array<string, string|null>|null */
                public ?array $name;
                public ?string $token;
                public ?string $refreshToken;
    
                public function __construct(object $decoded, mixed $data, ?string $firstName, ?string $lastName)
                {
                    $this->id = $decoded->sub;
                    $this->email = $decoded->email ?? null;
                    $this->name = [
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                    ];
                    $this->token = $data['access_token'] ?? null;
                    $this->refreshToken = $data['refresh_token'] ?? null;
                }
    
                public function getId(): string
                {
                    return $this->id;
                }
    
                public function getEmail(): ?string
                {
                    return $this->email;
                }
    
                /**
                 * @return array<string, string|null>|null
                 */
                public function getName(): ?array
                {
                    return $this->name;
                }
            };
        }
        
        return $socialUser;
    }

    public function buildPemFromModulusExponent(string $n, string $e): string
    {
        $modulus = new BigInteger(JWT::urlsafeB64Decode($n), 256);
        $exponent = new BigInteger(JWT::urlsafeB64Decode($e), 256);

        $rsa = RSA::loadPublicKey([
            'n' => $modulus,
            'e' => $exponent,
        ]);

        return $rsa->toString('PKCS8');
    }
}