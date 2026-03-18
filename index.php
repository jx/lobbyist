<?php
// ============================================================
//  index.php  –  Visitor-facing lobby panel
// ============================================================
require_once __DIR__ . '/db.php';
$employees = get_active_employees();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page-header">
    <h1>Welcome</h1>
    <p class="subtitle">Select the person you are here to see</p>
</div>

<div class="employee-grid">
<?php if (empty($employees)): ?>
    <p class="empty-state">No staff members are currently available.</p>
<?php else: ?>
    <?php foreach ($employees as $emp): ?>
    <button
        class="employee-card"
        data-order="<?= (int)$emp['display_order'] ?>"
        data-name="<?= htmlspecialchars($emp['name'], ENT_QUOTES) ?>"
        type="button"
    >
        <div class="card-photo">
            <?php if (!empty($emp['photo']) && file_exists(__DIR__ . '/photos/' . $emp['photo'])): ?>
                <img src="photos/<?= htmlspecialchars($emp['photo'], ENT_QUOTES) ?>"
                     alt="<?= htmlspecialchars($emp['name'], ENT_QUOTES) ?>">
            <?php else: ?>
                <div class="photo-placeholder">
                    <?= htmlspecialchars(mb_substr($emp['name'], 0, 1), ENT_QUOTES) ?>
                </div>
            <?php endif; ?>
        </div>
        <span class="card-name"><?= htmlspecialchars($emp['name'], ENT_QUOTES) ?></span>
    </button>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Modal overlay -->
<div id="modal-overlay" class="modal-overlay hidden" aria-modal="true" role="dialog">
    <div class="modal-box">

        <!-- Step 1: Name entry -->
        <div id="step-enter" class="modal-step">
            <p class="modal-heading">Who should we notify?</p>
            <p class="modal-sub">Please enter your name so <strong id="emp-name-label"></strong> knows you've arrived.</p>
            <input
                id="visitor-input"
                class="visitor-input"
                type="text"
                placeholder="Your name"
                maxlength="80"
                autocomplete="off"
            >
            <p id="input-error" class="input-error hidden">Please enter your name to continue.</p>
            <div class="modal-actions">
                <button id="btn-cancel" class="btn btn-secondary" type="button">Cancel</button>
                <button id="btn-submit" class="btn btn-primary" type="button">Notify</button>
            </div>
        </div>

        <!-- Step 2: Confirmation -->
        <div id="step-confirm" class="modal-step hidden">
            <div class="confirm-icon">&#10003;</div>
            <p class="modal-heading" id="confirm-message"></p>
            <p class="modal-sub">You can have a seat and they'll be right with you.</p>
            <div class="countdown-bar"><div id="countdown-fill" class="countdown-fill"></div></div>
        </div>

    </div>
</div>

<script>
(function () {
    let selectedOrder = null;
    let dismissTimer  = null;
    let countdownAnim = null;

    const overlay      = document.getElementById('modal-overlay');
    const stepEnter    = document.getElementById('step-enter');
    const stepConfirm  = document.getElementById('step-confirm');
    const empLabel     = document.getElementById('emp-name-label');
    const visitorInput = document.getElementById('visitor-input');
    const inputError   = document.getElementById('input-error');
    const btnCancel    = document.getElementById('btn-cancel');
    const btnSubmit    = document.getElementById('btn-submit');
    const confirmMsg   = document.getElementById('confirm-message');
    const countdownFill = document.getElementById('countdown-fill');

    // Open modal when an employee card is clicked
    document.querySelectorAll('.employee-card').forEach(function (card) {
        card.addEventListener('click', function () {
            selectedOrder = card.dataset.order;
            empLabel.textContent = card.dataset.name;
            visitorInput.value   = '';
            inputError.classList.add('hidden');
            stepEnter.classList.remove('hidden');
            stepConfirm.classList.add('hidden');
            overlay.classList.remove('hidden');
            setTimeout(function () { visitorInput.focus(); }, 50);
        });
    });

    function closeModal() {
        overlay.classList.add('hidden');
        clearTimeout(dismissTimer);
        if (countdownAnim) { countdownAnim.cancel(); countdownAnim = null; }
        selectedOrder = null;
    }

    btnCancel.addEventListener('click', closeModal);

    // Allow pressing Enter in the input to submit
    visitorInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') btnSubmit.click();
    });

    btnSubmit.addEventListener('click', function () {
        const name = visitorInput.value.trim();
        if (!name) {
            inputError.classList.remove('hidden');
            visitorInput.focus();
            return;
        }
        inputError.classList.add('hidden');
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Sending…';

        const body = new URLSearchParams({
            display_order: selectedOrder,
            visitor_name:  name,
        });

        fetch('send_notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Notify';

            confirmMsg.textContent = data.message || 'You have been announced.';
            stepEnter.classList.add('hidden');
            stepConfirm.classList.remove('hidden');

            // Animate countdown bar over 10 s
            countdownFill.style.transition = 'none';
            countdownFill.style.width = '100%';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    countdownFill.style.transition = 'width 10s linear';
                    countdownFill.style.width = '0%';
                });
            });

            dismissTimer = setTimeout(closeModal, 10000);
        })
        .catch(function () {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Notify';
            inputError.textContent = 'A network error occurred. Please try again.';
            inputError.classList.remove('hidden');
        });
    });

    // Close on overlay click (outside the box)
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });
}());
</script>
</body>
</html>
