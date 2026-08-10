<?php
$currentUser = $_SESSION['username'] ?? 'Usuario';
$currentRol = $_SESSION['rol'] ?? 'invitado';
?>
<div class="top-bar-simple" style="background: linear-gradient(135deg, #9F2241, #691C32); padding: 12px 24px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h5 class="text-white mb-0"><i class="fas fa-fingerprint me-2"></i>Sistema Biométrico</h5>
    </div>
    <div class="text-white">
        <i class="fas fa-user me-1"></i><?= htmlspecialchars($currentUser) ?>
        <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($currentRol) ?></span>
    </div>
</div>
