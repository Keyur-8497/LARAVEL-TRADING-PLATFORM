<?php

namespace App\Http\Controllers\Front;

use App\Services\KiteSessionManager;
use App\Support\ApplicationLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ZerodhaAuthController extends FrontMainController
{
    public function __construct(private readonly KiteSessionManager $kiteSessionManager)
    {
    }

    public function redirectToProvider(): RedirectResponse
    {
        if (! filled(config('kite.api_key')) || ! filled(config('kite.api_secret'))) {
            ApplicationLogger::warning('Zerodha login blocked because API credentials are missing.');

            return redirect()
                ->route('home')
                ->with('error', 'Add KITE_API_KEY and KITE_API_SECRET in your .env file before connecting Zerodha.');
        }

        ApplicationLogger::event('Redirecting user to Zerodha login.');

        return redirect()->away($this->kiteSessionManager->getLoginUrl());
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        if ($request->filled('status') && $request->string('status')->lower()->toString() !== 'success') {
            ApplicationLogger::warning('Zerodha login callback returned non-success status.', [
                'status' => $request->input('status'),
            ]);

            return redirect()
                ->route('home')
                ->with('error', 'Zerodha login was cancelled or not completed.');
        }

        $requestToken = $request->string('request_token')->trim()->toString();

        if ($requestToken === '') {
            ApplicationLogger::warning('Zerodha callback missing request_token.');

            return redirect()
                ->route('home')
                ->with('error', 'Missing request_token in Zerodha callback URL.');
        }

        try {
            $session = $this->kiteSessionManager->exchangeRequestToken($requestToken);

            ApplicationLogger::event('Zerodha session stored successfully.', [
                'user_id' => $session['user_id'] ?? null,
                'token_file_path' => $this->kiteSessionManager->getTokenFilePath(),
            ]);

            return redirect()
                ->route('dashboard')
                ->with('success', 'Zerodha connected successfully. Live dashboard is ready.');
        } catch (\Throwable $exception) {
            ApplicationLogger::error(
                'Zerodha callback failed.',
                ApplicationLogger::exceptionContext($exception)
            );

            return redirect()
                ->route('home')
                ->with('error', 'Unable to create Zerodha session: '.$exception->getMessage());
        }
    }

    public function logout(): RedirectResponse
    {
        $this->kiteSessionManager->clearSession();
        ApplicationLogger::event('Zerodha session cleared by logout action.');

        return redirect()
            ->route('home')
            ->with('success', 'Stored Zerodha access token removed successfully.');
    }
}
