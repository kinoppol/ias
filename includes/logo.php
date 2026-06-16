<?php
function ovec_logo($size = 84, $textSize = null) {
    return '<img src="/ias/assets/img/ovec-logo.gif" width="' . (int)$size . '" height="' . (int)$size . '" alt="OVEC" style="display:block;border-radius:50%;">';
}

function ovec_logo_full($size = 84) {
    return ovec_logo($size);
}
