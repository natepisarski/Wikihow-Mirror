<?= $optimizelyJs ?>
<? if ($showInternetOrgAnalytics): ?>
	<?= WikihowMobileTools::getInternetOrgAnalytics() ?>
<? endif; ?>
<script>
	WH.isAltDomain = <?= json_encode(Misc::isAltDomain()); ?>;
</script>
