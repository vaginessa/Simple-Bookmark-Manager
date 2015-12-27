<?php

///
/// Load bookmark storage
///

libxml_use_internal_errors(true);
$storage = array("id" => 1, "links" => array()); // Start at 1 to force a hashmap

if (file_exists("./data/bookmarks.dat")) {
  $storage = unserialize(file_get_contents("./data/bookmarks.dat"));
}

///
/// Actions
///

$actions = array();

$actions['add'] = function() {
  global $storage;
  $id = id();

  $storage["links"][$id] = array(
    "link" => pparam('link', 'http://www.google.com/'), // @todo make sure this is always set
    "description" => pparam('description', ''),
    "tags" => pparam('tags', array()),
  );

  // Fills title and favicon
  fill_data($id);

  // Return result
  return_result("0", $id);
};

$actions['modify'] = function() {
  // @todo: implement
};

$actions['delete'] = function() {
  global $storage;
    $id = pparam('id', -1);

    if ($id === -1) {
      return_result("4", "Unkown link ID specified");
    } elseif (array_key_exists($id, $storage['links'])) {
      unset($storage['links'][$id]);
      return_result("0", "");
    }
};

$actions['list'] = function() {
  global $storage;
  $id = pparam('id', -1);

  if ($id === -1) {
    return_result("0", $storage['links']);
  } elseif (array_key_exists($id, $storage['links'])) {
    return_result("0", $storage['links'][$id]);
  } else {
    return_result("3", "Unkown link ID requested");
  }
};

///
/// Helper functions
///

function return_result($code, $message) {
  echo json_encode(array("status" => $code, "data" => $message));
}

function pparam($key, $default) {
  return array_key_exists($key, $_POST) ? $_POST[$key] : $default;
}

function id() {
  global $storage;
  return $storage["id"]++;
}

function fill_data($id) {
  global $storage;

  $doc = new DOMDocument();
  $doc->strictErrorChecking = FALSE;
  $doc->loadHTML(utf8_encode(file_get_contents($storage["links"][$id]["link"])));
  $xml = simplexml_import_dom($doc);

  // @todo: cache favicon
  $fav = $xml->xpath('//link[@rel="shortcut icon"]')[0]['href'][0]->__toString(); // favicon

  if ($fav.substr(0, 4) != "http") {
    $data = parse_url($storage["links"][$id]["link"]);

    if ($fav[0] == '/') {
      // absolute path
      $fav = $data["scheme"]."://".$data["host"].$fav;
    } else {
      // relative path
      $fav = $data["scheme"]."://".$data["host"]."?".$data["argument"].$fav;
    }
  }

  $storage["links"][$id]["favicon"] = $fav;
  $storage["links"][$id]["title"] = (string)$xml->xpath('//title')[0]; // title
}

///
/// Execute action and return result
///

if (!array_key_exists('action', $_GET)) {
  return_result("1", "No action specified");
} elseif (!array_key_exists($_GET['action'], $actions)) {
  return_result("2", "No such action");
} else {
  $actions[$_GET['action']]();
}

///
/// Save bookmark storage
///

file_put_contents("./data/bookmarks.dat", serialize($storage));

?>