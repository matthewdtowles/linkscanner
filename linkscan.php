<?php

/**
 * This is a tool to test all links in a directory/doc recursively
 *
 * Time limit is temporary measure as script takes long time to run - unsure how long it should be
 * This will scan a directory and all of its children
 * Then results are written in JSON format to a new file for each scan
 * File follows a naming convention of YYYY-MM-DD-hour-minutes-linkscan.json
 * JSON is formed and added to a string called $logString
 * $start variable will need to be able to handle user input so that the scan can start where user wants it to
 * $dirs is the array of all files and directories based on a recursive scan
 * We then loop through them and find all html and php files
 * Then each $dir is cleaned up to be valid JSON and added to the $logString
 * Contents then obtained and all links are extracted
 * Only links with 'http...' in the beginning will be included to filter out Symfony style links as they are not valid URLs
 * For every link extracted, we the headers are retrieved and the response code is saved along with the href
 * Both response code and href/link are saved together as children of the file they are extracted from
 * JSON log file example:
 *   {
 *     "file/url/example.php": [
 *       {
 *         "0":[
 *           {"href":"http://www.hrefexample.com"},
 *           {"response":"200"}
 *         ],
 *         "1":[
 *           {"href":"http://www.anotherhrefexample.com"},
 *           {"response":"301"}
 *         ]
 *       }
 *     ],
 *     "file/url/another/example.html": [
 *       {
 *         "0":[
 *           {"href":"http://www.hrefexample.com"},
 *           {"response":"200"}
 *         ],
 *         "1":[
 *           {"href":"http://www.anotherhrefexample.com"},
 *           {"response":"301"}
 *         ]
 *       }
 *     ]
 *  }
 * END EXAMPLE
 * json_encode not used b/c results not as expected which is why JSON string is built this way
 * Note that the JSON is all one line and a beautifier can be used to format it nicely
 *
 * PHP v7+
 *
 * @author Matthew Towles
 * @version 0.0.0
 */

# script execution time limit default is 30
# script needs more time to run
$timeLimit = 200;
set_time_limit($timeLimit);

# set log file name/url to write to
$logFile = date("Y-m-d-H-i") . '-linkscan.json';

# log entry to be written to $logFile in JSON format
$logString = "{";

# starting point - this will be based on user input
$start = 'src';
$path = new RecursiveDirectoryIterator($start);

# an array with all directories and files
$dirs = new RecursiveIteratorIterator($path, RecursiveIteratorIterator::SELF_FIRST);

# dom created to parse html for links
$dom = new DOMDocument;

# check each file for links
foreach ($dirs as $dir) {

    # filter
    if (strpos($dir, '.php')  || strpos($dir, '.html')) {

        # replace the \ with / for valid JSON
        $dir = str_replace("\\", "/", $dir);
        $file = file_get_contents($dir);
        $logString .= "\"$dir\": [{";

        # @ symbol to suppress parsing errors due to invalid XHTML
        @$dom->loadHTML($file);

        # get all links - may want to make this more flexible
        $links = $dom->getElementsByTagName('a');

        # counter for keys
        $i = 0;

        # extract link URL and send requests
        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            # check if href is HTTP url
            # needle is ttp so http hrefs return value > 0
            if (strpos($href, 'ttp')) {

                # build contents for log
                $header = get_headers($href);
                $logString .= "\"$i\": [{\"href\": \"$href\"}, {\"response\": \"" . substr($header[0], 9, 3) . "\"}],";
                $i++;

            }
        }
        $logString = rtrim($logString, ',');
        $logString .= "}],";
    }
}
$logString = rtrim($logString, ',');
$logString .= "}";
file_put_contents($logFile, $logString);
