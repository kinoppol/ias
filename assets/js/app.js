function pad(n) { return String(n).padStart(2, '0'); }

function updateClock() {
    const now = new Date();
    const time = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const date = now.toLocaleDateString('th-TH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.querySelectorAll('#headerClock').forEach(el => el.textContent = time + ' — ' + date);
    document.querySelectorAll('#sidebarClockTime').forEach(el => el.textContent = time);
    document.querySelectorAll('#sidebarClockDate').forEach(el => el.textContent = date);
    document.querySelectorAll('.live-time').forEach(el => el.textContent = time);
    document.querySelectorAll('.live-date').forEach(el => el.textContent = date);
}
setInterval(updateClock, 1000);
updateClock();

function doCheckIn(btn) {
    if (!navigator.geolocation) { alert('อุปกรณ์ไม่รองรับ GPS'); return; }
    btn.disabled = true;
    const origText = btn.textContent;
    btn.textContent = '📡 กำลังรับพิกัด GPS...';
    navigator.geolocation.getCurrentPosition(
        pos => {
            const fd = new FormData();
            fd.append('lat', pos.coords.latitude);
            fd.append('lng', pos.coords.longitude);
            fetch('/ias/ajax/checkin.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => { window.location.reload(); })
                .catch(() => { btn.disabled = false; btn.textContent = origText; alert('เกิดข้อผิดพลาด'); });
        },
        err => {
            btn.disabled = false; btn.textContent = origText;
            alert('ไม่สามารถรับพิกัด GPS: ' + err.message);
        },
        { enableHighAccuracy: true, timeout: 15000 }
    );
}

function doCheckOut(btn) {
    if (!navigator.geolocation) { alert('อุปกรณ์ไม่รองรับ GPS'); return; }
    btn.disabled = true;
    const origText = btn.textContent;
    btn.textContent = '📡 กำลังรับพิกัด GPS...';
    navigator.geolocation.getCurrentPosition(
        pos => {
            const fd = new FormData();
            fd.append('lat', pos.coords.latitude);
            fd.append('lng', pos.coords.longitude);
            fetch('/ias/ajax/checkout.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => { window.location.reload(); })
                .catch(() => { btn.disabled = false; btn.textContent = origText; alert('เกิดข้อผิดพลาด'); });
        },
        err => {
            btn.disabled = false; btn.textContent = origText;
            alert('ไม่สามารถรับพิกัด GPS: ' + err.message);
        },
        { enableHighAccuracy: true, timeout: 15000 }
    );
}
