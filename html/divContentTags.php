<?php

include_once 'sessionStart.php';
if (!isset($pdo)) {
  $pdo = getDBPDO();
}
// procGetContentTags(theContentID BIGINT);
// Test w/ 100238... Should at least get createBy.
  $sql = 'CALL procGetContentTags(?, ?)';

if ((isset($_SESSION['userID'])) && ($_SESSION['userID'] > 0)) {
  $sqlParamArray = [$_POST['pageContentID'], $_SESSION['userID']];
} else {
  $sqlParamArray = [$_POST['pageContentID'], 0];
}

$currentTagCategory = '';
  $result = getOnePDOTable($pdo, $sql, $sqlParamArray);

echo '<div id="contentTags">';
foreach($result as $key => $value) {
  if ($currentTagCategory != $value['tagCategory']) {
    $currentTagCategory = $value['tagCategory'];
    echo '<br /><strong>' . $value['tagCategory'] . ':</strong> ';
    if ($value['canEdit']) {
      // First one has different punctuation, so keep inside the conditional
      echo '<a href = "" class="btn btn-default btn-xs" onclick="return confirm(\'Remove this tag?\');">' . $value['tag'] . '&nbsp;&#9745;</a>';
    } else {
      echo $value['tag'];
    }
  } else {
    if ($value['canEdit']) {
      // First one has different punctuation, so keep inside the conditional
      echo '&nbsp;<a href = "" class="btn btn-default btn-xs" onclick="return confirm(\'Remove this tag?\');">' . $value['tag'] . '&nbsp;&#9745;</a>';
    } else {
      echo ', ' . $value['tag'];
    }
  }
}

echo '</div>';

?>