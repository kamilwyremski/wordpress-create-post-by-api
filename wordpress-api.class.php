<?php

class WordpressApi
{
  private $domain;
  private $username;
  private $password;

  public function __construct(string $domain, string $username, string $password)
  {
    $this->domain = $domain;
    $this->username = $username;
    $this->password = $password;
  }

  function addMedia(string $url, string $filename = ''){
    $image_data = file_get_contents($url);
    if ($image_data === false) {
      throw new \Exception('Error fetching image from URL.');
    }
    $boundary = uniqid();
    if(!$filename){
      $filename = pathinfo(basename($url), PATHINFO_FILENAME);
    }
    $filename = mb_strtolower($filename);
    $data = "--$boundary\r\n" .
      "Content-Disposition: form-data; name=\"file\"; filename=\"".$filename.".jpg\"\r\n" .
      "Content-Type: image/jpeg\r\n\r\n" .
      "$image_data\r\n" .
      "--$boundary--\r\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->domain . '/wp-json/wp/v2/media');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Basic '. base64_encode($this->username . ':' . $this->password),
      'Content-Type: multipart/form-data; boundary=' . $boundary,
      'Content-Length: ' . strlen($data),
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    if ($response === false) {
      throw new \Exception(curl_error($ch));
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code == 201) {
      return json_decode($response ,true);
    } else {
      throw new \Exception($http_code);
    }
  }

  function addTag(string $tag){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->domain . '/wp-json/wp/v2/tags');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Basic '. base64_encode($this->username . ':' . $this->password),
      'Content-Type: application/json; charset=utf-8',
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
      'name' => trim($tag)
    ]));
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
      $new_tag = json_decode($response, true);
      if(!empty($new_tag['id'])){
        return $new_tag;
      }else{
        throw new \Exception('Error creating tag');
      }
    } else {
      throw new \Exception('Error creating tag');
    }
  }

  private function replaceImageLinks(array $matches) {
    $imageData = $this->addMedia($matches[1]);
    return str_replace($matches[1], $imageData['guid']['rendered'], $matches[0]);
  }

  function addArticle(string $title, string $body = '', string $lid = '', array $tags = [], string $thumbnail_id = null){

    $pattern = '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/';
    $body = preg_replace_callback($pattern, [$this, 'replaceImageLinks'], $body);

    $postfields = json_encode([
      'status' => 'publish',
      'tags' => $tags,
      'title' => $title,
      'content' => $body,
      'excerpt' => $lid,
      'featured_media' => $thumbnail_id
      ]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->domain . '/wp-json/wp/v2/posts');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Basic '. base64_encode($this->username . ':' . $this->password),
      'Content-Type: application/json; charset=utf-8',
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
      $post = json_decode($response, true);
      if (!empty($post['id'])) {
        return $post;
      } else {
        throw new \Exception($post['message']);
      }
    }else{
      throw new \Exception('Error communicating with WordPress API.');
    }
  }
}