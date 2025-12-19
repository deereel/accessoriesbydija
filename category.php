<?php
// 301 redirect to products.php with the same query string
$queryString = $_SERVER['QUERY_STRING'];

// a 'cat' parameter should be mapped to 'gender' and 'sub' to 'category'
$params = [];
parse_str($queryString, $params);

if (isset($params['cat'])) {
    $params['gender'] = $params['cat'];
    unset($params['cat']);
}

if (isset($params['sub'])) {
    $params['category'] = $params['sub'];
    unset($params['sub']);
}

$newQueryString = http_build_query($params);

$newUrl = 'products.php' . ($newQueryString ? '?' . $newQueryString : '');

header('HTTP/1.1 301 Moved Permanently');
header('Location: ' . $newUrl);
exit();
?>
