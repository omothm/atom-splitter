<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

  <title>AtomSplitter Test</title>
  <style>
    .page {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      padding: 12px;
      overflow-x: hidden;
    }

    #feed-selection {
      transition: opacity 180ms;
    }

    #feed-viewer {
      display: none;
      opacity: 0;
      transition: opacity 180ms;
    }

    #feed-viewer-author {
      font-style: italic;
    }

    .feed-title {
      font-weight: bold;
      cursor: pointer;
    }

    .feed-author {
      font-style: italic;
      margin-left: 10px;
    }

    .feed-content {
      display: none;
    }
  </style>
</head>

<body class="bg-light">
  <div class="page" id="feed-selection">
    <div class="container">
      <h1 class="text-primary">AtomSplitter Feed</h1>
      <?php
      require __DIR__ . "/../src/AtomSplitter.php";

      use \com\omothm\AtomSplitter;

      $url = "https://en.blog.wordpress.com/feed/atom/";
      $splitter = new AtomSplitter($url);
      $ret = $splitter->run();
      if ($ret == TRUE) {
        $entries = $splitter->get_entries();
        echo "<ul>";
        foreach ($entries as $entry) {
          echo "<li><span class=\"feed-title\">" . $entry["title"] . "</span>";
          echo "<span class=\"feed-author\">" . $entry["authors"][0]["name"] . "</span>";
          echo "<span class=\"feed-content\">" . $entry["content"] . "</span></li>";
        }
        echo "</ul>";
      } else {
        echo "<p>" . $splitter->get_error() . "</p>";
      }
      ?>
    </div>
  </div>
  <div class="page" id="feed-viewer">
    <div class="container">
      <div id="feedmodal-titlebar">
        <button type="button" class="btn btn-secondary" onclick="back()">Back</button>
        <hr>
        <h1 id="feed-viewer-title" class="text-primary">Title</h1>
        <h2 id="feed-viewer-author" class="text-secondary">Author</h2>
        <p id="feed-viewer-content">Content</p>
      </div>
    </div>
  </div>

  <script>
    var selectors = document.getElementsByClassName("feed-title");
    var i;
    for (i = 0; i < selectors.length; i++) {
      selectors[i].addEventListener("click", function() {
        showfeed(this);
      });
    }

    function showfeed(element) {
      document.getElementById("feed-viewer-title").innerHTML = element.innerHTML;
      document.getElementById("feed-viewer-author").innerHTML = element.nextElementSibling.innerHTML;
      document.getElementById("feed-viewer-content").innerHTML = element.nextElementSibling.nextElementSibling.innerHTML;
      document.getElementById("feed-viewer").style.display = "block";
      document.getElementById("feed-viewer").style.opacity = "1";
      document.getElementById("feed-selection").style.opacity = "0";
      setTimeout(function() {
        document.getElementById("feed-selection").style.display = "none";
      }, 180);
    }

    function back() {
      document.getElementById("feed-selection").style.display = "block";
      document.getElementById("feed-selection").style.opacity = "1";
      document.getElementById("feed-viewer").style.opacity = "0";
      setTimeout(function() {
        document.getElementById("feed-viewer").style.display = "none";
      }, 180);
    }
  </script>

  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
</body>

</html>