<?php

namespace App\Services\Subdomain;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use \Ovh\Api;
use Illuminate\Http\Request;

/*
 * Use multiple route for the ovh api
 * POST: /domain/zone/{zoneName}/record for create a new record
 * DELETE:  /domain/zone/{zoneName}/record/{id} for delete a record
 * POST: /domain/zone/{zoneName}/refresh for refresh record list
 */
class OvhService
{


    public function create(Request $request) {
        $validated = $request->validate([
            'key' => 'required',
            'secret' => 'required',
            'consumer' => 'required',
            'recordType' => [
                'required',
                Rule::in(['A', 'AAAA','CNAME','SRV']),
            ],
            'record' => 'required',
            'value' => 'required',
            'domain' => 'required',
            'ttl' =>  [
                'required',
            ],
            'protocol' => [
                'nullable',
                Rule::in(['tcp','udp']),
            ],

            'api' => [
                'required',
                Rule::in(['eu', 'us','ca']),
            ],
            'port' => [
                'nullable',
            ],
            'priority' => 'nullable|integer',
            'service' => 'nullable|string',
            'weight' => 'nullable|integer|between:0,65535',
        ]);

        if($validated['recordType'] == 'SRV' &&
            (!isset($validated['priority']) || !isset($validated['service']) || !isset($validated['protocol']) || !isset($validated['weight']) || !isset($validated['port']) || intval($validated['port']) < 1 || intval($validated['port']) > 65535)) {
            return response()->json(['status' => 'error', 'message' => 'You need to enter a protocol,priority,weight and a port for make a SRV record!'], 400);
        }
        $data = [
            'fieldType' => $validated['recordType'],
            'subDomain' => $validated['record'],
            'target' => $validated['value'],
            'ttl' => $validated['ttl']
        ];
       if($validated['recordType'] == 'SRV') {
            $priority = $validated['priority'];
            $weight = $validated['weight'];
            $port = $validated['port'];
            $data['subDomain'] = $validated['service'] . '._' . $validated['protocol'] . '.' . $validated['record'];
            $data['target'] = "$priority " . "$weight " . "$port " . $validated['value'];

        }
        try {
            $ovh = new Api($validated['key'],
                $validated['secret'],
                'ovh-' . $validated['api'],
                $validated['consumer']);
            $request = $ovh->post('/domain/zone/' . $validated['domain'] . '/record', $data);
            $ovh->post('/domain/zone/' . $validated['domain'] . '/refresh');

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $response = Http::post('https://haste.bagou450.com/documents', $responseBodyAsString);
            return response()->json(['status' => 'error', 'message' => 'A error occured with the OVH api.', 'data' => $response->header('X-Document-URL')], 500);
        }
        return response()->json([
            'status' => 'sucess',
            'message' => 'The operation on the record was made successfully.',
            'id' => $request['id']
        ]);
    }
    public function delete(Request $request) {
        $validated = $request->validate([
            'key' => 'required',
            'secret' => 'required',
            'consumer' => 'required',
            'domain' => 'required',
            'record' => 'required',
            'api' => [
                'required',
                Rule::in(['eu', 'us','ca']),
            ]
        ]);
        try {
            $ovh = new Api($validated['key'],
                $validated['secret'],
                'ovh-' . $validated['api'],
                $validated['consumer']);
            $request = $ovh->delete('/domain/zone/' . $validated['domain'] . '/record/' . $validated['record']);
            return response()->json([
                'status' => 'sucess',
                'message' => 'The operation on the record was made successfully.'
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $response = Http::post('https://haste.bagou450.com/documents', $responseBodyAsString);
            return response()->json(['status' => 'error', 'message' => 'A error occured with the OVH api.', 'data' => $response->header('X-Document-URL')], 500);
        }

    }

}