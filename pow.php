<!DOCTYPE html>
<html>
  <head>
    <?php
    require "/srv/http/inc/crypto.php";
    session_start();
    $hash = clientHash();
    ?>
    <link rel="stylesheet" href="/style.css">
    <script>
      function base64Encode(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        for (let i = 0; i < bytes.byteLength; i++) {
          binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
      }

      async function hashSHA256(message) {
        const msgBuffer = new TextEncoder().encode(message);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        return new Uint8Array(hashBuffer);
      }

      function countLeadingZeros(binaryString) {
        let count = 0;
        for (let i = 0; i < binaryString.length; i++) {
          if (binaryString[i] === '0') {
            count++;
          } else {
            break;
          }
        }
        return count;
      }

      async function solvePoW(clientHash, day, difficulty) {
        let solution = 0;
        let validSolution = false;
        let hashCount = 0;
        let startTime = performance.now();

        // Create a timer to update the hashrate every second
        const hashRateInterval = setInterval(() => {
          const elapsedTime = (performance.now() - startTime) / 1000;
          const hashRate = Math.floor(hashCount / elapsedTime);
          document.getElementById("hashRate").textContent = `${hashRate} H/s`;
        }, 1000);

        while (!validSolution) {
          const solutionStr = solution.toString();
          const combinedStr = clientHash + day + solutionStr;
          const hashArray = await hashSHA256(combinedStr);
          
          // Convert the hash to a binary string
          let binaryHash = '';
          for (let byte of hashArray) {
            binaryHash += byte.toString(2).padStart(8, '0');
          }

          hashCount++; // Increment the hash count

          // Check for leading zeros in the binary hash
          if (countLeadingZeros(binaryHash) >= difficulty) {
            validSolution = true;
            clearInterval(hashRateInterval); // Stop the hashrate timer when a solution is found
          } else {
            solution++;
          }
        }

        return solution;
      }

      async function generatePoW() {
        const clientHash = document.getElementById("clientHash").value;
        const day = document.getElementById("day").value;
        const difficulty = parseInt(document.getElementById("difficulty").value, 10);

        const solution = await solvePoW(clientHash, day, difficulty);
        
        document.getElementById("powSolution").value = solution;
        document.getElementById("powForm").submit();
      }
    </script>
  </head>
  <body>
    <div class="center">
      <h1>Error</h1>
      <hr>
      <h3>To prevent spam, this board requires PoW solution before posting</h3>
      <p>
	Proof of work (PoW) is a method to prevent spam and DDoS attacks
      </p>
      <hr>
      <h4>Your client hash is <?php echo $hash;?></h4>
      <h4>Your requests per minute is <?php echo intval(requestsPerMinute()); ?></h4>
      <?php
        if (!isset($_SESSION["powSolution"])) {
          echo '<p>PoW not generated</p>';
        } elseif (validatePoW($_SESSION["powSolution"], intval(15+(requestsPerMinute()/30)))) { // Update difficulty here
            echo '<p>PoW is valid</p>';
        } else {
            echo '<p>PoW is invalid</p>';
        }
      ?>

      <p id="status">Click the button below to start solving the Proof of Work.</p>
      <p id="hashRate"></p>

      <form id="powForm" method="post" action="submitpow.php">
        <input type="hidden" id="powSolution" name="powSolution" value="">
        <input type="hidden" id="clientHash" name="clientHash" value="<?php echo $hash; ?>">
        <input type="hidden" id="day" name="day" value="<?php echo strval(intval(time() / 86400)); ?>">
        <input type="hidden" id="difficulty" name="difficulty" value="<?php echo intval(15+(requestsPerMinute()/30)) ?>">
        <button type="button" onclick="generatePoW();">Solve PoW</button>
      </form>
      <br>
      <button type="button" onclick="history.back();">Back</button>
    </div>
  </body>
</html>
