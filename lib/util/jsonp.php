// This is a helper function that enables JSONP API responses (JSON with a callback)
// and falls back to static JSON if a callback parameter is not specified. This allows
// the API to be used cross-domain without having to deal with server-side cross-origin
// configuration settings applied.

function jsonp_encode($value, $options = 0, $depth = 512) {
  $callback = $_GET['callback'];
  $json = json_encode($value, $options, $depth);
  return (isset($callback) && !empty($callback)) ? "${callback}(${json})" : $json;
}
