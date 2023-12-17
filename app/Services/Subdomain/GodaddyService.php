<?php

namespace App\Services\Subdomain;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class GodaddyService
{
    function checkStatus(object $daddyRequest) {
        $status = $daddyRequest->status();
        switch ($status) {
            case 400:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Request was malformed.',
                    'data' => $response->header('X-Document-URL')
                ]);
            case 401:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Authentication info not sent or invalid.',
                    'data' => $response->header('X-Document-URL')
                ]);
            case 403:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Authenticated user is not allowed access.',
                    'data' => $response->header('X-Document-URL')
                ]);
            case 404:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Resource not found.',
                    'data' => $response->header('X-Document-URL')
                ]);
            case 422:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Record does not fulfill the schema.',
                    'data' => $response->header('X-Document-URL')
                ]);
            case 429:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Too many requests received within interval!',
                    'data' => $response->header('X-Document-URL')
                ]);
            case 500:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Internal server error.',
                    'data' => $response->header('X-Document-URL')
                ]);
            case 504:
                $response = Http::post('https://haste.bagou450.com/documents', $daddyRequest->json());
                return response()->json([
                    'status' => 'error',
                    'message' => 'GoDaddy API returned an error. Gateway Timeout.',
                    'data' => $response->header('X-Document-URL')
                ]);
            default:
                return response()->json([
                    'status' => 'sucess',
                    'message' => 'The operation on the record was made successfully.'
                ]);
        }
    }

    public function create(Request $request) {
        $validated = $request->validate([
            'key' => 'required',
            'secret' => 'required',
            'domain' => 'required',
            'record' => 'required',
            'recordType' => [
                'required',
                Rule::in(['A', 'AAAA','CNAME','SRV']),
            ],
            'value' => 'required',
            'ttl' =>  [
                'required',
                'integer',
                'between:600,86400',
            ],
            'port' => [
                'nullable',
                'integer',
                'numeric',
                'between:1,65535',
            ],
            'protocol' => [
                'nullable',
                Rule::in(['TCP','tcp','udp', 'UDP']),
            ],
            'priority' => 'nullable|integer',
            'weight' => 'nullable|integer|between:0,65535',
            'service' => 'nullable|string'
        ]);

        $domain = $validated['domain'];
        $type = $validated['recordType'];
        $record = $validated['record'];
        $key = $validated['key'];
        $secret = $validated['secret'];

        if($type === 'SRV' && ((!isset($validated['port']) || !$validated['port']) || !isset($validated['priority']) || (!isset($request['protocol']) || !$request['protocol']) || (!isset($validated['service']) || !$validated['service']) || !isset($validated['weight']))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data are missing for make a SRV record (In Port,Priority,Protocol,Service,Weight).'
            ]);
        }
        $putData = [
            'data' => $validated['value'],
            'ttl' => intval($validated['ttl'])
        ];
        if($type == 'SRV') {
            $putData = $putData + [
                'port' => intval($validated['port']) ,
                'priority' => intval($validated['priority']) ,
                'protocol' => '_' . $validated['protocol'] ,
                'service' => $validated['service'] ,
                'weight' => intval($validated['weight'])
            ];
        }
        $daddyRequest = Http::withHeaders([
            'Authorization' => "sso-key $key:$secret"
        ])->put("https://api.godaddy.com/v1/domains/$domain/records/$type/$record", array($putData));
        return $this->checkStatus($daddyRequest);
    }
    public function delete(Request $request) {
        $validated = $request->validate([
            'key' => 'required',
            'secret' => 'required',
            'domain' => 'required',
            'record' => 'required',
            'recordType' => [
                'required',
                Rule::in(['A', 'AAAA','CNAME','SRV']),
            ]
        ]);
        $domain = $validated['domain'];
        $type = $validated['recordType'];
        $record = $validated['record'];
        $key = $validated['key'];
        $secret = $validated['secret'];
        $daddyRequest = Http::withHeaders([
            'Authorization' => "sso-key $key:$secret"
        ])->delete("https://api.godaddy.com/v1/domains/$domain/records/$type/$record");
        return $this->checkStatus($daddyRequest);
    }

}