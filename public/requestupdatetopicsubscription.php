<?php 
  require_once __DIR__ . '/../lib/bootstrap.php';
  
  // which topic is being (un-)subscribed?
  $topicID = seekPOST("t");
  if (is_null($topicID))
  {
    header("Location: " . getenv("APP_URL") . "/forum.php");
    exit;
  }

  // rebuild the url of the topic so that we can go back at the end
  $topic_url = "/viewtopic.php?t=$topicID";
  $c = seekPOST("c");
  if (!is_null($c) && !empty($c))
    $topic_url .= "&c=$c";
  else
  {
    $o = seekPOST("o");
    if (!is_null($o) && !empty($o))
      $topic_url .= "&o=$o";
  }

  // is this request from a registered user?
  if (!validateFromCookie($user, $unused, $permissions, \RA\Permissions::Registered))
  {
    header("Location: " . getenv("APP_URL") . $topic_url ."&e=badcredentials");
    exit;
  }
  $userID = getUserIDFromUser($user);
  if ($userID == 0)
  {
    header("Location: " . getenv("APP_URL") . $topic_url ."&e=badcredentials");
    exit;
  }

  // are we subscribing or unsubscribing?
  $operation = seekPost("operation");
  if ($operation !== "subscribe" && $operation !== "unsubscribe")
  {
    header("Location: " . getenv("APP_URL") . $topic_url ."&e=invalidparams");
    exit;
  }

  // update the database
  $subscriptionStatus = ($operation === "subscribe");
  if (!updateForumTopicSubscription($topicID, $userID, $subscriptionStatus))
  {
    header("Location " . getenv("APP_URL") . $topic_url ."&e=subscription_update_fail");
    exit;
  }

  // everything's ok, go back to the topic
  header("Location: " . getenv("APP_URL") . $topic_url);
?>
