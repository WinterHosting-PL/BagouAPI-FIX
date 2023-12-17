<?php

namespace App\Services\Subdomain;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Namecheap\Api;
use Namecheap\Domain\DomainsDns;

class NameCheapService
{
    function checkStatus(object $nameRequest) {
        if (!empty((array)$nameRequest->Errors) || !empty((array)$nameRequest->Warnings)) {
            return response()->json([
                'status' => 'error',
                'message' => 'NameCheap API returned an error during getDNS.',
                'data' => $nameRequest
            ]);
        } else {
            $answer = [
                'status' => 'success',
                'message' => 'The operation on the record was made successfully.',

            ];
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
        $value = $validated['value'];

        $key = $validated['key'];
        $secret = $validated['secret'];

        if($type === 'SRV' && ((!isset($validated['port']) || !$validated['port']) || !isset($validated['priority']) || (!isset($request['protocol']) || !$request['protocol']) || (!isset($validated['service']) || !$validated['service']) || !isset($validated['weight']))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some data are missing for make a SRV record (In Port,Priority,Protocol,Service,Weight).'
            ]);
        }
        $domainData = explode('.', $domain);
        $TLD = end($domainData);
        $SLD = $domainData[0];
        $request = [
            'ApiUser' => $secret,
            'ApiKey' => $key,
            'UserName'=> $secret,
            'ClientIp'=> "185.25.205.205",
            'Command' => "namecheap.domains.dns.getHosts",
            'SLD' => $SLD,
            'TLD' => $TLD
        ];
        $urlString = '';
        foreach ($request as $key => $value) {
            $urlString .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $urlString = rtrim($urlString, '&');

        $allHost = Http::get("https://api.sandbox.namecheap.com/xml.response?$urlString");
        $xml = simplexml_load_string($allHost->body());
        $json = json_encode($xml);
        $allHost = json_decode($json);
        if (!empty((array)$allHost->Errors) || !empty((array)$allHost->Warnings)) {
            return response()->json([
                'status' => 'error',
                'message' => 'NameCheap API returned an error during getDNS.',
                'data' => $allHost
            ]);
        }
        $hostList = [];
        $recordList = [];
        $addressList = [];
        $ttlList = [];
        $MXPrefList = [];
        foreach ($allHost->CommandResponse->DomainDNSGetHostsResult->host as $host) {
            $host = $host->{'@attributes'};
            $hostList[] = $host->Name;
            $recordList[] = $host->Type;
            $addressList[] = $host->Address;
            $ttlList[] = $host->TTL;
            $MXPrefList[] = $host->MXPref;
        }
        $RequestValues = [];
        for ($i = 0; $i < count($hostList); $i++) {
            $RequestValues[] = [
                "Key" => "HostName" . ($i + 1),
                "Value" => $hostList[$i]
            ];
            $RequestValues[] = [
                "Key" => "RecordType" . ($i + 1),
                "Value" => $recordList[$i]
            ];
            $RequestValues[] = [
                "Key" => "Address" . ($i + 1),
                "Value" => $addressList[$i]
            ];
            $RequestValues[] = [
                "Key" => "TTL" . ($i + 1),
                "Value" => $ttlList[$i]
            ];

            // Ajouter MXPref si le type d'enregistrement est MX
            if (isset($MXPrefList[$i]) && $recordList[$i] == "MX") {
                $RequestValues[] = [
                    "Key" => "MXPref" . ($i + 1),
                    "Value" => $MXPrefList[$i]
                ];
            }
        }
        $i = count($hostList);
        if($type === 'SRV') {
            $record = $validated['service'] . '._' . $validated['protocol'] . ".$record";
            $value = $validated['priority'] . ' ' . $validated['weight'] . ' ' . $validated['port'] . ' ' . $value;
        }
        $RequestValues[] = ["Key" => "HostName" . ($i + 1), "Value" => $record];
        $RequestValues[] = ["Key" => "RecordType" . ($i + 1), "Value" => $validated['recordType']];
        $RequestValues[] = ["Key" => "Address" . ($i + 1), "Value" => $value];
        $RequestValues[] = ["Key" => "TTL" . ($i + 1), "Value" => $validated['ttl']];
        $request['Command'] = 'namecheap.domains.dns.setHosts';
        $urlString = '';
        foreach ($RequestValues as $item) {
            $urlString .= urlencode($item["Key"]) . '=' . urlencode($item["Value"]) . '&';
        }
        foreach ($request as $key => $value) {
            $urlString .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $urlString = rtrim($urlString, '&');
        $nameCheapRequest = Http::get("https://api.sandbox.namecheap.com/xml.response?$urlString");
        $xml = simplexml_load_string($nameCheapRequest->body());
        $json = json_encode($xml);
        $nameCheapRequest = json_decode($json);
        return $this->checkStatus($nameCheapRequest);
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
            ],
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
                'message' => 'Some data are missing for delete a SRV record (In Port,Priority,Protocol,Service,Weight).'
            ]);
        }
        $domainData = explode('.', $domain);
        $TLD = end($domainData);
        $SLD = $domainData[0];
        $request = [
            'ApiUser' => $secret,
            'ApiKey' => $key,
            'UserName'=> $secret,
            'ClientIp'=> "185.25.205.205",
            'Command' => "namecheap.domains.dns.getHosts",
            'SLD' => $SLD,
            'TLD' => $TLD
        ];
        $urlString = '';
        foreach ($request as $key => $value) {
            $urlString .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $urlString = rtrim($urlString, '&');

        $allHost = Http::get("https://api.sandbox.namecheap.com/xml.response?$urlString");
        $xml = simplexml_load_string($allHost->body());
        $json = json_encode($xml);
        $allHost = json_decode($json);
        if (!empty((array)$allHost->Errors) || !empty((array)$allHost->Warnings)) {
            return response()->json([
                'status' => 'error',
                'message' => 'NameCheap API returned an error during getDNS.',
                'data' => $allHost
            ]);
        }
        $hostList = [];
        $recordList = [];
        $addressList = [];
        $ttlList = [];
        $MXPrefList = [];
        foreach ($allHost->CommandResponse->DomainDNSGetHostsResult->host as $host) {
            $host = $host->{'@attributes'};
            $hostList[] = $host->Name;
            $recordList[] = $host->Type;
            $addressList[] = $host->Address;
            $ttlList[] = $host->TTL;
            $MXPrefList[] = $host->MXPref;
        }
        if($type === 'SRV') {
            $record = $validated['service'] . '._' . $validated['protocol'] . ".$record";
        }
        $RequestValues = [];
        for ($i = 0; $i < count($hostList); $i++) {
            if($hostList[$i] === $record && $recordList[$i] == $validated['recordType']) {
                continue;
            }
            $RequestValues[] = [
                "Key" => "HostName" . ($i + 1),
                "Value" => $hostList[$i]
            ];
            $RequestValues[] = [
                "Key" => "RecordType" . ($i + 1),
                "Value" => $recordList[$i]
            ];
            $RequestValues[] = [
                "Key" => "Address" . ($i + 1),
                "Value" => $addressList[$i]
            ];
            $RequestValues[] = [
                "Key" => "TTL" . ($i + 1),
                "Value" => $ttlList[$i]
            ];

            if (isset($MXPrefList[$i]) && $recordList[$i] == "MX") {
                $RequestValues[] = [
                    "Key" => "MXPref" . ($i + 1),
                    "Value" => $MXPrefList[$i]
                ];
            }
        }

        $request['Command'] = 'namecheap.domains.dns.setHosts';
        $urlString = '';
        foreach ($RequestValues as $item) {
            $urlString .= urlencode($item["Key"]) . '=' . urlencode($item["Value"]) . '&';
        }
        foreach ($request as $key => $value) {
            $urlString .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $urlString = rtrim($urlString, '&');
        $nameCheapRequest = Http::get("https://api.sandbox.namecheap.com/xml.response?$urlString");
        $xml = simplexml_load_string($nameCheapRequest->body());
        $json = json_encode($xml);
        $nameCheapRequest = json_decode($json);
        return $this->checkStatus($nameCheapRequest);
    }

}