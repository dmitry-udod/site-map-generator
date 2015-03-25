<?php namespace App\Generators;

use App\Contracts\SiteMapGeneratorInterface;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Log;
use App;
use GuzzleHttp\Pool;

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
    protected $level = 0;

    protected $urlsToProcess = [];

    protected $alreadyProcessedUrls = [];

    public function __construct()
    {
        $this->client = new Client();
        $this->sitemap = App::make("sitemap");
    }

    /**
     * Generate site map xml file
     */
    public function generate()
    {
        $this->urlsToProcess = [$this->url];
        while ($this->level <= 5) {
            if(!empty($this->urlsToProcess)) {
                $this->sendPoolRequest($this->urlsToProcess);
            }
            $this->level++;
            Log::debug('Level: ', [$this->level]);
        }

        $this->sitemap->store('xml', 'sitemaps/'. preg_replace("/[^a-zA-Z0-9]/", "", $this->url));
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
                $isRoot = starts_with($link, '/') && !starts_with($link, '//');
                $isStartWithDots = starts_with($link, '../');
                if (
                    (starts_with($link, $this->url) || $isRoot || $isStartWithDots)
                    && $link !== $this->url
                    && !$this->isFileLink($link)
                ) {
                    if ($isRoot) {
                        $link = $this->url . $link;
                    }
                    if ($isStartWithDots) {
                        $link = $this->url . '/' . str_replace('../', '', $link);
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
                        if (!in_array($url, $this->alreadyProcessedUrls)) {
                            $this->sitemap->add($url, Carbon::now(), '0.9', 'daily');
                            $this->sitemap->store('xml', 'mysitemap');
                            $this->alreadyProcessedUrls[] = $url;
                            $this->alreadyProcessedUrls[] = $url . '/';
                        }
                        $this->urlsToProcess += $this->extractLinks($response->getBody()->getContents());
                        Log::debug(array_unique($this->urlsToProcess));
                    }
                },
                function ($error) {
                    Log::error('Exception', [$error->getMessage() ,$error]);
                }
            );
        }
        $this->level++;
    }

    public function sendPoolRequest(array $urls)
    {
        $urls = array_unique($urls);

        foreach ($urls as &$url) {
            if (!in_array($url, $this->alreadyProcessedUrls)) {
                $requests[] = $this->client->createRequest('GET', $url);
                $this->alreadyProcessedUrls[] = $url;
                $this->alreadyProcessedUrls[] = $url . '/';
            }
            unset($url);
        }

        if (!empty($requests)) {
            $results = Pool::batch($this->client, $requests);

            foreach ($results->getSuccessful() as $response) {
                $url = $response->getEffectiveUrl();
                Log::debug('Processed URL:' . $url);
                $this->urlsToProcess = array_merge($this->urlsToProcess, $this->extractLinks($response->getBody()->getContents()));
                $this->urlsToProcess = array_unique($this->urlsToProcess);
//                $this->urlsToProcess += $this->extractLinks($response->getBody()->getContents());
//                $this->urlsToProcess = array_unique($this->urlsToProcess);

                $this->sitemap->add($url, Carbon::now(), '0.9', 'daily');
            }

            foreach ($results->getFailures() as $requestException) {
                Log::error('Exception', [$requestException->getMessage(), $requestException]);
            }

            return $this->urlsToProcess;
        } else {
            $this->urlsToProcess = null;
        }
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

    private function isFileLink($link)
    {
        return str_contains(strtolower($link), ['.jpeg', '.jpg', '.png', '.pdf', '.docs', '.gif', '.zip', '.rar']);
    }
}