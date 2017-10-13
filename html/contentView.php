<?php
include 'pageHeader.php';
  $pdo = getDBPDO();

// Action determined from GET directly, else via POST. Will be:
//  Update or Insert (Same function): Data is set, so update DB.
//  Edit:   Load an existing contentTitle for editing.
//  Delete: Delete from DB
if ((isset($_POST["update"])) && ($_POST["update"] != '')) {
  $action = 'update';
} else if ((isset($_POST["insert"])) && ($_POST["insert"] != '')) {
  $action = 'insert';
} else if ((isset($_GET["action"])) && ($_GET["action"] != '')) {
  $action = $_GET["action"];
} else {
  $action = '';
}

// pageContentID for Edit/Delete comes from $_GET. For Update and insert comes from $_POST.
//  Move all to _POST for consistency.
if ((isset($_GET["pageContentID"])) && ($_GET["pageContentID"] > 0)) {
  $_POST["pageContentID"] = $_GET["pageContentID"];
  unset($_GET["pageContentID"]);
}

// UserID needed for validating permission to edit.
if ((isset($_SESSION["userID"])) && ($_SESSION["userID"] > 0)) {
  $userID = $_SESSION["userID"];
} else {
  $userID = 0;
}

if ((isset($_POST["contentTitle"])) && ($_POST["contentTitle"] != '')) {
  $contentTitle = trim($_POST["contentTitle"]);
} else {
  $contentTitle = '';
}

if ((isset($_POST["contentDescription"])) && ($_POST["contentDescription"] != '')) {
  $contentDescription = trim($_POST["contentDescription"]);
} else {
  $contentDescription = '';
}

if ((isset($_POST["contentText"])) && ($_POST["contentText"] != '')) {
  $contentText = trim($_POST["contentText"]);
} else {
  $contentText = '';
}

if ((isset($_POST["contentURL"])) && ($_POST["contentURL"] != '')) {
  $contentURL = trim($_POST["contentURL"]);
} else {
  $contentURL = '';
}
  // TO DO: Test if we have filename here... Know it is in $_FILES['userUpload']['name'] if not.
  if ((isset($_POST["contentFilename"])) && ($_POST["contentFilename"] != '')) {
    $contentFilename = trim($_POST["contentFilename"]);
  } else {
    $contentFilename = '';
  }


// Set variables for input form and continue to display.
$sql = '';
if ($action == 'delete') {
  $sql = 'SELECT contentDelete(?, ?)';
  $sqlParamsArray = [$_POST["pageContentID"], $userID];
  $result = getOnePDORow($pdo, $sql, $sqlParamsArray);
  header('Location: ' . '/content.php');
  exit();
} else if ($action == 'insert') {
  $sql = 'SELECT contentInsert(?, ?, ?, ?, ?, ?)';
  $sqlParamsArray = [$contentTitle, $contentDescription, $contentText, $contentURL, $contentFilename, $userID];
  $newID = getOnePDOValue($pdo, $sql, $sqlParamsArray);
  // TO DO: This needs to be a file upload function, taking the name of the tile and having access to the $_FILES array.
  // Same function for insert and update, with different input.
  if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] == UPLOAD_ERR_NO_FILE) {
    // $uploadfile = $GLOBALS['CONTENT_STORE_DIRECTORY'] . basename($_FILES['userUpload']['name']);
    // $path = $_FILES['image']['name'];
    // $ext = pathinfo($path, PATHINFO_EXTENSION);
    $uploadfile = $GLOBALS['CONTENT_STORE_DIRECTORY'] . $newID . '.' . pathinfo($_FILES['userUpload']['name'], PATHINFO_EXTENSION);
    // echo '<pre>';
    if (move_uploaded_file($_FILES['userUpload']['tmp_name'], $uploadfile)) {
      ;  // Success. Do nothing here.
    } else {
      echo '<pre><br />File upload error. File array dump follows. <br />';
      outputArray($_FILES, true);
      echo "<script>alert('Upload error. Press OK to return to page.')</script>";
    }
  }
  $_SESSION['lastURL'] = 'contentView.php?action=edit&pageContentID=' . $newID;
  header('Location: ' . $_SESSION['lastURL']);
  exit();
} else if ($action == 'update') {
  $sql = 'SELECT contentUpdate(?, ?, ?, ?, ?, ?, ?)';
  $sqlParamsArray = [$_POST["pageContentID"], $contentTitle, $contentDescription, $contentText, $contentURL, $contentFilename, $userID];
  $result = getOnePDOValue($pdo, $sql, $sqlParamsArray);
  if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] == UPLOAD_ERR_NO_FILE) {
    // $uploadfile = $GLOBALS['CONTENT_STORE_DIRECTORY'] . basename($_FILES['userUpload']['name']);
    // $path = $_FILES['image']['name'];
    // $ext = pathinfo($path, PATHINFO_EXTENSION);
    $uploadfile = $GLOBALS['CONTENT_STORE_DIRECTORY'] . $_POST["pageContentID"] . '.' . pathinfo($_FILES['userUpload']['name'], PATHINFO_EXTENSION);
    // echo '<pre>';
    if (move_uploaded_file($_FILES['userUpload']['tmp_name'], $uploadfile)) {
      ;  // Success. Do nothing here.
    } else {
      echo '<pre><br />File upload error. File array dump follows. <br />';
      outputArray($_FILES, true);
      echo "<script>alert('Upload error. Press OK to return to page.')</script>";
    }
  }
  header('Location: ' . 'contentView.php?action=edit&pageContentID=' . $_POST["pageContentID"]);
  exit();
}

// action=edit&pageContentID=100257

// If we are still here, we are displaying the content edit screen... So, if editing,
//  load the content to edit. Otherwise just continue with defaults.
if ($action == 'edit') {
  $sql = 'CALL procViewContent(?, ?)';
  if (isset($_SESSION['userID']) && ($_SESSION['userID'] > 0)) {
    $sqlParamsArray = [$_POST["pageContentID"], $_SESSION['userID']];
  } else {
    $sqlParamsArray = [$_POST["pageContentID"], 0];
  }
  $row = getOnePDORow($pdo, $sql, $sqlParamsArray);
  outputArray($row);
  if (!empty($row)) {
    $contentTitle = trim($row['contentTitle']);
    $contentDescription = trim($row['contentDescription']);
    $contentText = trim($row['contentText']);
    $contentURL = trim($row['contentURL']);
    $contentFilename = trim($row['contentFilename']);
    $canEdit = $row['canEdit'];
  } else {
    $contentTitle = 'Title';
    $contentDescription = 'Description.';
    $contentText = 'Text';
    $contentURL = 'URL';
    $contentFilename = 'Select Filename with Browse Button.';
    // $canEdit = true;
  }
}


  abstract class ViewMode {
    const View = 0;
    const Create = 1;
    const Update = 2;
  }


  $ViewMode = ViewMode::View;
  if (!isset($_POST["pageContentID"]) || $_POST["pageContentID"] == '' || $_POST["pageContentID"] == 0) {
    $ViewMode = ViewMode::Create;
  } else {
    if (isset($canEdit) && ($canEdit)) {
      $ViewMode = ViewMode::Update;
    }
}

htmlStart('Content View');
?>
<div class="container">
  <?php include 'divButtonGroupMain.php'; ?>
    <br/>
  <?php include 'divV4LBanner.php'; ?>
    <br/>
  <?php
    debugSectionOut("Edit Content");
    debugOut('$action', $action);
    debugOut('$userID', $userID);
    debugOut('$contentTitle', $contentTitle);
    debugOut('$contentDescription', $contentDescription);
    debugOut('$contentText', $contentText);
    debugOut('$contentURL', $contentURL);
    debugOut('$contentFilename', $contentFilename);
    debugOut('$sql', $sql);
  ?>

    <form enctype="multipart/form-data" action="contentView.php" method="post" name="contentViewForm">
        <table id="contentViewTable">

            <tr>
                <td>ID:</td>
                <td>
                  <?php
                    if ($ViewMode == ViewMode::Create) {
                      // echo '<input type="text" name="contentID" value="Auto-generated" rows="1" cols="80" readonly/>';
                      echo '<textarea name="pageContentID" rows="1" cols="80" required placeholder="ID" id="pageContentID" readonly>Auto-generated</textarea>';
                    } else {
                      // echo '<input type="text" name="contentID" value="' . $_POST["pageContentID"] . '" readonly/>';
                      echo '<textarea name="pageContentID" rows="1" cols="80" required placeholder="ID" id="pageContentID" readonly>' . $_POST["pageContentID"] . '</textarea>';
                    }
                  ?>
                </td>
            </tr>
            <tr>
                <td>Title:</td>
                <td><textarea name="contentTitle" rows="1" cols="80"
                  <?php
                    if ($contentTitle == '') {
                      echo 'required placeholder="Title" id="inputContentTitle"></textarea>';
                    } else {
                      echo ' id="inputContentTitle">' . $contentTitle . '</textarea>';
                    }
                  ?>
                </td>
            </tr>
            <tr>
                <td>Description:&nbsp;</td>
                <td><textarea name="contentDescription" rows="3" cols="80"
                  <?php if ($contentDescription == '') {
                    echo 'required placeholder="Description" id="inputContentDescription"></textarea>';
                  } else {
                    echo ' id="inputContentDescription">' . $contentDescription . '</textarea>';
                  } ?>
                </td>
            </tr>
            <tr>
                <td>Text:</td>
                <td><textarea name="contentText" rows="20" cols="80"
                  <?php if ($contentText == '') {
                    echo 'required placeholder="Content Text" id="inputContentText"></textarea>';
                  } else {
                    echo ' id="inputContentText">' . $contentText . '</textarea>';
                  } ?>
                </td>
            </tr>
            <tr>
                <td>URL:</td>
                <td><textarea name="contentURL" rows="1" cols="80"
                  <?php if ($contentURL == '') {
                    echo 'required placeholder="Fully Qualified URL (i.e. http://www.example.com)"></textarea>';
                  } else {
                    echo ' id="inputContentURL">' . $contentURL . '</textarea>';
                  } ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                  <?php if ($ViewMode == ViewMode::Create) {
                    echo 'File to upload:';
                  } elseif ($ViewMode == ViewMode::Update) {
                    echo 'New (Replacement) File:';
                  } else {
                    echo 'Filename: ';
                  }
                  ?>
                </td>
                <td>
                  <?php
                    if ($ViewMode == ViewMode::Create || $ViewMode == ViewMode::Update) {
                      // <!-- MAX_FILE_SIZE must precede the file input field -->
                      echo '<input type="hidden" name="MAX_FILE_SIZE" value="16777216"/>';
                      // <!-- Name of input element determines name in $_FILES array -->
                      echo '<input name="userUpload" type="file"/>';
                    } else {
                      echo '< textarea name = "contentFilename" rows = "1" cols = "80"';
                      if ($contentFilename == '') {
                        echo ' required placeholder="Content Filename" id="inputContentText"></textarea>';
                      } else {
                        echo ' id="inputContentText">' . $contentFilename . '</textarea>';
                      }
                    }
                  ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><?php
                    if ($ViewMode == ViewMode::Create) {
                      echo '<input type="submit" class="btn btn-primary" name="insert" value=" Add Content " id="inputid1" /> ';
                    } else {
                      if ($ViewMode == ViewMode::Update) {
                        echo '<input type="submit" class="btn btn-primary" name="update" value="Save Changes" id="inputid1" /> ';
                        echo '<input type="button" class="btn btn-default" name="cancel" value="   Cancel   " onClick="window.location=\'./content.php\';" />';
                      } else {
                        echo '<input type="button" class="btn btn-default" name="back" value="    Back    " onClick="window.location=\'./content.php\';" />';
                      }
                    }
                  ?>
                </td>
            </tr>
        </table>
    </form>
    <br/>

    <!-- Here we should conditionally (if editing) add or remove tags. -->
  <?php
    if (isset($_POST["pageContentID"]) && ($_POST["pageContentID"] > 0)) {
      include 'divContentTagsEdit.php';
      include 'divContentTags.php';
    }
  ?>

</div>
</body>
</html>
































