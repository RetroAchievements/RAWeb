<?php

use App\Site\Enums\Permissions;

$requestedCategoryID = requestInputSanitized('c', null, 'integer');

authenticateFromCookie($user, $permissions, $userDetails);

$forumList = getForumList($requestedCategoryID);

$numUnofficialLinks = 0;
if ($permissions >= Permissions::Moderator) {
    $unofficialLinks = getUnauthorisedForumLinks();
    $numUnofficialLinks = is_countable($unofficialLinks) ? count($unofficialLinks) : 0;
}

$pageTitle = "Forum Index";
$requestedCategory = "";
if ($requestedCategoryID !== 0 && !empty($forumList)) {
    $requestedCategory = $forumList[0]['CategoryName'];    // Fetch any elements data
    $pageTitle .= ": " . $requestedCategory;
}

sanitize_outputs($requestedCategory);

RenderContentStart($pageTitle);
?>
<article>
    <?php
    echo "<div class='navpath'>";
    if ($requestedCategory == "") {
        echo "<b>Forum Index</b>";
    } else {
        echo "<a href='/forum.php'>Forum Index</a>";
        echo " &raquo; <b>$requestedCategory</b>";
    }
    echo "</div>";

    // Output all forums fetched, by category

    if ($numUnofficialLinks > 0) {
        echo "<div class='my-3 bg-embedded p-2 rounded-sm'>";
        echo "<b>Administrator Notice:</b> <a href='/viewforum.php?f=0'>$numUnofficialLinks unofficial posts need authorising: please verify them!</a>";
        echo "</div>";
    }

    $lastCategory = "_init";

    $forumIter = 0;

    echo "<div class='table-wrapper'>";
    foreach ($forumList as $forumData) {
        $nextCategory = $forumData['CategoryName'];
        $nextCategoryID = $forumData['CategoryID'];

        sanitize_outputs($nextCategory);

        if ($nextCategory != $lastCategory) {
            if ($lastCategory !== "_init") {
                // We are starting another table, but we need to close the last one!
                echo "</tbody>";
                echo "</table>";
                echo "<br>";
                echo "<br>";
                $forumIter = 0;
            }

            sanitize_outputs($forumData['CategoryDescription']);

            echo "<h2>Forum: $nextCategory</h2>";
            echo "<p class='mb-5'>" . $forumData['CategoryDescription'] . "</p>";

            echo "<table class='table-highlight'>";
            echo "<tbody>";
            echo "<tr class='do-not-highlight'>";
            echo "<th></th>";
            echo "<th class='w-full xl:w-[60%]'>Forum</th>";
            echo "<th>Topics</th>";
            echo "<th>Posts</th>";
            echo "<th class='xl:text-right'>Last Post</th>";
            echo "</tr>";

            $lastCategory = $nextCategory;
        }

        // Output one forum, then loop
        $nextForumID = $forumData['ID'];
        $nextForumTitle = $forumData['Title'];
        $nextForumDesc = $forumData['Description'];
        $nextForumNumTopics = $forumData['NumTopics'];
        $nextForumNumPosts = $forumData['NumPosts'];
        $nextForumLastPostCreated = $forumData['LastPostCreated'];
        if ($nextForumLastPostCreated !== null) {
            $nextForumCreatedNiceDate = date("d M, Y H:i", strtotime($nextForumLastPostCreated));
        } else {
            $nextForumCreatedNiceDate = "None";
        }
        $nextForumLastPostAuthor = $forumData['LastPostAuthor'];
        $nextForumLastPostTopicName = $forumData['LastPostTopicName'];
        $nextForumLastPostTopicID = $forumData['LastPostTopicID'];
        $nextForumLastPostID = $forumData['LastPostID'];

        sanitize_outputs(
            $nextForumTitle,
            $nextForumDesc,
            $nextForumLastPostAuthor,
            $nextForumLastPostTopicName,
        );

        echo "<tr>";

        echo "<td class='p-1'><img alt='$nextForumTitle' src='" . asset('assets/images/icon/forum-topic-unread.gif') . "' width='32' height='32'></td>";
        echo "<td><a href='/viewforum.php?f=$nextForumID'>$nextForumTitle</a><br>";
        echo "$nextForumDesc</td>";
        echo "<td>" . localized_number($nextForumNumTopics) . "</td>";
        echo "<td>" . localized_number($nextForumNumPosts) . "</td>";
        echo "<td>";
        echo "<div class='xl:flex xl:flex-col xl:items-end xl:gap-y-0.5'>";
        if (isset($nextForumLastPostAuthor) && mb_strlen($nextForumLastPostAuthor) > 1) {
            echo userAvatar($nextForumLastPostAuthor, icon: false);
        }
        echo "<span class='smalldate'>$nextForumCreatedNiceDate</span>";
        echo "<a class='text-2xs' href='/viewtopic.php?t=$nextForumLastPostTopicID&c=$nextForumLastPostID#$nextForumLastPostID'>View</a>";
        echo "</div>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";
    ?>
</article>
<?php view()->share('sidebar', true) ?>
<aside>
    <?php
    RenderRecentForumPostsComponent(8);
    ?>
</aside>
<?php RenderContentEnd(); ?>
