<?php namespace App\Generators;

use App\Contracts\SiteMapGeneratorInterface;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Log;
use App;

class SiteMapGenerator implements SiteMapGeneratorInterface
{
    /**
     * Site URL
     *
     * @var string|null
     */
    protected $url = null;

    /**
     * Level of immersion
     *
     * @var int
     */
    protected $level = 1;

    public function __construct()
    {
        $this->client = new Client();
        $this->sitemap = App::make("sitemap");
    }

    public function generate()
    {
        $now = Carbon::now();
        $this->sitemap->add($this->url, $now, '1.0', 'daily');

        while ($this->level < 2) {
            $html = $this->getPageContent($this->url);
            $urls = $this->extractLinks($html);
            $this->runAsyncQueries($urls);
        }

        $this->sitemap->store('xml', 'mysitemap');
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
                $isRoot = starts_with($link, '/');
                if ((starts_with($link, $this->url) || $isRoot) && $link !== $this->url) {
                    if ($isRoot) {
                        $link = $this->url . $link;
                    }
                    $links[] = $link;
                }
            }
        });

        return $links;
    }

    /**
     * Send async requests
     *
     * @param array $urls
     */
    public function runAsyncQueries(array $urls)
    {
        foreach ($urls as $url) {
            $response = $this->client->get($url, ['future' => true]);

            $response->then(
                function ($response) use ($url) {
                    if ($response->getStatusCode() === 200) {
                        $this->sitemap->add($url, Carbon::now(), '0.9', 'daily');
                        $this->sitemap->store('xml', 'mysitemap');
                    }
                },
                function ($error) {
                    Log::error('Exception', [$error->getMessage() ,$error]);
                }
            );
        }
        $this->level++;
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