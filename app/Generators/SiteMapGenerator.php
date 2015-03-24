<?php namespace App\Generators;

use App\Contracts\SiteMapGeneratorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SiteMapGenerator implements SiteMapGeneratorInterface
{
    public function __construct()
    {
        $this->client = new Client();
    }


    public function generate()
    {

    }

    /**
     * Get main page content
     *
     * @return string
     */
    public function getMainPageContent($url)
    {
        try {
            $response = $this->client->get($url);
        } catch (RequestException $e) {

        }
    }


}