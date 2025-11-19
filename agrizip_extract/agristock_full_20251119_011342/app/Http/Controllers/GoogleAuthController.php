<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Mail;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $user = Socialite::driver('google')->user();

        $email = $user->getEmail();

        // Tu peux envoyer un email ici si besoin :
        Mail::raw("Lien Google Drive cliqué par : $email", function ($message) {
            $message->to('amarakonem10@gmail.com')
                    ->subject('Lien Google Drive ouvert');
        });

        // Redirection vers ton fichier Drive (à adapter dynamiquement si tu veux)
        return redirect('https://docs.google.com/document/d/1KfJnXgc3gdx42k_m5iZmjCCCqaimS7kntNNacSOO7KM/edit?usp=sharing');
    }

    public function accessDrive(Request $request)
    {
        // stocker l’ID du fichier drive à ouvrir (optionnel)
        session(['drive_id' => $request->query('file')]);

        // rediriger vers Google Sign-In
        return redirect('/login/google');
    }
}
