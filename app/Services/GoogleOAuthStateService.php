<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use RuntimeException;

class GoogleOAuthStateService
{
    private const SESSION_KEY = 'google_oauth_state';

    private const SESSION_INTENT_KEY = 'google_oauth_intent';

    public function __construct(
        private Session $session
    ) {
    }

    public function generate(
        string $intent = 'login'
    ): string {
        $intent = strtolower(trim($intent));

        if (
            !in_array(
                $intent,
                ['login', 'link'],
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid Google authentication intent.'
            );
        }

        $state = bin2hex(
            random_bytes(32)
        );

        $this->session->put(
            self::SESSION_KEY,
            $state
        );

        $this->session->put(
            self::SESSION_INTENT_KEY,
            $intent
        );

        return $state;
    }

    public function validate(
        ?string $state
    ): string {
        $state = is_string($state)
            ? trim($state)
            : '';

        $storedState = $this->session->get(
            self::SESSION_KEY
        );

        $intent = $this->session->get(
            self::SESSION_INTENT_KEY
        );

        $this->clear();

        if (
            !is_string($storedState) ||
            $storedState === '' ||
            $state === ''
        ) {
            throw new RuntimeException(
                'Invalid Google authentication request.'
            );
        }

        if (!hash_equals($storedState, $state)) {
            throw new RuntimeException(
                'Invalid Google authentication request.'
            );
        }

        if (
            !is_string($intent) ||
            !in_array(
                $intent,
                ['login', 'link'],
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid Google authentication request.'
            );
        }

        return $intent;
    }

    /**
     * Read the stored intent only to choose the error context.
     * Successful authentication still requires validate().
     */
    public function intent(): string
    {
        return $this->session->get(self::SESSION_INTENT_KEY) === 'link'
            ? 'link'
            : 'login';
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_KEY);
        $this->session->remove(self::SESSION_INTENT_KEY);
    }
}
