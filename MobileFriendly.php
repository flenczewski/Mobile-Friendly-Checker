<?php
/**
 * Mobile Friendly Multi Checker v 0.1
 *
 * Keywords: Page Speed Insights, API, Multi Curl, mobile ready, mobile friendly
 *
 *
 * Version: 0.1
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

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function addUrl($url)
    {
        $this->urlList[md5($url)] = $url;
    }

    public function showResult()
    {
        $res = $this->_getResult($this->urlList);

        foreach($this->urlList as $key => $url) {

            // pass or not
            if( $res[$key]->pass == 1) {
                echo '<span style="color:green">PASS ('. $res[$key]->score .'/100)</span>';
            } else {
                echo '<span style="color:red;font-weight:bold">ERR ('. $res[$key]->score .'/100)</span>';
            }

            // tested url
            echo " for: ". $url;

            // has redirect?
            if( $url != $res[$key]->testUrl) {
                echo ' (-> ' . $res[$key]->testUrl .')';
            }

            echo "\n";
        }
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
            }
        }

        return $result;
    }

}
