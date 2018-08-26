<?php
// // ini_set('zlib.output_compression',0);

// header( 'Content-type: text/html; charset=utf-8' );
// header('Content-Encoding: none');

// // ob_implicit_flush(1);
// // ob_end_flush();
// if(ob_get_level() == 0) ob_start();

// echo 'Begin ...<br />';
// for( $i = 0 ; $i < 10 ; $i++ )
// {
//     echo $i . '<br />';
//     flush();
//     ob_flush();
// //     fcflush();
//     sleep(1);
// }
// echo 'End ...<br />';

// /**
//  * Output buffer flusher
//  * Forces a flush of the output buffer to screen useful for displaying long loading lists eg: bulk emailers on screen
//  * Stops the end user seeing loads of just plain old white and thinking the browser has crashed on long loading pages.
//  */
// function fcflush()
// {
//     static $output_handler = null;
//     if ($output_handler === null) {
//         $output_handler = @ini_get('output_handler');
//     }
//     if ($output_handler == 'ob_gzhandler') {
//         // forcing a flush with this is very bad
//         return;
//     }
//     flush();
//     if (function_exists('ob_flush') AND function_exists('ob_get_length') AND ob_get_length() !== false) {
//         @ob_flush();
//     } else if (function_exists('ob_end_flush') AND function_exists('ob_start') AND function_exists('ob_get_length') AND ob_get_length() !== FALSE) {
//         @ob_end_flush();
//         @ob_start();
//     }
// }





// // Turn off output buffering
// ini_set('output_buffering', 'off');
// // Turn off PHP output compression
// ini_set('zlib.output_compression', false);

// //Flush (send) the output buffer and turn off output buffering
// //ob_end_flush();
// while (@ob_end_flush());

// // Implicitly flush the buffer(s)
// ini_set('implicit_flush', true);
// ob_implicit_flush(true);

// //prevent apache from buffering it for deflate/gzip
// header("Content-type: text/plain");
// header('Cache-Control: no-cache'); // recommended to prevent caching of event data.

// for($i = 0; $i < 10; $i++)
// {
//     echo "$i";
//     sleep(1);
// }

// ob_flush();
// flush();

// /// Now start the program output

// echo "Program Output";

// ob_flush();
// flush();





// apache_setenv('no-gzip', 1); //can comment this line

header('Content-Encoding: none');

// header( 'Content-type: text/html; charset=utf-8' );

// for ($i=0; $i<10; $i++) {
//     echo $i.'<br>';
//     flush();
//     ob_flush();
//     sleep(1);
// }


if (ob_get_level() == 0) ob_start();

for ($i = 0; $i<10; $i++){
    
    echo "<br> Line to show.";
    echo str_pad('',4096)."\n";
    
    ob_flush();
    flush();
    sleep(1);
}

echo "Done.";

ob_end_flush();

?>