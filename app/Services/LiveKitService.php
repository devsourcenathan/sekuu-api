<?php

namespace App\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\Session;
use App\Models\User;

class LiveKitService
{
    protected string $apiKey;

    protected string $apiSecret;

    protected string $url;

    public function __construct()
    {
        $this->apiKey = config('livekit.api_key');
        $this->apiSecret = config('livekit.api_secret');
        $this->url = config('livekit.url');
    }

    /**
     * Generate a LiveKit access token for a user to join a session
     */
    public function generateToken(Session $session, User $user, array $customPermissions = []): string
    {
        // Déterminer le rôle de l'utilisateur dans la session
        $role = $this->getUserRole($session, $user);

        // Récupérer les permissions par défaut pour ce rôle
        $permissions = config("livekit.permissions.{$role}", config('livekit.permissions.participant'));

        // Fusionner avec les permissions personnalisées
        $permissions = array_merge($permissions, $customPermissions);

        // Créer le VideoGrant avec les permissions
        $videoGrant = new VideoGrant();
        $videoGrant->setRoomJoin(true);
        $videoGrant->setRoomName($session->livekit_room_name);
        $videoGrant->setCanPublish($permissions['canPublish'] ?? true);
        $videoGrant->setCanSubscribe($permissions['canSubscribe'] ?? true);
        $videoGrant->setCanPublishData($permissions['canPublishData'] ?? true);

        // Créer le token
        $token = new AccessToken($this->apiKey, $this->apiSecret);
        $token->setIdentity((string) $user->id);
        $token->setName($user->name);
        $token->setGrant($videoGrant);

        // Définir la durée de validité du token
        $ttl = config('livekit.default_token_ttl', 3600);
        $token->setTtl($ttl);

        return $token->toJwt();
    }

    /**
     * Déterminer le rôle de l'utilisateur dans la session
     */
    protected function getUserRole(Session $session, User $user): string
    {
        // L'instructeur est toujours host
        if ($session->instructor_id === $user->id) {
            return 'host';
        }

        // Vérifier le rôle dans la table pivot
        $participant = $session->sessionParticipants()
            ->where('user_id', $user->id)
            ->first();

        return $participant?->role ?? 'participant';
    }

    /**
     * Get LiveKit server URL
     */
    public function getServerUrl(): string
    {
        return $this->url;
    }

    /**
     * Validate webhook signature (optional, for security)
     */
    public function validateWebhookSignature(string $body, string $signature): bool
    {
        $webhookSecret = config('livekit.webhook_secret');

        if (! $webhookSecret) {
            return true; // Pas de validation si pas de secret configuré
        }

        $expectedSignature = hash_hmac('sha256', $body, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
