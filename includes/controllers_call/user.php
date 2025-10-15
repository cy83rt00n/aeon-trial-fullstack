<?php

function controller_user ($act, $d) {
    if ($act == 'edit_window_read') return User::edit_window_read($d);
    if ($act == 'edit_window_update') return User::edit_window_update($d);
    if ($act == 'edit_window_delete') return User::edit_window_delete($d);
    return '';
}
