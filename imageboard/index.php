<?php
if (str_contains($_SERVER['REQUEST_URI'], "imageboard")) {
    header("Location: /");
    exit();
}
// Function to calculate a combined hash of all files in a directory
function generateDirectoryHash($directory) {
    $hashes = [];
    
    // Recursively collect all files in the directory
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    // Iterate over each file
    foreach ($files as $file) {
        if ($file->isFile()) {
            // Hash the file contents and append to the array
            $fileHash = hash_file('sha256', $file->getRealPath());
            $hashes[] = $fileHash;
        }
    }

    // Sort the hashes to ensure the order doesn't matter
    sort($hashes);

    // Combine the individual file hashes into one single hash
    return hash('sha256', implode('', $hashes));
}

function copyFiles($sourceDir, $targetDir) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->isFile()) {
            // Calculate the relative path correctly
            $relativePath = str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $file->getRealPath());

            $targetFilePath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;

            // Create directories if they don't exist
            if (!file_exists(dirname($targetFilePath))) {
                if (!mkdir(dirname($targetFilePath), 0755, true)) {
                    echo "Failed to create directory: " . dirname($targetFilePath) . "\n";
                    continue; // Skip copying this file
                }
            }

            // Attempt to copy the file
            copy($file->getRealPath(), $targetFilePath);
        }
    }
}

// Paths to your directories
$dir1 = __DIR__; // Current directory
$dir2 = $_SERVER['DOCUMENT_ROOT'] . '/imageboard'; // Directory to compare with

// Generate hashes for both directories
$hash1 = generateDirectoryHash($dir1);
$hash2 = generateDirectoryHash($dir2);

// Compare the hashes
if ($hash1 !== $hash2) {
    copyFiles($dir2, $dir1);
}


require $_SERVER['DOCUMENT_ROOT'] . "/inc/crypto.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/inc/db-utils.php';
session_start();

requestsPerMinute();

if (!isset($_SESSION["powSolution"])) {
    header("Location: /pow.php");
    exit();
}
if (validatePoW($_SESSION["powSolution"],intval(15+(requestsPerMinute()/30))) == false) {
    header("Location: /pow.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
  <head>
    <link rel="stylesheet" href="/style.css">
    <link rel="icon" type="image/x-icon" href="/image/favicon.gif">
  </head>
  <body>
    <div id="wrapper">
      <div id="right">
	<div class="boardheader">
	  <h1>/<?php echo getBoard(); ?>/</h1>
	</div>
	<hr>
	<div class="center">
	  <form id="post" action="post.php" method="post" enctype="multipart/form-data">
	    <fieldset>
	      <table id="postform">
		<tbody>
		  <tr>
		    <td class="label">
		      <label for="name">Name (leave empty for anonymous)</label>
		    </td>
		    <td>
		      <input name="name" id="name" maxlength="60" type="text">
		    </td>
		  </tr>
		  <tr>
		    <td class="label">
		      <label for="subject">Subject</label>
		    </td>
		    <td>
		      <input name="subject" id="subject" maxlength="60" type="text" autocomplete="off">
		      <input value="Send" name="submit" id="submit" type="submit">
		    </td>
		  </tr>
		  <tr>
		    <td class="label">
		      <label for="msg">Message</label>
		    </td>
		    <td>
		      <textarea name="msg" id="msg" rows="4" cols="48" autocomplete="off"></textarea>
		    </td>
		  </tr>
		  <tr>
		    <td class="label">
		      <label for="captcha">CAPTCHA</label>
		      <div class=image-container>
			<img src="/captcha.php" width="192" height="48" alt="CAPTCHA" class="image1">
			<img src="/captcha-2.php" id="offset-image"  id="offset-image" class="image2">
			<br><br><br>
			<input type="range" id="offset-slider" min="0" max="1600" value="0">
			<button type="button" onclick="confirm('Solve this CAPTCHA by moving the slider until you see 2 words, then type the solution. The CAPTCHA is only composed of lowercase characters');">Help</button>
			<script src="/script.js"></script>
		      </div>
		    </td>
		    <td>
		      <input name="captcha" id="captcha" type="text" autocomplete="off">
		    </td>
		  </tr>
		  <tr>
		    <td class="label">
		      <label for="file">File</label>
		    </td>
		    <td>
		      <input name="file" id="file" type="file" size="35">
		    </td>
		  </tr>
		  <tr>
		    <td class="label">
		      <label for="postpassword">
			<abbr title="Is used to delete posts and files.">Password</abbr>
		      </label>
		    </td>
		    <td>
		      <input name="postpassword" id="postpassword" value="" type="text" autocomplete="off">
		    </td>
		  </tr>
		  <tr>
		    <td colspan="2">
		      <ul id="postinfo">
			<li>Allowed filetypes are gif, jpeg, jpg, png</li>
			<li>The biggest file size is 2 MB.</li>
		      </ul>
		    </td>
		  </tr>
		</tbody>
	      </table>
	    </fieldset>
	  </form>
	</div>
	<hr>
	<?php
	require_once '/srv/http/setup.php';

	// Create a connection

	$database = getBoard();
	$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, $database);
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	// Fetch posts
	$sql = "SELECT * FROM posts";
	$result = $conn->query($sql);

	$posts = [];
	if ($result instanceof mysqli_result) {
	    while ($row = $result->fetch_assoc()) {
		// Process each post and replies
		$post = [
		    'post_id' => $row['post_id'],
		    'post_title' => htmlspecialchars($row['post_title'], ENT_QUOTES, 'UTF-8'),
		    'poster_name' => htmlspecialchars($row['poster_name'], ENT_QUOTES, 'UTF-8'),
		    'post_content' => htmlspecialchars($row['post_content'], ENT_QUOTES, 'UTF-8'),
		    'has_image' => $row['has_image'] == 1,
		    'post_timestamp' => $row['post_timestamp'],
		    'thumb_image_path' => $row['has_image'] ? str_replace('images/', 'images/THUMB_', htmlspecialchars($row['post_image_path'], ENT_QUOTES, 'UTF-8')) : null,
		    'replies' => []
		];

		// Fetch replies for each post
		$stmt_replies = $conn->prepare("SELECT * FROM replies WHERE post_id_fk = ?");
		$stmt_replies->bind_param("i", $row['post_id']);
		$stmt_replies->execute();
		$result_replies = $stmt_replies->get_result();

		if ($result_replies instanceof mysqli_result) {
		    while ($reply_row = $result_replies->fetch_assoc()) {
			$reply = [
			    'reply_id' => htmlspecialchars($reply_row['reply_id'], ENT_QUOTES, 'UTF-8'),
			    'replier_name' => htmlspecialchars($reply_row['replier_name'], ENT_QUOTES, 'UTF-8'),
			    'reply_content' => htmlspecialchars($reply_row['reply_content'], ENT_QUOTES, 'UTF-8'),
			    'has_image' => $reply_row['has_image'] == 1,
			    'thumb_image_path' => $reply_row['has_image'] ? str_replace('images/', 'images/THUMB_', htmlspecialchars($reply_row['reply_image_path'], ENT_QUOTES, 'UTF-8')) : null,
			    'reply_timestamp' => $reply_row['reply_timestamp']
			];

			$post['replies'][] = $reply;
		    }
		}

		$posts[] = $post;
	    }
	}

	// Render the posts using Twig
	echo $twig->render('posts.html.twig', ['posts' => $posts]);

	$conn->close();
	?>
  </body>
</html>
