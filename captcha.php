<?php
session_start();
function generateCaptchaText($length) {
    // Path to the file
    $file = '/srv/http/words.txt';

    // Initialize an empty array to hold valid lines
    $validLines = [];

    // Read the file line by line
    if ($handle = fopen($file, 'r')) {
        while (($line = fgets($handle)) !== false) {
            // Trim the line to remove extra whitespace and check its length
            $line = trim($line);
            if (strlen($line) == $length) {
                $validLines[] = $line;
            }
        }

        // Close the file handle
        fclose($handle);
    } else {
        die('Error opening the file');
    }

    // Check if there are any valid lines
    if (!empty($validLines)) {
        // Pick a random line from the valid lines
        $randomLine = $validLines[array_rand($validLines)];
    }

    return $randomLine;
}
function distort($image, $width, $height, $bg) {
    $contents = imagecreatetruecolor($width, $height);
    $X          = rand(0, $width);
    $Y          = rand(0, $height);
    $phase      = rand(0, 100);
    $scale      = 1.9 + rand(0, 10000) / 30000;
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $Vx = $x - $X;
            $Vy = $y - $Y;
            $Vn = sqrt($Vx * $Vx + $Vy * $Vy);

            if ($Vn != 0) {
                $Vn2 = $Vn + 4 * sin($Vn / 30);
                $nX  = $X + ($Vx * $Vn2 / $Vn);
                $nY  = $Y + ($Vy * $Vn2 / $Vn);
            } else {
                $nX = $X;
                $nY = $Y;
            }
            $nY = $nY + $scale * sin($phase + $nX * 0.2);
            $p = imagecolorat($image, abs(round($nX) % $width), abs(round($nY) % $height));

            if ($p == 0) {
                $p = $bg;
            }

            imagesetpixel($contents, $x, $y, $p);
        }
    }

    return $contents;
}
function createCaptcha($text, $width = 192, $height = 48, $fontSize = 20, $difficulty = 1) {
    $image = imagecreatetruecolor($width, $height);

    $bgColor = imagecolorallocate($image, 238, 170, 136);
    $textColor = imagecolorallocate($image, 128, 0, 0);
    $lineColor = imagecolorallocate($image, 128, 0, 0);

    imagefill($image, 0, 0, $bgColor);

    $fontPath = __DIR__ . '/font.ttf';
    $position = 0;
    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];

        $dx1 = rand(0, 4);
        $dy1 = rand(0, 6);
        $x1 = (int)(rand(-2*$difficulty, 2*$difficulty));
        $y1 = (int)(rand($difficulty, $difficulty));

        imagettftext(
            $image,
            24,
                     rand(-6*$difficulty, 6*$difficulty),
                     $position * 16 + 16,
                     35 + $y1,
                     $textColor,
                     $fontPath,
                     $char
        );
        $position++;
    }
    for ($i = 0; $i < rand(3 * $difficulty, 4 * $difficulty); $i++) {
        imageline(
            $image,
            rand(0, $width),
                  rand(0, $height),
                  rand(0, $width),
                  rand(0, $height),
                  $lineColor
        );
    }

    $image2 = distort($image, $width, $height, $bgColor);
    $transparent = imagecolorallocatealpha($image2, 0, 0, 0, 127); // Fully transparent
    imagealphablending($image2, false);
    header('Content-Type: image/png');

    $sectionSize = 48;
    $sectionX = rand(0,$width-$sectionSize);
    $sectionY = 0;
    $section = imagecreatetruecolor($sectionSize, $sectionSize);

    imagefill($section, 0, 0, $bgColor);
    imagecopy($section, $image2, 0, 0, $sectionX, $sectionY, $sectionSize, $sectionSize);
    imagefilledrectangle($image2, $sectionX, $sectionY, $sectionX + $sectionSize, $sectionY + $sectionSize, $bgColor);
    $text = generateCaptchaText(3);
    $position = 0;
    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];

        $dx1 = rand(0, 4);
        $dy1 = rand(0, 6);
        $x1 = (int)(rand(-2*$difficulty, 2*$difficulty));
        $y1 = (int)(rand($difficulty, $difficulty));
        imagettftext(
            $image2,
            22,
                     rand(-6*$difficulty, 6*$difficulty),
                     $position * 12 + $sectionX,
                     35 + $y1,
                     $textColor,
                     $fontPath,
                     $char
        );
        $position++;
    }

    imagepng($section);
    $imageData = ob_get_clean();
    $_SESSION["captcha_section"] = base64_encode($imageData);

    imagepng($image2);
    imagedestroy($section);
    imagedestroy($image2);
}

$captchaText = generateCaptchaText(5) . " " . generateCaptchaText(5);
$_SESSION["captcha_text"] = $captchaText;
createCaptcha($captchaText);

?>
