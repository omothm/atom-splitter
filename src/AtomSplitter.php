<?php
/**
 * AtomSplitter - Atom feed fetcher and parser.
 * 
 * @author Omar Othman (omothm) <omar@omothm.com>
 */

namespace com\omothm;

/**
 * AtomSplitter fetches an Atom feed from a given URL and parses it into an
 * associative array. This parser ignores any data other than feed entries.
 * 
 * Each element of the entries array contains the following elements:
 * <ul>
 *   <li>$entry["title"]   : the title of the entry
 *   <li>$entry["link"]    : the link to the entry
 *   <li>$entry["id"]      : the ID of the entry
 *   <li>$entry["updated"] : the date of the last update of the entry
 *   <li>$entry["content"] : the content of the entry
 *   <li>$entry["authors]  : an array of the authors of the entry
 * </ul>
 * 
 * Each element of the authors array contains the following:
 * <ul>
 *   <li>$author["name"]  : the name of the author
 *   <li>$author["uri"]   : the homepage of the author
 *   <li>$author["email"] : the email of the author
 * </ul>
 * 
 * For example, if we want to get the title of the first entry, we would write
 * $entries = $splitter->get_entries();
 * echo $entries[0]["title"];
 * 
 * If we want to get the email of the third author of the ninth entry, we would
 * write
 * echo $entries[8]["authors][2]["email"];
 * 
 * @author Omar Othman (omothm) <omar@omothm.com>
 */
class AtomSplitter
{
  const ENCODING = "UTF-8";
  const TAG_ENTRY = "entry";
  const TAG_TITLE = "title";
  const TAG_CONTENT = "content";
  const TAG_ID = "id";
  const TAG_UPDATED = "updated";
  const TAG_AUTHOR = "author";
  const TAG_AUTHORS = "authors";
  const TAG_LINK = "link";
  const TAG_AUTHOR_NAME = "name";
  const TAG_AUTHOR_URI = "uri";
  const TAG_AUTHOR_EMAIL = "email";

  private $url;
  private $timeout;
  private $trials;
  private $error;
  private $raw_content;
  private $entries;

  // XML variables
  private $xml_entry_counter;
  private $xml_inside_entry;
  private $xml_inside_author;
  private $xml_current_element;
  private $xml_level;

  /**
   * Initializes a parser object.
   *
   * @param string $url The URL of the Atom feed
   * @param integer $timeout Timeout of HTTP fetch in seconds
   * @param integer $trials Number of trials to fetch HTTP
   */
  public function __construct(string $url, int $timeout = 0, int $trials = 1)
  {
    $this->url = $url;
    $this->timeout = $timeout;
    $this->trials = $trials;
  }

  /**
   * Runs the fetcher and parser. After running this function, the result can be
   * retrieved by calling get_entries(), unless an error occurred (which can be
   * retrieved by get_error()).
   *
   * @return boolean TRUE if successful
   */
  public function run()
  {
    $this->error = NULL;
    $this->raw_content = "";
    $this->entries = array();
    $this->xml_entry_counter = -1;
    $this->xml_level = 0;

    // Fetch content
    $ret = $this->fetch_content($this->url, $this->timeout, $this->trials);
    if ($ret === FALSE) {
      return FALSE;
    }

    // Parse XML
    $parser = xml_parser_create(self::ENCODING);
    xml_set_object($parser, $this);
    xml_set_element_handler($parser, "xml_start", "xml_end");
    xml_set_character_data_handler($parser, "xml_data");
    $ret = xml_parse($parser, $this->raw_content);
    if ($ret === 0) {
      $errorCode = xml_get_error_code($parser);
      if ($errorCode == FALSE) {
        $this->error = "XML parser invalidated";
        return FALSE;
      } else if ($errorCode !== XML_ERROR_NONE) {
        $this->error = "XML parsing error [$errorCode] - "
          . xml_error_string($errorCode) . "at "
          . xml_get_current_line_number($parser) . ":"
          . xml_get_current_column_number($parser);
      }
    }
    xml_parser_free($parser);
    return is_null($this->error);
  }

  /**
   * Returns entries array.
   *
   * @return array The array of feed entries
   */
  public function get_entries()
  {
    return $this->entries;
  }

  /**
   * Returns the original Atom feed XML file.
   *
   * @return string The Atom XML as retrieved from the URL
   */
  public function get_raw_content()
  {
    return $this->raw_content;
  }

  /**
   * Returns an error string if an error occurred while fetching/parsing.
   *
   * @return string The error string if any
   * @return NULL if no error occurred
   */
  public function get_error()
  {
    return $this->error;
  }

  private function fetch_content(string $url, int $timeout = 0, int $trials = 1)
  {
    // Check variable ranges
    if ($timeout < 0) {
      $this->error = "fetch_content: Illegal timeout value ($timeout)";
      return FALSE;
    }
    if ($trials < 1) {
      $this->error = "Fatal - Illegal number of trials ($trials)";
      return FALSE;
    }

    // Initialize cURL
    $ch = curl_init();
    if ($ch === FALSE) {
      $this->error = "curl_init error - Could not initialize a cURL handle";
      return FALSE;
    }

    // Set URL
    $ret = curl_setopt($ch, CURLOPT_URL, $url);
    if ($ret === FALSE) {
      $this->error = "CURLOPT_URL error - " . curl_error($ch);
      curl_close($ch);
      return FALSE;
    }

    // Return the string instead of print it out
    $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if ($ret === FALSE) {
      $this->error = "CURLOPT_RETURNTRANSFER error - " . curl_error($ch);
      curl_close($ch);
      return FALSE;
    }

    // Ignore the header in the return value
    $ret = curl_setopt($ch, CURLOPT_HEADER, FALSE);
    if ($ret === FALSE) {
      $this->error = "CURLOPT_HEADER error - " . curl_error($ch);
      curl_close($ch);
      return FALSE;
    }

    // Set timeout
    if ($timeout > 0) {
      $ret = curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      if ($ret === FALSE) {
        $this->error = "CURLOPT_TIMEOUT error - " . curl_error($ch);
        curl_close($ch);
        return FALSE;
      }
    }

    // Start trials
    for ($i = 0; $i < $trials; $i++) {
      $result = curl_exec($ch);
      if ($result !== FALSE) {
        $this->raw_content = $result;
        return TRUE;
      }
    }

    if ($result === FALSE) {
      $this->error = "curl_exec error - " . curl_error($ch);
    }
    curl_close($ch);
  }

  private function xml_start($parser, string $name, array $attribs)
  {
    $this->xml_level++;

    $this->dbg = FALSE;

    if ($this->xml_level == 2 && !strcasecmp($name, self::TAG_ENTRY)) {
      $this->xml_inside_entry = TRUE;
      $this->xml_entry_counter++;
      array_push($this->entries, array());
      $this->entries[$this->xml_entry_counter][self::TAG_AUTHORS] = array();
    } else if ($this->xml_level == 3 && $this->xml_inside_entry) {
      if (!strcasecmp($name, self::TAG_TITLE)) {
        $this->entries[$this->xml_entry_counter][self::TAG_TITLE] = "";
        $this->xml_current_element = &$this->entries[$this->xml_entry_counter][self::TAG_TITLE];
      } else if (!strcasecmp($name, self::TAG_ID)) {
        $this->entries[$this->xml_entry_counter][self::TAG_ID] = "";
        $this->xml_current_element = &$this->entries[$this->xml_entry_counter][self::TAG_ID];
      } else if (!strcasecmp($name, self::TAG_UPDATED)) {
        $this->entries[$this->xml_entry_counter][self::TAG_UPDATED] = "";
        $this->xml_current_element = &$this->entries[$this->xml_entry_counter][self::TAG_UPDATED];
      } else if (!strcasecmp($name, self::TAG_CONTENT)) {
        $this->entries[$this->xml_entry_counter][self::TAG_CONTENT] = "";
        $this->xml_current_element = &$this->entries[$this->xml_entry_counter][self::TAG_CONTENT];
      } else if (!strcasecmp($name, self::TAG_LINK)) {
        foreach ($attribs as $key => $value) {
          if (!strcasecmp($key, "href")) {
            $this->entries[$this->xml_entry_counter][self::TAG_LINK] = $value;
            break;
          }
        }
        unset($this->xml_current_element);
      } else if (!strcasecmp($name, self::TAG_AUTHOR)) {
        $this->xml_inside_author = TRUE;
        array_push($this->entries[$this->xml_entry_counter][self::TAG_AUTHORS], array());
      } else {
        unset($this->xml_current_element);
      }
    } else if ($this->xml_level == 4 && $this->xml_inside_author) {
      $index = sizeof($this->entries[$this->xml_entry_counter][self::TAG_AUTHORS]) - 1;
      if (!strcasecmp($name, self::TAG_AUTHOR_NAME)) {
        $this->entries[$this->xml_entry_counter][self::TAG_AUTHORS][$index][self::TAG_AUTHOR_NAME] = "";
        $this->xml_current_element = &$this->entries[$this->xml_entry_counter][self::TAG_AUTHORS][$index][self::TAG_AUTHOR_NAME];
      } else if (!strcasecmp($name, self::TAG_AUTHOR_URI)) {
        $this->entries[$this->xml_entry_counter][self::TAG_AUTHORS][$index][self::TAG_AUTHOR_URI] = "";
        $this->xml_current_element = &$this->entries[$this->xml_entry_counter][self::TAG_AUTHORS][$index][self::TAG_AUTHOR_URI];
      } else if (!strcasecmp($name, self::TAG_AUTHOR_EMAIL)) {
        $this->entries[$this->xml_entry_counter][self::TAG_AUTHORS][$index][self::TAG_AUTHOR_EMAIL] = "";
        $this->xml_current_element = &$this->entries[$this->xml_entry_counter][self::TAG_AUTHORS][$index][self::TAG_AUTHOR_EMAIL];
      }
    } else if ($this->xml_level < 4) {
      unset($this->xml_current_element);
    }
  }

  private function xml_end($parser, string $name)
  {
    if (!strcasecmp($name, self::TAG_ENTRY)) {
      $this->xml_inside_entry = FALSE;
    } else if (!strcasecmp($name, self::TAG_AUTHOR)) {
      $this->xml_inside_author = FALSE;
    }

    $this->xml_level--;
  }

  private function xml_data($parser, string $data)
  {
    if (isset($this->xml_current_element)) {
      $this->xml_current_element .= $data;
    }
  }
}
