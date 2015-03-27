<?php namespace App\Generators;

use App\Contracts\SiteMapGeneratorInterface;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use Symfony\Component\DomCrawler\Crawler;
use Log;
use App;
use Session;
use GuzzleHttp\Pool;

class SiteMapGenerator implements SiteMapGeneratorInterface
{
    const SITE_MAPS_DIRECTORY = '/sitemaps';

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

    /**
     * Urls to process
     *
     * @var array
     */
    protected $urlsToProcess = [];

    /**
     * List of already processed URL's
     *
     * @var array
     */
    protected $alreadyProcessedUrls = [];

    /**
     * Max deep level
     *
     * @var int
     */
    protected $maxDeepsLevel;

    /**
     * Custom priorities
     *
     * @var array
     */
    protected $priorities = [];

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
        while ($this->level <= $this->maxDeepsLevel) {
            if(!empty($this->urlsToProcess)) {
                $this->sendPoolRequest($this->urlsToProcess);
            }
            $this->level++;
            Log::debug('Level: ', [$this->level]);
        }

        $this->sitemap->store('xml', self::SITE_MAPS_DIRECTORY .
            '/' .preg_replace("/[^a-zA-Z0-9]/", "", str_replace('http', '', $this->url)));

        Session::flash('success', 'Site map generate successfully');
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
     * Send pool requests
     *
     * @param array $urls
     * @return array
     */
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

                $this->sitemap->add($url, Carbon::now(), $this->priority($url), 'daily');
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

    /**
     * Set max deep level
     *
     * @param int $maxDeepsLevel
     * @return $this
     */
    public function setMaxDeepsLevel($maxDeepsLevel)
    {
        if (empty($maxDeepsLevel)) {
            $maxDeepsLevel = Config::get('settings.deeps_level');
        }

        $this->maxDeepsLevel = $maxDeepsLevel;

        return $this;
    }

    /**
     * Set custom priorities
     *
     * @param array $priorities
     * @return $this
     */
    public function setPriorities(array $priorities)
    {
        $this->priorities['first_level_priority'] = !empty($priorities['first_level_priority']) ? $priorities['first_level_priority'] : '1' ;
        $this->priorities['second_level_priority'] = !empty($priorities['second_level_priority']) ? $priorities['second_level_priority'] : '0.8' ;
        $this->priorities['other_level_priority'] = !empty($priorities['other_level_priority']) ? $priorities['other_level_priority'] : '0.5' ;

        return $this;
    }

    /**
     * Check is file link
     *
     * @param $link
     * @return bool
     */
    private function isFileLink($link)
    {
        return str_contains(strtolower($link), ['.jpeg', '.jpg', '.png', '.pdf', '.docs', '.gif', '.zip', '.rar']);
    }

    /**
     * Get priority
     *
     * @param $url
     * @return string
     */
    private function priority($url)
    {
        $url = parse_url($url);

        if(!empty($url['path'])) {
            $parts = explode('/', $url['path']);
            $level = count($parts);
            if ($level <= 2) {
                return $this->priorities['second_level_priority'];
            } else {
                return $this->priorities['other_level_priority'];
            }
        }

        return $this->priorities['first_level_priority'];
    }
}