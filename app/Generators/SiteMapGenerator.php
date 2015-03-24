<?php namespace App\Generators;

use App\Contracts\SiteMapGeneratorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Log;

class SiteMapGenerator implements SiteMapGeneratorInterface
{
    /**
     * Site URL
     *
     * @var string|null
     */
    protected $url = null;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function generate()
    {

    }

    /**
     * Get page content
     *
     * @param $url
     * @return string
     * @throws \Exception
     */
    public function getPageContent($url)
    {
        try {
            $response = $this->client->get($url);
        } catch (RequestException $e) {
            Log::error('Cant resolve host', [$e]);
            throw new \Exception($e->getMessage());
        }

        if ($response->getStatusCode() === 200 ) {
            return $response->getBody()->getContents();
        } else {
            Log::error('Cant get page content', ['code' => $response->getStatusCode(), $response]);
            return '';
        }
    }

    /**
     * Get all links for specified domain
     *
     * @param $html
     * @return array
     */
    public function extractLinks($html)
    {
        $links = [];
        $crawler = new Crawler($html);
        $crawler->filter('a')->each(function (Crawler $node, $i) use (&$links) {
            $link = $node->extract('href');
            if (!is_null($link = $link[0])) {
                if (starts_with($link, $this->url) && $link !== $this->url) {
                    $links[] = $link;
                }
            }
        });

        return $links;
    }

    /**
     * Set site URL
     *
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }
}