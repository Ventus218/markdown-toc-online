<?php
    function sendResponseAndExit(?string $toc, ?int $max_depth, ?bool $no_first_h1, string $server_ip, ?string $error) {
        $response = array("toc" => $toc, "max-depth" => $max_depth, "no-first-h1" => $no_first_h1, "server-ip" => $server_ip, "error" => $error);
        $output = json_encode($response);

        echo $output;

        if (isset($error)) {
            http_response_code(500);
            exit(0);
        } else {
            http_response_code(200);
            exit(1);
        }
    }

    header("Content-Type: application/json");

    // response arguments to be filled or with default values
    $toc = null;
    $max_depth = 6;
    $no_first_h1 = false;
    $server_ip = $_SERVER['SERVER_ADDR'];
    $error = null;

    $body_json = file_get_contents('php://input');
    $input = json_decode($body_json, TRUE);

    if (!isset($input)) {
        $error = "Body was not valid JSON";
        sendResponseAndExit($toc, $max_depth, $no_first_h1, $server_ip, $error);
    }

    if (!isset($input["md-text"])) {
        $error = "Missing \"md-text\" field";
        sendResponseAndExit($toc, $max_depth, $no_first_h1, $server_ip, $error);
    }
    $md_text = $input["md-text"];
    
    if (isset($input["max-depth"])) {
        $max_depth = $input["max-depth"];

        if (!is_int($max_depth)) {
            $error = "Max depth must be an integer value";
            sendResponseAndExit($toc, null, $no_first_h1, $server_ip, $error);
        }
    }

    if (isset($input["no-first-h1"])) {
        $no_first_h1 = $input["no-first-h1"];

        if (!is_bool($no_first_h1)) {
            $error = "Exclude first H1 must be true or false";
            sendResponseAndExit($toc, $max_depth, null, $server_ip, $error);
        }
    }
    
    $md_text_arg = escapeshellarg($md_text);
    $command = "unset LD_LIBRARY_PATH && echo ".$md_text_arg." | markdown-toc";
    $command.=" --maxdepth ".$max_depth;

    if ($no_first_h1) {
        $command.=" --no-firsth1";
    }

    $command.=" -"; // markdown-toc argument in ordear to read from stdin
    $result = shell_exec($command);

    if (!empty($result)) {
        $replaced = preg_replace("/<!-- toc here -->/", $result, $md_text, 1, $count);
        $toc = $count == 1 ? $replaced : $result;
    } else {
        $error = "TOC could not be generated";
        sendResponseAndExit($toc, $max_depth, $no_first_h1, $server_ip, $error);
    }
    
    sendResponseAndExit($toc, $max_depth, $no_first_h1, $server_ip, $error);
?>
