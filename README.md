# atom-splitter
A PHP library to retrieve and parse Atom feed from a URL.
## What It Does
AtomSplitter fetches Atom feed from a given URL (using cURL) and parses its feed entries into a PHP array. For example, a feed like this:
```xml
<entry>
  <title>Some Title</title>
  <link href="http://example.org/2012/12/12/some-title"/>
  <id>12345</id>
  <updated>2012-12-12T12:12:12Z</updated>
  <author>
    <name>Omar Othman</name>
    <uri>http://omothm.com</uri>
    <email>omar@omothm.com</email>
  </author>

  <content>Some text.</content>
</entry>

<entry>
  <title>Another Title</title>
  <link href="http://example.org/2012/12/13/another-title"/>
  <id>67890</id>
  <updated>2012-12-13T12:12:12Z</updated>
  <author>
    <name>Author 1</name>
  </author>
  <author>
    <name>Author 2</name>
  </author>
  <content>Some text.</content>
</entry>
```
would be converted into this:
```
Array
(
    [0] => Array
        (
            [authors] => Array
                (
                    [0] => Array
                        (
                            [name] => Omar Othman
                            [uri] => http://omothm.com
                            [email] => omar@omothm.com
                        )
                )
            [title] => Some Title
            [link] => http://example.org/2012/12/12/some-title
            [id] => 12345
            [updated] => 2012-12-12T12:12:12Z
            [content] => Some text.
        )
    [1] => Array
        (
            [authors] => Array
                (
                    [0] => Array
                        (
                            [name] => Author 1
                        )
                    [1] => Array
                        (
                            [name] => Author 2
                        )
                )
            [title] => Another Title
            [link] => http://example.org/2012/12/13/another-title
            [id] => 67890
            [updated] => 2012-12-13T12:12:12Z
            [content] => Some text.
        )
)
```
**Note:** This tool parses `<entry>` nodes only. Any nodes other than `<entry>` are ignored.
## Usage
1. After adding `AtomSplitter.php` to your website (let's say in `php/`), import it into the page where you want to use it.
   ```php
   require "php/AtomSplitter.php";
   use \com\omothm\AtomSplitter;
   ```
2. Initialize an AtomSplitter object with the URL to the atom feed and run it.
   ```php
   $url = "https://en.blog.wordpress.com/feed/atom/";
   $splitter = new AtomSplitter($url);
   $ret = $splitter->run();
   ```
3. If successful, `$ret` will be `TRUE`. Retrieve the entries array.
   ```php
   if ($ret === TRUE) {
     $entries = $splitter->get_entries();
   } else {
     $error = $splitter->get_error();
   }
   ```
4. Get individual information about each entry.
   ```php
   // Get information about the first entry (index 0)
   $title   = $entries[0]["title"];
   $link    = $entries[0]["link"];
   $id      = $entries[0]["id"];
   $updated = $entries[0]["updated"];
   $content = $entries[0]["content"];
   $authors = $entries[0]["authors"];
   
   $first_author_name  = $authors[0]["name"]; // or $entries[0]["authors"][0]["name"]
   $first_author_uri   = $authors[0]["uri"];
   $first_author_email = $authors[0]["email"];
   ```
   The information shown here about each entry are all the information parsed about that entry. Other information are ignored.
## Other features
* **Raw content.** If you want to see what the actual XML looks like, use this function:
  ```php
  $raw_content = $splitter->get_raw_content();
  ```
  This function will return the XML feed as retrieved from the source.
* **Timeout.** You can set timeout and number of trials on the object to limit the time and trials of fetching the feed.
  ```php
  $timeout = 3; // in seconds
  $trials = 3;
  $splitter = new AtomSplitter($url, $timeout, $trials);
  ```
  The default is 0 for the timeout (indefinite) and 1 for trials.
 
