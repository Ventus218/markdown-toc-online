<?php
    if (!empty($_POST["md-text"])) {
        $md_text = $_POST["md-text"];

        if(isset($_POST["max-depth"])) {
            $max_depth = $_POST["max-depth"];
            settype($max_depth, "int");
        } else {
            $max_depth = null;
        }

        $no_first_h1 = isset($_POST["no-first-h1"]) && filter_var($_POST["no-first-h1"], FILTER_VALIDATE_BOOLEAN);;

        $host = gethostbyname(getenv("BACKEND_HOST"));
        $port = getenv("BACKEND_PORT");
        $url = "http://".$host.":".$port."/markdown-toc.php";
        
        $body = array("md-text" => $md_text, "max-depth" => $max_depth, "no-first-h1" => $no_first_h1);
        
        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json",
                'method'  => 'POST',
                'content' => json_encode($body)
                )
            );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result, TRUE);

        $server_ip = $result["server-ip"];

        if(!empty($result["toc"])) {
            $toc = $result["toc"];
        }

        if(!empty($result["max-depth"])) {
            $max_depth = $result["max-depth"];
        }
        
        if(!empty($result["no-first-h1"])) {
            $no_first_h1 = $result["no-first-h1"];
        }

        if(!empty($result["error"])) {
            $error = $result["error"];
        }
    }

    include("./index-template.php");
?>
