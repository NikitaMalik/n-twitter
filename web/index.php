<?php

require('../vendor/autoload.php');
require_once('TwitterAPIExchange.php');


$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));

// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views',
));

$app->get('/', function() use($app) {

    $settings = array(
        'oauth_access_token' => "",
        'oauth_access_token_secret' => "",
        'consumer_key' => "DslCjVfeCOIyLTPbEdeAkictg",
        'consumer_secret' => "29nTKkY8I8yQtrn8xClRTcP6Bgll48ClswoICimCV18p4qMaoA"
    );
    $twitter = new TwitterAPIExchange($settings);

    if ($_REQUEST['handle'] != null) {

        $handle = ($_REQUEST['handle'] == null ? "bindian0509" : $_REQUEST['handle']);
        $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        $getfield = '?screen_name=' . $handle . '&count=50';
        $requestMethod = 'GET';

        $tweets = $twitter->setGetfield($getfield)
                ->buildOauth($url, $requestMethod)
                ->performRequest();
        $tweets_arr = json_decode($tweets, true);

        $retweets = array();
        
        //print_r($tweets);die;

        foreach ($tweets_arr as $tweets) {
            if (intval($tweets['retweet_count']) > 0) {
                $ctime = explode("+", $tweets['created_at']);
                
                $tmp = array(
                    "tweet" => $tweets['text'], 
                    "url" => "https://twitter.com/" . $tweets['user']['screen_name'] . "/status/" . $tweets['id_str'],
                "user" => $tweets['user']['name'],
                "user_url" => "https://twitter.com/" . $tweets['user']['screen_name'],
                    "created_at" => $ctime[0]
                        );
                array_push($retweets, $tmp);
            }
        }

        $app['monolog']->addDebug('logging output.');
        return $app['twig']->render('retweets.twig', array('retweets' => $retweets, 'handle' => $handle));
    }
    if ($_REQUEST['hashtag'] != null) {

        $hastag = ($_REQUEST['hashtag'] == null ? "geek" : $_REQUEST['hashtag']);


        $url = 'https://api.twitter.com/1.1/search/tweets.json';
        $getfield = '?q=#' . $hastag . '&count=30';
        $requestMethod = 'GET';

        $twitter = new TwitterAPIExchange($settings);
        $response = $twitter->setGetfield($getfield)
                ->buildOauth($url, $requestMethod)
                ->performRequest();

        //print_r($response);die;

        $tweets_arr = json_decode($response, true);

        //echo '<pre>';        print_r($tweets_arr);die;
        
        $hashtagTweets = array();

        foreach ($tweets_arr['statuses'] as $tweets) {
            $ctime = explode("+", $tweets['created_at']);

            $tmp = array(
                "tweet" => $tweets['text'],
                "url" => "https://twitter.com/" . $tweets['user']['screen_name'] . "/status/" . $tweets['id_str'],
                "user" => $tweets['user']['name'],
                "user_url" => "https://twitter.com/" . $tweets['user']['screen_name'],
                "created_at" => "@".$ctime[0]
            );
            array_push($hashtagTweets, $tmp);
        }

        $app['monolog']->addDebug('logging output.');
        return $app['twig']->render('hashtag.twig', array('hashtagTweets' => $hashtagTweets, 'hashtag' => $hastag));
    }
    $app['monolog']->addDebug('logging output.');
    return $app['twig']->render('index.twig', array("action" => $_SERVER['SCRIPT_NAME']));
});

$app->run();
