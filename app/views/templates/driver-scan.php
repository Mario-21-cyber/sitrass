<?php require __DIR__ . '/_driver_header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<h2><?= t('nav_scan_qr') ?></h2>

<?php if (!empty($result)): ?>
    <div class="alert <?= $result['success'] ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($result['message']) ?>
    </div>
<?php endif; ?>

<div style="display:flex; gap:0.5rem; margin-bottom:1rem;">
    <button type="button" id="tabCameraBtn" class="btn" style="width:auto; padding:0.5rem 1rem;" onclick="showTab('camera')"><?= t('qr_camera_tab') ?></button>
    <button type="button" id="tabManualBtn" class="btn-ghost" style="padding:0.5rem 1rem;" onclick="showTab('manual')"><?= t('qr_manual_tab') ?></button>
</div>

<div id="cameraTab" style="max-width:400px;">
    <div id="cameraContainer" style="position:relative; display:none; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:1rem; background:#000;">
        <video id="qrVideo" style="width:100%; display:block;" playsinline></video>
        <canvas id="qrCanvas" style="display:none;"></canvas>
        <div style="position:absolute; inset:0; border:3px solid var(--amber-light); border-radius:var(--radius); pointer-events:none; opacity:0.6;"></div>
    </div>
    <p id="cameraStatus" class="text-sm text-muted" style="margin-bottom:0.75rem;"></p>
    <button type="button" id="cameraToggleBtn" class="btn" onclick="toggleCamera()"><?= t('qr_camera_start') ?></button>

    <form id="cameraForm" method="POST" action="/sitrass/public/driver/verifyQr" style="display:none;">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" id="cameraToken">
    </form>
</div>

<div id="manualTab" style="display:none; max-width:400px;">
    <form method="POST" action="/sitrass/public/driver/verifyQr">
        <?= Csrf::field() ?>
        <div class="field">
            <label><?= t('qr_input_label') ?></label>
            <input type="text" name="token" required autofocus>
        </div>
        <button type="submit" class="btn"><?= t('btn_verify') ?></button>
    </form>

    <p style="font-size:0.85rem; color:var(--ocean); margin-top:1rem;">
        <?= t('qr_helper_text') ?>
    </p>
</div>

<a href="/sitrass/public/driver/dashboard" class="btn-link" style="margin-top:1rem; display:inline-block;"><?= t('link_back_dashboard') ?></a>

<script>
function showTab(tab) {
    const cameraTab = document.getElementById('cameraTab');
    const manualTab = document.getElementById('manualTab');
    const cameraBtn = document.getElementById('tabCameraBtn');
    const manualBtn = document.getElementById('tabManualBtn');

    if (tab === 'camera') {
        cameraTab.style.display = 'block';
        manualTab.style.display = 'none';
        cameraBtn.className = 'btn';
        manualBtn.className = 'btn-ghost';
    } else {
        cameraTab.style.display = 'none';
        manualTab.style.display = 'block';
        cameraBtn.className = 'btn-ghost';
        manualBtn.className = 'btn';
        stopCameraStream();
    }
}

let cameraStream = null;
let scanLoopId = null;

function toggleCamera() {
    if (cameraStream) {
        stopCameraStream();
    } else {
        startCameraStream();
    }
}

function startCameraStream() {
    const statusEl = document.getElementById('cameraStatus');
    const container = document.getElementById('cameraContainer');
    const toggleBtn = document.getElementById('cameraToggleBtn');
    const video = document.getElementById('qrVideo');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusEl.textContent = <?= json_encode(t('qr_camera_not_supported')) ?>;
        return;
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            cameraStream = stream;
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            video.play();
            container.style.display = 'block';
            toggleBtn.textContent = <?= json_encode(t('qr_camera_stop')) ?>;
            statusEl.textContent = <?= json_encode(t('qr_camera_scanning')) ?>;
            scanLoopId = requestAnimationFrame(scanFrame);
        })
        .catch(function(err) {
            statusEl.textContent = <?= json_encode(t('qr_camera_permission_denied')) ?>;
            console.warn('Camera error:', err);
        });
}

function stopCameraStream() {
    if (scanLoopId) {
        cancelAnimationFrame(scanLoopId);
        scanLoopId = null;
    }
    if (cameraStream) {
        cameraStream.getTracks().forEach(function(track) { track.stop(); });
        cameraStream = null;
    }
    document.getElementById('cameraContainer').style.display = 'none';
    document.getElementById('cameraToggleBtn').textContent = <?= json_encode(t('qr_camera_start')) ?>;
    document.getElementById('cameraStatus').textContent = '';
}

function scanFrame() {
    const video = document.getElementById('qrVideo');
    const canvas = document.getElementById('qrCanvas');

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);

        if (code && code.data) {
            document.getElementById('cameraStatus').textContent = <?= json_encode(t('qr_camera_detected')) ?>;
            document.getElementById('cameraToken').value = code.data;
            stopCameraStream();
            document.getElementById('cameraForm').submit();
            return;
        }
    }
    scanLoopId = requestAnimationFrame(scanFrame);
}
</script>

<?php require __DIR__ . '/_driver_footer.php'; ?>