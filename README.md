# Laravel Sign In with Apple

[![Latest Version on Packagist](https://packagist.org/packages/lamy/laravel-sign-in-apple)]

**Laravel Sign In with Apple** is a simple package that makes it easier to implement **Apple Sign In** using Laravel 12 and Socialite.

It handles Apple-specific configurations, token generation, and user data parsing to provide a smooth authentication flow.

---

## 🚀 Features

- Easy integration with Laravel and Socialite
- Automatic `client_secret` generation for Apple
- Secure decoding of Apple tokens
- Simple `.env` configuration
- Fully customizable authentication logic

---

## 📦 Installation

You can install the package via composer:

```bash
composer require lamy/laravel-sign-in-apple
```

You can add the configuration apple in config/services.php:

```bash
php artisan laravel-sign-in-apple:install
```

We also recommend using laravel/socialite and socialiteproviders/apple to automatically manage user resolution and persistence:
```bash
composer require laravel/socialite socialiteproviders/apple
```

Add Event to AppServiceProvider.php in function boot():
```bash
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Apple\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

Event::listen(function (SocialiteWasCalled $event) {
    $event->extendSocialite('apple', Provider::class);
});
```


## ⚙️ Apple Developer Configuration

### 1. Create an App ID

- Go to the [Apple Developer Console](https://developer.apple.com/)
- Create a new App ID: [Create a Bundle ID](https://developer.apple.com/account/resources/identifiers/list/bundleId)
  - **Description**: `example.com App ID`
  - **Bundle ID (explicit)**: `com.example.id`
  - Enable: ✅ **Sign In with Apple**
- Retrieve your **Team ID** (e.g. `123AZ987ZA`)
- Add it to your `.env` file:

```env
APPLE_TEAM_ID=123AZ987ZA
```

### 2. Create a Service ID

- Go to [Create a Service ID](https://developer.apple.com/account/resources/identifiers/list/serviceId)
- Click **➕ Add**:
  - **Description**: `example.com Service ID`
  - **Identifier**: `com.example.service`
  - Enable: ✅ **Sign In with Apple**
- Click **Configure** under "Sign In with Apple":
  - **Primary App ID**: select the App ID created in step 1
  - **Web Domain**: `example.com` (your website domain)
  - **Return URL**: `https://example.com/auth/callback` (your Laravel route)
- Click **Save**
- Go back and click **Edit** to confirm the config is saved
- Add this to your `.env`:

```env
APPLE_CLIENT_ID=com.example.service
```

### 3. Create a Sign In with Apple Key

- Go to **Create a Key** in the Apple Developer Console
- Click **+ Add**
- Enter a **Key Name** (e.g., Sign In with Apple Key)
- ✅ Enable **Sign In with Apple**
- Select the **Primary App ID** created in step 1
- Click **Continue**, then **Register**
- Click **Download** to get the `.p8` key file (⚠️ this download is only available once)
- Rename the downloaded file from `AuthKey_12345ABCD.p8` to `key.pem` by running:

```bash
mv AuthKey_12345ABCD.p8 key.pem
```
Place the key.pem file at the root of your Laravel project (same level as your .env)

Add the following to your .env file:

```env
APPLE_KEY_ID=12345ABCD
```

### 4. Final .env keys overview

You should have these keys in your .env file:
```env
APPLE_CLIENT_ID=com.example.service
APPLE_CLIENT_SECRET=""
APPLE_TEAM_ID=123AZ987ZA
APPLE_KEY_ID=12345ABCD
```

The APPLE_CLIENT_SECRET will be generated later.

### 5. Generate the Client Secret Token

To generate the first client secret token, run the following in artisan tinker:
```bash
php artisan tinker
```

Then inside tinker:
```bash
\Lamy\LaravelSignInApple\LaravelSignInApple::generateToken();
```

Copy the generated token and set it in your .env:
```env
APPLE_CLIENT_SECRET="your_generated_token_here"
```

### 6. Routing
Add the following routes to your routes/auth.php:
⚠️ Specify the callback name as: apple-callback
```bash
use App\Http\Controllers\Auth\SocialAuthenticationController;

Route::get('/auth/redirect/apple', [SocialAuthenticationController::class, 'socialRedirect'])->name('auth-social');
Route::post('/auth/callback/apple', [SocialAuthenticationController::class, 'appleCallback'])->name('apple-callback');
```

### 7. Controller Example
Here is an example controller to handle the Apple Sign In flow:
```bash
<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Lamy\LaravelSignInApple\Facades\LaravelSignInApple;

class SocialAuthenticationController extends Controller
{
    public function socialRedirect()
    {
        return Socialite::driver('apple')->scopes(['name', 'email'])->redirect();
    }

    public function appleCallback(LaravelSignInApple $signInApple, Request $request)
    {
        $socialUser = $signInApple->decodeAppleToken($request);

        if (property_exists($socialUser, 'success') && $socialUser->success === false) {
            return redirect()->intended('/')->with('error', $socialUser->message);
        }
        
        return $this->authenticate($socialUser, $request);
    }

    public function authenticate(object $socialUser, Request $request)
    {
        $userExist = User::where("apple_id", $socialUser->getId())->first();
        $redirectTo = '/';

        if ($userExist) {
            Auth::login($userExist);
            $redirectTo = '/home';
        } elseif ($socialUser->getEmail()) {
            $user = User::updateOrCreate(
                ['email'                    => $socialUser->getEmail()],
                [
                    'firstname'             => $socialUser->getName()['firstName'] ?? null,
                    'lastname'              => $socialUser->getName()['lastName'] ?? null,
                    'email'                 => $socialUser->getEmail(),
                    'apple_id'              => $socialUser->getId(),
                    'apple_token'           => $socialUser->token,
                    'apple_refresh_token'   => $socialUser->refreshToken,
                ]
            );

            Auth::login($user);
            $redirectTo = '/home';
        }

        return redirect($redirectTo);
    }
}
```

### 🔐 Token Generation and Apple Authentication
#### `generateToken()`

The static method `generateToken()` does **not require any parameters**.

If you've run the installation command:

```bash
php artisan laravel-sign-in-apple:install
```
and properly set the following environment variables:
APPLE_CLIENT_ID
APPLE_TEAM_ID
APPLE_KEY_ID

Then this method will return a string, which is your APPLE_CLIENT_SECRET — you can paste it into your .env file.
ℹ️ This token is valid for 6 months. After that, you’ll need to generate a new one and update APPLE_CLIENT_SECRET again.


### 🔐 Decode Apple Token Authentication
#### `decodeAppleToken(Request $request)`

The static method decodeAppleToken() expects an instance of Illuminate\Http\Request as a parameter, to handle the callback from Apple.

It will:
Extract the authorization code from $request->input('code')
Exchange the authorization code for an access token,
Verify the Apple ID token’s signature,
Extract user data (ID, email, name) and return a structured object for use in your app.

✅ Returned object structure
The returned object provides the following methods and properties:

```bash
$socialUser->getId();         // string       (Apple user ID)
$socialUser->getEmail();      // string|null  (Apple email)
$socialUser->getName();       // array|null   ['firstName' => ..., 'lastName' => ...]
$socialUser->token;           // string|null  (Apple access token)
$socialUser->refreshToken;    // string|null  (Apple refresh token)
```

⚠️ Errors are silently encapsulated in the returned object using:
```bash
$socialUser->success = false;
$socialUser->message = 'Error during authentication via Apple.';
```
You can choose to handle these errors manually.

🔒 Security notes
The Apple ID token (JWT) is validated against Apple’s public key.

The method uses:
firebase/php-jwt
phpseclib/phpseclib

to handle cryptographic operations and ensure secure authentication.


⚠️ Handling Missing Name and Email

If a user doesn’t provide name and email during the Apple Sign In, they can:
On their iPhone, go to Settings
Tap on their Avatar or Name
Tap Sign In with Apple
Remove the app that was previously authorized

Retry the login flow; Apple will prompt for name and email only once

For more info, see:
StackOverflow - Laravel Socialite Apple Sign In no user info
https://stackoverflow.com/questions/78101351/laravel-socialite-apple-sign-in-no-user-info/78294921


## Credits - [Edouard LAMY](https://github.com/)
