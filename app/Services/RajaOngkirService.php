<?php

namespace App\Services;

final class RajaOngkirService
{
    private const BASE_URL = 'https://rajaongkir.komerce.id/api/v1';

    public function __construct(private readonly string $apiKey)
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('API key RajaOngkir belum diisi.');
        }
    }

    public function searchDestinations(string $search, int $limit = 10): array
    {
        if (mb_strlen(trim($search)) < 3) {
            return [];
        }

        $response = $this->request('GET', '/destination/domestic-destination?' . http_build_query([
            'search' => trim($search),
            'limit' => min(10, max(1, $limit)),
            'offset' => 0,
        ]));

        return array_map(static fn(array $item): array => [
            'id' => (string) ($item['id'] ?? ''),
            'label' => (string) ($item['label'] ?? $item['subdistrict_name'] ?? $item['district_name'] ?? ''),
            'province' => (string) ($item['province_name'] ?? ''),
            'city' => (string) ($item['city_name'] ?? ''),
            'district' => (string) ($item['district_name'] ?? ''),
            'subdistrict' => (string) ($item['subdistrict_name'] ?? ''),
            'zip_code' => (string) ($item['zip_code'] ?? ''),
        ], $response['data'] ?? []);
    }

    public function calculate(int $origin, int $destination, int $weight, string $couriers): array
    {
        if ($origin <= 0 || $destination <= 0) {
            throw new \RuntimeException('ID lokasi asal atau tujuan belum valid.');
        }

        $response = $this->request('POST', '/calculate/domestic-cost', [
            'origin' => $origin,
            'destination' => $destination,
            'weight' => max(1, $weight),
            'courier' => $couriers,
            'price' => 'lowest',
        ]);

        return array_values(array_map(static fn(array $item): array => [
            'courier' => (string) ($item['code'] ?? $item['courier_code'] ?? ''),
            'courier_name' => (string) ($item['name'] ?? $item['courier_name'] ?? $item['code'] ?? ''),
            'service' => (string) ($item['service'] ?? ''),
            'description' => (string) ($item['description'] ?? ''),
            'cost' => (float) ($item['cost'] ?? 0),
            'etd' => (string) ($item['etd'] ?? ''),
        ], $response['data'] ?? []));
    }

    private function request(string $method, string $path, array $form = []): array
    {
        $headers = ['key: ' . $this->apiKey, 'Accept: application/json'];
        $options = ['http' => ['method' => $method, 'timeout' => 15, 'ignore_errors' => true]];

        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $options['http']['content'] = http_build_query($form);
        }
        $options['http']['header'] = implode("\r\n", $headers);

        $body = @file_get_contents(self::BASE_URL . $path, false, stream_context_create($options));
        $status = (int) preg_replace('/^HTTP\/\S+\s+(\d+).*$/', '$1', $http_response_header[0] ?? 'HTTP/1.1 500');
        $data = is_string($body) ? json_decode($body, true) : null;

        if ($status < 200 || $status >= 300 || !is_array($data)) {
            $message = $data['message'] ?? $data['meta']['message'] ?? 'RajaOngkir tidak dapat dihubungi.';
            throw new \RuntimeException((string) $message);
        }

        return $data;
    }
}
