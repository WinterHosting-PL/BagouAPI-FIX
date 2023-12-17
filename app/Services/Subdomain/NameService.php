<?php

namespace App\Services\Subdomain;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class NameService
{
    function checkStatus(object $nameRequest, bool $id) {
        $status = $nameRequest->status();
        if ($status >= 400 && $status <= 499) {
            $jsonData = $nameRequest->json();
            $response = Http::post('https://haste.bagou450.com/documents', $jsonData);
            if ($response->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Name API returned an error. Request was malformed.',
                    'data' => $response->header('X-Document-URL')
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Name API returned an error. Request was malformed.',
                    'data' => 'https://haste.bagou450.com'
                ]);
            }

        } else {
            $answer = [
                'status' => 'success',
                'message' => 'The operation on the record was made successfully.',

            ];
            if($id) {
                $answer['id'] = $nameRequest->json()['id'];
            }
            return response()->json($answer);
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
            'answer' => $validated['value'],
            'type' => $type,
            'host' => $record,
            'ttl' => intval($validated['ttl'])
        ];
        if($type == 'SRV') {
            $putData['host'] =  $validated['service'] . '._' . $validated['protocol'] . '.' . $validated['value'];
            $putData['answer'] =  $validated['weight'] . ' ' . $validated['port'] . ' ' . $validated['value'];

            $putData = $putData + [
                'port' => intval($validated['port']) ,
                'priority' => intval($validated['priority']) ,
                'protocol' => '_' . $validated['protocol'] ,
                'service' => $validated['service'] ,
                'weight' => intval($validated['weight'])
            ];
        }
        $nameRequest = Http::withBasicAuth($secret, $key)->withHeaders(['Content-Type' => 'application/json'])->post("https://api.name.com/v4/domains/$domain/records", $putData);
        return $this->checkStatus($nameRequest, true);
    }
    public function delete(Request $request) {
        $validated = $request->validate([
            'key' => 'required',
            'secret' => 'required',
            'domain' => 'required',
            'record' => 'required'
        ]);
        $record = $validated['record'];
        $domain = $validated['domain'];
        $key = $validated['key'];
        $secret = $validated['secret'];
        $nameRequest = Http::withBasicAuth($secret, $key)->withHeaders(['Content-Type' => 'application/json'])->delete("https://api.name.com/v4/domains/$domain/records/$record");

        return $this->checkStatus($nameRequest, false);
    }

}