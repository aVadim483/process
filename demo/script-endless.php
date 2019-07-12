<?php

ignore_user_abort(true);
set_time_limit(0);


// Clear all output buffers
@ob_end_flush();
@ob_flush();
@flush();

// Close user session
if(session_id()){
    session_write_close();
}

while (true) {
    echo time();
    sleep(1);
}

// EOF