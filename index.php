<?php

$envFilePath = __DIR__ . '/.env';

if (!file_exists($envFilePath)) {
  die('.env file not found.');
}

$envContent = file_get_contents($envFilePath);
$lines = explode("\n", $envContent);

foreach ($lines as $line) {
  $line = trim($line);
  if (!empty($line) && strpos($line, '=') !== false) {
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    putenv("$key=$value");
    $_ENV[$key] = $value;
  }
}

require_once('wordpress-api.class.php');

$wordpressApi = new WordpressApi(getenv('WORDPRESS_DOMAIN'), getenv('WORDPRESS_USERNAME'), getenv('WORDPRESS_PASSWORD'));

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add post to Wordpress</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
  <div class="container mt-5">
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" and !empty($_POST['title']) and !empty($_POST['lid']) and !empty($_POST['content'])) {
      $thumbnail_id = null;
      if (!empty($_POST['thumb'])) {
        try {
          $thumbnail_id = $wordpressApi->addMedia($_POST['thumb'], $_POST['title'])['id'];
        } catch (\Exception $e) {
          echo ('<p>Error sending image: ' . $e->getMessage() . '</p>');
        }
      }
      $tags = [];
      if (!empty($_POST['tags'])) {
        $new_tags = explode(',', $_POST['tags']);
        foreach ($new_tags as $tag) {
          try {
            $tags[] = $wordpressApi->addTag($tag)['id'];
            echo ('<p>Tag created successfully: ' . $new_tag_name . '</p>');
          } catch (\Exception $e) {
            echo ('<p>Error creating tag: ' . $e->getMessage() . '</p>');
          }
        }
      }

      try {
        $post = $wordpressApi->addArticle($_POST['title'], $_POST['content'], $_POST['lid'], $tags, $thumbnail_id);
        echo('<p>You have successfully added an article. Post ID: ' . $post['id'] . '</p>');
      } catch (\Exception $e) {
        echo('<p>Error: ' . $e->getMessage() . '</p>');
      }
    }
    ?>
  
    <h1 class="mb-4">Add post to Wordpress</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <div class="mb-3">
        <label for="title" class="form-label">Title:</label>
        <input type="text" name="title" required class="form-control" id="title">
      </div>

      <div class="mb-3">
        <label for="lid" class="form-label">LID:</label>
        <textarea name="lid" rows="2" required class="form-control" id="lid"></textarea>
      </div>

      <div class="mb-3">
        <label for="content" class="form-label">Content (in HTML):</label>
        <textarea name="content" rows="4" required class="form-control" id="content"></textarea>
      </div>

      <div class="mb-3">
        <label for="tags" class="form-label">Tags (enter after comma):</label>
        <input type="text" name="tags" class="form-control" id="tags">
      </div>

      <div class="mb-3">
        <label for="thumb" class="form-label">Link to thumb:</label>
        <input type="url" name="thumb" class="form-control" id="thumb">
      </div>

      <button type="submit" class="btn btn-primary">Send</button>
    </form>
  </div>

</body>

</html>