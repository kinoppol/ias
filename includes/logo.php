<?php
function ovec_logo($size = 84, $textSize = 13) {
    ob_start();
    ?>
    <svg viewBox="0 0 120 120" width="<?= $size ?>" height="<?= $size ?>">
      <circle cx="60" cy="60" r="59" fill="#E65100"/>
      <circle cx="60" cy="60" r="50" fill="white"/>
      <g transform="translate(60,60)">
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(45)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(90)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(135)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(180)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(225)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(270)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(315)"/>
      </g>
      <circle cx="60" cy="60" r="43" fill="#0D47A1"/>
      <text x="60" y="65" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="<?= $textSize ?>" font-weight="bold" letter-spacing="1">OVEC</text>
    </svg>
    <?php
    return ob_get_clean();
}

function ovec_logo_full($size = 84) {
    ob_start();
    ?>
    <svg viewBox="0 0 120 120" width="<?= $size ?>" height="<?= $size ?>">
      <circle cx="60" cy="60" r="59" fill="#E65100"/>
      <circle cx="60" cy="60" r="50" fill="white"/>
      <g transform="translate(60,60)">
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(45)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(90)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(135)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(180)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(225)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(270)"/>
        <rect x="-6" y="-58" width="12" height="14" rx="3" fill="#E65100" transform="rotate(315)"/>
      </g>
      <circle cx="60" cy="60" r="43" fill="#0D47A1"/>
      <path d="M35 52 L35 74 Q47.5 68 60 73 Q72.5 68 85 74 L85 52 Q72.5 57 60 52 Q47.5 57 35 52 Z" fill="none" stroke="white" stroke-width="2.5" stroke-linejoin="round"/>
      <line x1="60" y1="52" x2="60" y2="74" stroke="white" stroke-width="2"/>
      <line x1="39" y1="57" x2="57" y2="55" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
      <line x1="39" y1="63" x2="57" y2="61" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
      <line x1="39" y1="69" x2="57" y2="67" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
      <line x1="63" y1="55" x2="81" y2="57" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
      <line x1="63" y1="61" x2="81" y2="63" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
      <line x1="63" y1="67" x2="81" y2="69" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
      <text x="60" y="90" text-anchor="middle" fill="white" font-family="Arial,sans-serif" font-size="9" font-weight="bold" letter-spacing="2">OVEC</text>
    </svg>
    <?php
    return ob_get_clean();
}
