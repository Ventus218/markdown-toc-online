<?php
    header("Content-Type: application/json");

    // response arguments to be filled
    $toc = null;
    $max_depth = null;
    $no_first_h1 = null;
    $server_ip = null;
    $error = null;

    $body_json = file_get_contents('php://input');
    $input = json_decode($body_json, TRUE);
    // TODO: check for valid json
    $md_text = $input["md-text"];

    $md_text_arg = escapeshellarg($md_text);
    $command = "unset LD_LIBRARY_PATH && echo ".$md_text_arg." | markdown-toc";

    if (isset($input["max-depth"])) {
        $max_depth = $input["max-depth"];

        if (is_int($max_depth)) {
            $command.=" --maxdepth ".$max_depth;
        } else {
            $error = "Max depth must be an integer value";
        }
    }

    if (isset($input["no-first-h1"])) {
        $no_first_h1 = $input["no-first-h1"];

        if (is_bool($no_first_h1)) {
            if ($no_first_h1) {
                $command.=" --no-firsth1";
            }
        } else {
            $error = "Exclude first H1 must be true or false or absent";
        }
    }

    $command.=" -"; // markdown-toc argument in ordear to read from stdin
    $result = shell_exec($command);

    $server_ip = $_SERVER['SERVER_ADDR'];

    if (!empty($result)) {
        $replaced = preg_replace("/<!-- toc here -->/", $result, $md_text, 1, $count);
        $toc = $count == 1 ? $replaced : $result;
    } else {
        $error = "TOC could not be generated";    
    }
    
    $response = array("toc" => $toc, "max-depth" => $max_depth, "no-first-h1" => $no_first_h1, "server-ip" => $server_ip, "error" => $error);
    $output = json_encode($response);

    echo $output;
?>
