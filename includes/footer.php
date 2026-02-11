</div> <!-- End .layout -->

<?php
// Show floating AI chat widget for patients (except on ai-assistant page which has its own chat)
if (
    isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'patient'
    && (!isset($current_page) || $current_page !== 'ai-assistant')
): ?>
    <?php include __DIR__ . '/patient_ai_chat_widget.php'; ?>
<?php endif; ?>

</body>

</html>