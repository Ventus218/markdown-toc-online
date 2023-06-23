<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Markdown TOC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container-fluid p-3">
        <div class="row gy-3">
            <main>
                <div class="col-12">
                    <h1 class="fw-bold">Markdown T<span class="fs-6 fw-normal">able</span>O<span class="fs-6 fw-normal">f</span>C<span class="fs-6 fw-normal">ontents</span></h1>
                </div>
            
                <form class="col form-floating" action="" method="post">
                    <div class="row align-items-center gy-3 mb-3">
                        <div class="col-md col-12">
                            <textarea class="form-control" style="height: 40vh;" name="md-text" id="md-text" placeholder="# Title

<!-- toc here -->

## Heading2
foo

### Heading3
bar

## foobar" required><?php echo $md_text ?? ""; ?></textarea>
                        </div>
                        <div class="col-md-auto col-12">
                            <button class="form-control btn btn-primary" type="submit">Generate</button>
                        </div>
                        <div class="col-md col-12">
                            <textarea class="form-control" style="height: 40vh;" id="toc"><?php echo $toc ?? ""; ?></textarea>
                        </div>
                    </div>
                    <section>
                        <div class="row gy-3">
                            <div class="col-12">
                                <h2>Options</h2>
                            </div>
                            <div class="col-12">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <label for="max-depth">Max depth</label>
                                    </div>
                                    <div class="col-auto">
                                        <input class="form-control" type="number" name="max-depth" id="max-depth-input" value="<?php echo $max_depth ?? 6 ?>" min="1" required/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <label class="form-check-label" for="no-first-h1">Exclude first H1</label>
                                    </div>
                                    <div class="col-auto">
                                        <input class="form-check-input" type="checkbox" name="no-first-h1" id="no-first-h1-input" <?php echo ($no_first_h1 ?? true) ? "checked" : ""; ?>/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <p> If <code>&lt!-- toc here --&gt</code> gets found in the source markdown then the toc will be generated and included automatically. <br> You can place it wherever you like! </p>
                            </div>
                        </div>
                    </section>
                </form>
            </main>

            <footer>
                <?php if (isset($error)): ?>
                    <div class="col-12">
                        <p class="text-danger my-0"> An error was encountered: <?php echo $error; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($server_ip)): ?>
                        <div class="col-12">
                            <p class="text-secondary my-0"> Executed on server with ip: <?php echo $server_ip; ?></p>
                        </div>
                    <?php endif; ?>
            </footer>
        </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>