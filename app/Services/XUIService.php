<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XUIService
{
    protected string $baseUrl;
    protected string $basePath;
    protected string $apiToken;

    public function __construct(string $host, string $apiToken)
    {
        $parsedUrl = parse_url(rtrim($host, '/'));

        if ($parsedUrl === false) {
            throw new \InvalidArgumentException("Invalid URL format: $host");
        }

        $this->baseUrl = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'localhost') . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '');
        $this->basePath = $parsedUrl['path'] ?? '';

        if (!empty($this->basePath) && !str_starts_with($this->basePath, '/')) {
            $this->basePath = '/' . $this->basePath;
        }

        $this->apiToken = $apiToken;
    }

    private function getClient(): PendingRequest
    {
        $options = [
            'verify' => false,
            'timeout' => 120,
            'connect_timeout' => 60,
        ];

        if (env('HTTP_PROXY')) {
            $options['proxy'] = env('HTTP_PROXY');
        }

        return Http::withOptions($options)->withoutVerifying()->withToken($this->apiToken);
    }

    public function getClients(int $inboundId): array
    {
        if (empty($this->apiToken)) {
            Log::error('Cannot get clients: API token is not configured');
            return [];
        }

        try {
            $url = $this->baseUrl . $this->basePath . "/panel/api/inbounds/get/{$inboundId}";
            $response = $this->getClient()->get($url);

            if (!$response->successful()) {
                Log::error('Failed to fetch inbound details', [
                    'inbound_id' => $inboundId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();

            Log::debug('X-UI raw response for getClients', [
                'inbound_id' => $inboundId,
                'full_response' => $data
            ]);

            $rawSettings = $data['obj']['settings'] ?? '{}';
            $settings = is_array($rawSettings)
                ? $rawSettings
                : json_decode((string) $rawSettings, true);
            $clients = is_array($settings) ? ($settings['clients'] ?? []) : [];

            Log::info('Successfully fetched clients', [
                'inbound_id' => $inboundId,
                'count' => count($clients),
                'clients_list' => array_map(function($c) {
                    return ['id' => $c['id'] ?? null, 'email' => $c['email'] ?? null, 'subId' => $c['subId'] ?? null];
                }, $clients)
            ]);

            return $clients;

        } catch (\Exception $e) {
            Log::error('Exception while fetching clients', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function checkClientExists(int $inboundId, string $email): bool
    {
        $clients = $this->getClients($inboundId);
        foreach ($clients as $client) {
            if (isset($client['email']) && $client['email'] === $email) {
                return true;
            }
        }
        return false;
    }

    public function getInbounds(): array
    {
        if (empty($this->apiToken)) {
            Log::error('Cannot get inbounds: API token is not configured');
            return [];
        }

        try {
            $url = $this->baseUrl . $this->basePath . '/panel/api/inbounds/list';
            $response = $this->getClient()->get($url);

            if (!$response->successful()) {
                Log::error('Failed to fetch inbounds', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            $inbounds = $data['obj'] ?? [];
            Log::info('Successfully fetched inbounds', ['count' => count($inbounds)]);
            return $inbounds;

        } catch (\Exception $e) {
            Log::error('Exception while fetching inbounds', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function addClient(int $inboundId, array $clientData): ?array
    {
        if (empty($this->apiToken)) {
            return ['success' => false, 'msg' => 'API token is not configured.'];
        }

        try {
            $uuid = Str::uuid()->toString();
            $subId = Str::random(16);

            Log::info('Creating XUI client', [
                'inbound_id' => $inboundId,
                'email' => $clientData['email'] ?? 'N/A',
                'generated_uuid' => $uuid,
                'generated_subId' => $subId
            ]);

            // Per-protocol secrets (uuid/password/auth) are generated server-side when omitted.
            $clientSettings = [
                'id' => $uuid,
                'email' => $clientData['email'],
                'flow' => $clientData['flow'] ?? '',
                'security' => 'auto',
                'totalGB' => (int) ($clientData['total'] ?? 0),
                'expiryTime' => (int) ($clientData['expiryTime'] ?? 0),
                'reset' => 0,
                'resetDay' => 0,
                'resetMax' => 0,
                'trafficReset' => 'never',
                'trafficResetDay' => 1,
                'limitIp' => 0,
                'limitHwid' => 0,
                'group' => '',
                'comment' => '',
                'enable' => true,
                'subId' => $subId,
            ];

            $url = $this->baseUrl . $this->basePath . '/panel/api/clients/add';

            Log::info('Trying XUI addClient endpoint', [
                'url' => $url,
                'inbound_id' => $inboundId
            ]);

            $response = $this->getClient()->post($url, [
                'client' => $clientSettings,
                'inboundIds' => [$inboundId],
            ]);

            $responseData = $response->json();

            Log::info('XUI addClient response', [
                'status' => $response->status(),
                'success' => $responseData['success'] ?? false,
                'msg' => $responseData['msg'] ?? 'N/A'
            ]);

            if (!$response->successful() || !($responseData['success'] ?? false)) {
                Log::error('XUI addClient failed completely', [
                    'inbound_id' => $inboundId,
                    'status' => $response->status(),
                    'last_error' => $responseData['msg'] ?? '',
                    'last_response_body' => $response->body()
                ]);
                return ['success' => false, 'msg' => $responseData['msg'] ?? $response->body()];
            }

            return array_merge($responseData, [
                'generated_uuid' => $uuid,
                'generated_subId' => $subId,
                'inbound_id' => $inboundId
            ]);

        } catch (\Exception $e) {
            Log::error('Exception in XUI addClient', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'msg' => 'Error creating client: ' . $e->getMessage()];
        }
    }

    public function resetClientTraffic(int $inboundId, string $email): bool
    {
        if (empty($this->apiToken)) {
            Log::error('Cannot reset traffic: API token is not configured');
            return false;
        }

        try {
            // New Sanaei API: POST /panel/api/clients/resetTraffic/{email}
            $url = $this->baseUrl . $this->basePath . '/panel/api/clients/resetTraffic/' . rawurlencode($email);

            Log::info('Resetting XUI client traffic', [
                'url' => $url,
                'inbound_id' => $inboundId,
                'email' => $email
            ]);

            $response = $this->getClient()->post($url);

            if ($response->successful() && $response->json('success')) {
                Log::info('✅ Client traffic reset successfully', ['email' => $email]);
                return true;
            } else {
                Log::error('❌ Failed to reset client traffic', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'inbound_id' => $inboundId,
                    'email' => $email
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception in resetClientTraffic', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function updateClient(int $inboundId, string $clientId, array $clientData): ?array
    {
        if (empty($this->apiToken)) {
            return ['success' => false, 'msg' => 'API token is not configured.'];
        }

        try {
            $email = $clientData['email'] ?? null;

            if (empty($email)) {
                // Resolve the email from the existing client when not provided
                foreach ($this->getClients($inboundId) as $c) {
                    if (($c['id'] ?? null) === $clientId) {
                        $email = $c['email'] ?? null;
                        break;
                    }
                }
            }

            if (empty($email)) {
                return ['success' => false, 'msg' => 'Client email could not be resolved for update.'];
            }

            // New Sanaei API updates by email and replaces the full row: fetch the
            // existing client first so unknown fields (password/auth/etc.) survive.
            $existing = null;
            foreach ($this->getClients($inboundId) as $c) {
                if (($c['email'] ?? null) === $email || ($c['id'] ?? null) === $clientId) {
                    $existing = $c;
                    break;
                }
            }

            $subId = $clientData['subId'] ?? ($existing['subId'] ?? Str::random(16));

            $clientSettings = array_merge($existing ?? [], [
                'id' => $clientId,
                'email' => $email,
                'flow' => $clientData['flow'] ?? ($existing['flow'] ?? ''),
                'security' => $existing['security'] ?? 'auto',
                'totalGB' => (int) ($clientData['total'] ?? ($existing['totalGB'] ?? 0)),
                'expiryTime' => (int) ($clientData['expiryTime'] ?? ($existing['expiryTime'] ?? 0)),
                'reset' => (int) ($existing['reset'] ?? 0),
                'resetDay' => (int) ($existing['resetDay'] ?? 0),
                'resetMax' => (int) ($existing['resetMax'] ?? 0),
                'trafficReset' => $existing['trafficReset'] ?? 'never',
                'trafficResetDay' => (int) ($existing['trafficResetDay'] ?? 1),
                'limitIp' => (int) ($existing['limitIp'] ?? 0),
                'limitHwid' => (int) ($existing['limitHwid'] ?? 0),
                'group' => $existing['group'] ?? '',
                'comment' => $existing['comment'] ?? '',
                'enable' => true,
                'subId' => $subId,
            ]);

            $updateClientUrl = $this->baseUrl . $this->basePath . '/panel/api/clients/update/' . rawurlencode($email);

            Log::info('Updating XUI client', [
                'url' => $updateClientUrl,
                'inbound_id' => $inboundId,
                'client_id' => $clientId
            ]);

            $response = $this->getClient()->post($updateClientUrl, $clientSettings);

            $responseData = $response->json();

            Log::info('XUI updateClient response', [
                'status' => $response->status(),
                'success' => $responseData['success'] ?? false,
                'msg' => $responseData['msg'] ?? 'N/A'
            ]);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('Exception in XUI updateClient', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'client_id' => $clientId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'msg' => 'Error updating client: ' . $e->getMessage()];
        }
    }
}
