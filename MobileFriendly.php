<?php
/**
 * Mobile Friendly Multi Checker v 0.2
 *
 * Keywords: Page Speed Insights, API, Multi Curl, mobile ready, mobile friendly
 *
 * Version: 0.2
 * Author: fabian.lenczewski@gmail.com
 * Since: 2015-03-18 19:36
 */
class MobileFriendly {

    /**
     * Page Speed Insights API Key
     * @var string
     */
    public $apiKey;

    protected $urlList= array();

    protected $packSize = 10;

    /**
     * @var mamcached handler
     */
    private $_memcache;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->_memcache = new Memcache;
        $this->_memcache->connect('localhost', '11211');
    }

    public function addUrl($url)
    {
        $url = trim($url);
        if( substr($url, 0, 1) != ';' ) {
            $this->urlList[md5($url)] = $url;
        }
    }

    public function addUrlList($urlList)
    {
        if(count($urlList) > 0) {
            foreach($urlList as $url) {
                $this->addUrl($url);
            }
        }
    }

    public function execute($regenerate = false)
    {
        // memcached key
        $mcKey = md5('MobileFriendly-URLS-' . serialize($this->urlList));

        $res = $this->_memcache->get($mcKey);
        if ( !$res || $regenerate) {
            $res = $this->_getResult($this->urlList);
            $this->_memcache->set($mcKey, $res, false, 12*3600);
        }

        return $res;
    }

    private function _getResult($urlArray)
    {
        // chunk all urls list to packSize
        $packList = array_chunk($urlArray, $this->packSize, true);

        foreach($packList as $pack) {

                $ch = array();
                $mh = curl_multi_init();
                foreach ($pack as $key => $url) {
                    $apiUrl = 'https://www.googleapis.com/pagespeedonline/v3beta1/mobileReady?url=' . urlencode($url)
                        . '&screenshot=false&snapshots=true&fields=id%2CruleGroups&strategy=mobile&key=' . $this->apiKey;
                    $ch[$key] = curl_init($apiUrl);
                    curl_setopt($ch[$key], CURLOPT_HEADER, 0);
                    curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch[$key], CURLOPT_TIMEOUT, 300);
                    curl_setopt($ch[$key], CURLOPT_FOLLOWLOCATION, true);
                    curl_multi_add_handle($mh, $ch[$key]);
                }

                $running = null;
                do {
                    curl_multi_exec($mh, $running);
                } while ($running);

                foreach ($ch as $key => $handler) {
                    $res = json_decode(curl_multi_getcontent($ch[$key]));
                    curl_multi_close($ch[$key]);

                    $result[$key] = $res->ruleGroups->USABILITY;
                    $result[$key]->testUrl = $res->id;
                    $result[$key]->url = $urlArray[$key];
                    if(!isset($result[$key]->score)) {
                        $result[$key]->error = $res;
                    }
                }
            }

        return $result;
    }

}
