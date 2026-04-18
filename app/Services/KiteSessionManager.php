<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use KiteConnect\KiteConnect;
use RuntimeException;

class KiteSessionManager
{
    public function getApiKey(): ?string
    {
        return config('kite.api_key');
    }

    public function getApiSecret(): ?string
    {
        return config('kite.api_secret');
    }

    public function getAccessToken(): ?string
    {
        $session = $this->getSessionData();

        return $session['access_token'] ?? config('kite.access_token');
    }

    public function hasStoredAccessToken(): bool
    {
        return filled($this->getSessionData()['access_token'] ?? null);
    }

    public function hasActiveSession(): bool
    {
        $session = $this->getSessionData();

        if (! filled($session['access_token'] ?? null)) {
            return false;
        }

        if ($this->isSessionExpired($session)) {
            $this->clearSession();

            return false;
        }

        return true;
    }

    public function getSessionData(): ?array
    {
        $path = $this->getTokenFilePath();

        if (! File::exists($path)) {
            return null;
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : null;
    }

    public function isSessionExpired(?array $session = null): bool
    {
        $session ??= $this->getSessionData();

        if (! is_array($session) || ! filled($session['login_time'] ?? null)) {
            return true;
        }

        try {
            $loginDate = Carbon::parse($session['login_time']);
        } catch (\Throwable) {
            return true;
        }

        return ! $loginDate->isSameDay(now());
    }

    public function saveSession(object|array $sessionData): array
    {
        $session = $this->normalizeSessionData($sessionData);
        $path = $this->getTokenFilePath();

        if (! filled($session['access_token'] ?? null)) {
            throw new RuntimeException('Zerodha session did not include an access token.');
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($session, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (! File::exists($path)) {
            throw new RuntimeException('Zerodha session file could not be created.');
        }

        return $session;
    }

    public function clearSession(): void
    {
        $path = $this->getTokenFilePath();

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    public function getLoginUrl(): string
    {
        return $this->makeClient()->getLoginURL();
    }

    public function exchangeRequestToken(string $requestToken): array
    {
        $session = $this->makeClient()->generateSession($requestToken, (string) $this->getApiSecret());

        return $this->saveSession($session);
    }

    public function hasValidAccessToken(): bool
    {
        $token = $this->getAccessToken();

        if (! filled($this->getApiKey()) || ! filled($token)) {
            return false;
        }

        try {
            $this->makeClient($token)->getProfile();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function makeClient(?string $accessToken = null): KiteConnect
    {
        $client = new KiteConnect((string) $this->getApiKey());
        $token = $accessToken ?? $this->getAccessToken();

        if (filled($token)) {
            $client->setAccessToken($token);
        }

        return $client;
    }

    public function getTokenFilePath(): string
    {
        $configuredPath = config('kite.token_file_path', 'storage/app/kite_session.json');

        if ($this->isAbsolutePath($configuredPath)) {
            return $configuredPath;
        }

        return base_path($configuredPath);
    }

    private function normalizeSessionData(object|array $sessionData): array
    {
        $data = is_array($sessionData) ? $sessionData : json_decode(json_encode($sessionData), true);
        $loginTime = $sessionData->login_time ?? ($data['login_time'] ?? null);

        return [
            'api_key' => $this->getApiKey(),
            'access_token' => $data['access_token'] ?? null,
            'public_token' => $data['public_token'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'user_name' => $data['user_name'] ?? null,
            'email' => $data['email'] ?? null,
            'login_time' => $this->normalizeLoginTime($loginTime),
        ];
    }

    private function normalizeLoginTime(mixed $loginTime): string
    {
        if ($loginTime instanceof DateTimeInterface) {
            return $loginTime->format('Y-m-d H:i:s');
        }

        if (is_string($loginTime) && $loginTime !== '') {
            return $loginTime;
        }

        if (is_array($loginTime) && isset($loginTime['date'])) {
            return (string) $loginTime['date'];
        }

        return now()->format('Y-m-d H:i:s');
    }

    private function isAbsolutePath(string $path): bool
    {
        return Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
