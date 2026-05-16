<main class="customer-shell narrow">
    <section class="panel center-panel">
        <span class="material-symbols-outlined large-icon">info</span>
        <h1><?= e($title ?? 'Notice') ?></h1>
        <p><?= e($message ?? '') ?></p>
        <a class="primary-button" href="<?= customerUrl('marketplace') ?>">Back to marketplace</a>
    </section>
</main>
