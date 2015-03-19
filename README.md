# Mobile-Friendly-Checker
Multi URL's mobile friendly checker (used Page Speed Insights API)

```php
<?php
include_once 'MobileFriendly.php';
$api = new MobileFriendly('Page_Speed_Insights_API_KEY');
$api->addUrl('http://fabian.art.pl/');
$result = $api->execute();

print_r($result);
```

##Result##
```php
Array
(
    [c6172f67cbbc2a6a31289e58fafa7cf5] => stdClass Object
        (
            [score] => 99
            [pass] => true
            [testUrl] => http://fabian.art.pl/
            [url] => http://fabian.art.pl/
        )
)
```
