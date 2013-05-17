qnnsafes.com-v1
===============

Old website for qnnsafes.com. Archive only.

This site is downloaded on May 16, 2013 using [SiteSucker 2.4.3](http://www.sitesucker.us/).

Some functions may not work. Images are compressed using [crushimg.sh](https://gist.github.com/caiguanhao/4528926).

Parser script
-------------

This repo contains a parser script which converts the product articles from the old website to Jekyll-friendly markdown files for the [new site](https://github.com/qnn/qnnsafes.com).

To run parser script, you need PHP.

    php parse.php

All markdown files are put in the ``products`` directory. The parser script will also collect image links in the web pages and parse them to ``curl`` commands. All commands are in a bash script called ``download_images.sh`` which you may run it to automatically download all the images. Those images would still be used for the new site.

    bash download_images.sh

Alternatively, you can also do lossless compressions on those images by using [crushimg.sh](https://gist.github.com/caiguanhao/4528926), make thumbnails or scale down the images if their resolutions are a little higher.

**Bash script to make thumbnails**

    #!/bin/bash
    set -e
    PWD="`pwd`"
    CONVERT=$(which convert)
    IMAGES=($(find $PWD -maxdepth 1 -iregex '.*\.jpg$' -type f))
    
    if [[ ! -d "${PWD}/150px" ]]; then
        mkdir "${PWD}/150px"
    fi
    
    for (( i=0; i<${#IMAGES[@]}; i++ )); do
        $CONVERT -resize 150x150 -- "${IMAGES[$i]}" "${IMAGES[$i]/$PWD/$PWD/150px}"
    done

**Bash script to scale down images**

    #!/bin/bash
    set -e
    PWD="`pwd`"
    CONVERT=$(which convert)
    IMAGES=($(find $PWD -maxdepth 1 -iregex '.*\.jpg$' -type f))
    
    for (( i=0; i<${#IMAGES[@]}; i++ )); do
        WIDTH=`identify -format "%w" "${IMAGES[$i]}"`
        echo "${IMAGES[$i]} width=$WIDTH"
        if [[ $WIDTH -gt 500 ]]; then
            $CONVERT -resize 500 -- "${IMAGES[$i]}" "${IMAGES[$i]}";
        fi
    done

Developer
---------

* caiguanhao
