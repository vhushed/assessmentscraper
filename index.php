<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,
    'https://www.wired.com/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36");
$output = curl_exec($ch);
curl_close($ch);

?>

<!DOCTYPE html>
<head>
    <title>Scraper</title>
    <style>
        table{
            width: 90%;
            border-collapse: collapse;
            height: 20px;
        }
        td{
            margin:20px 0;
            padding:5px;
            border: none;
        }
    </style>
</head>

<body>
    <table>

    <?php
    $articles = [];
    $document = new DOMdocument();
    libxml_use_internal_errors(true);
    $document->loadHTML($output);
    $elements = $document->getElementsByTagName("a");
    $cutoffTimestamp = strtotime('2022-01-01');
    foreach ($elements as $element) {
        $isArticleLink = false;
        $text = $element -> nodeValue;
        $href = $element -> getAttribute('href');

        if (strlen($element->nodeValue) > 120 || strlen($element->nodeValue) < 20) {
            continue;
        }


        $skipPhrases = ['Autocomplete Interview','Skip to main content','Your California Privacy Rights','Czech Republic & Slovakia','Steven Levy\'s Plaintext Column','WIRED Classics from the Archive'];
        foreach ($skipPhrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                continue 2;
            }
        }

        #true if the previous conditions pass
        $isArticleLink = true;

        #wired has /story, /review urls, which i add to the original
        $wiredUrl = "https://www.wired.com";
        if (strpos($href,'https')!==0){
            $wiredUrl = $wiredUrl . $href;
            $href = $wiredUrl;
        }
        

        if ($isArticleLink) {
            $datetime = "";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,
                $href);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36");
            $html = curl_exec($ch);
            curl_close($ch);
            
            if (!$html){
                $datetime = "Unknown Date";

            }else{
                $document = new DOMdocument();
                libxml_use_internal_errors(true);
                $document->loadHTML($html);
                $time = $document->getElementsByTagName('time');
                $datetime = "Unknown Date";
                # example of wired's time tag<time data-testid="ContentHeaderPublishDate" datetime="2025-03-18T18:02:26-04:00"
                foreach ($time as $times) {
                    if ($times->hasAttribute('data-testid') && $times->getAttribute('data-testid') === "ContentHeaderPublishDate") {
                        $datetime= $times->getAttribute('datetime');
                    }
                }
            }

            $timestamp = strtotime($datetime);
            if ($timestamp === false) {
                $timestamp = 0; 
            }
            if ($timestamp !== 0 && $timestamp < $cutoffTimestamp) {
                continue;
            }
        
            $articles[] = [
                'title' => $text,
                'url' => $href,
                'datetime' => $datetime,
                'timestamp' => $timestamp
            ];


        }
    }
    usort($articles, function ($a, $b) {
        return $b['timestamp'] - $a['timestamp']; 
    });


    foreach ($articles as $article) {
        echo "<tr>";
        echo "<td>{$article['title']}</td>";
        echo "<td><a href='{$article['url']}'>{$article['url']}</a></td>";
        echo "<td><p>{$article['datetime']}</p></td>";
        echo "</tr>";
    }
    ?>

    

    
    </table>
</body>
</html>