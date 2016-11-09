<?php

namespace Flachesis\Cpc;

use Goutte\Client;

class CpcLocation
{
    const CPC_SERVICE_CENTER = 'http://www.fetc.net.tw/ServiceCenter-cpc/cpc.html';
    const GOOGLE_GEO_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    protected $gapiKey;
    protected $crawlClient;
    protected $guzzleClient;

    public function __construct($gapiKey)
    {
        $this->gapiKey = $gapiKey;

        $this->crawlClient = new Client();
        $this->guzzleClient = $this->crawlClient->getClient();
    }

    protected function retrievalCplRawInfo()
    {
        $crawler = $this->crawlClient->request('GET', static::CPC_SERVICE_CENTER);
        $rawInfo = $crawler->filter('span.text_0922')->each(function ($node, $i) {
            return $node->text();
        });

        $cpcInfo = array();

        for ($i = 0; $i < count($rawInfo); $i += 3) {
            $cpcInfo[] = array(
                'name' => $rawInfo[$i],
                'addr' => $rawInfo[$i + 1],
                'time' => $rawInfo[$i + 2],
            );
        }

        return $cpcInfo;

    }

    protected function getCoordinates($addr)
    {
        $resp = $this->guzzleClient->request('GET', static::GOOGLE_GEO_URL, [
            'query' => [
                'address' => $addr,
                'region' => 'tw',
                'key' => $this->gapiKey,
            ],
        ]);

        $content = $resp->getBody()->getContents();
        $json = json_decode($content);

        if (!isset($json->results[0])) {
            return null;
        }

        return $json->results[0]->geometry->location;
    }

    public function getCpcInfo()
    {
        $cpcInfo = $this->retrievalCplRawInfo();

        foreach ($cpcInfo as $key => $info) {
            $cpcInfo[$key]['location'] = $this->getCoordinates($info['addr']);
            sleep(1);
        }

        return $cpcInfo;
    }
}
