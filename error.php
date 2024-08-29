<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="/style.css">
  </head>
  <body>
    <div class="center">
      <h1>Error</h1>
      <hr>
      <?php
      if (isset($_GET["errstring"])) {
          echo "<h3>".strip_tags($_GET["errstring"])."</h3>";
      } else {
          header("Location: index.php");
      }
     ?>
     <button type="button" onclick="history.back();">Back</button>
    </div>
  </body>
</html>
