console.log("setting up rewardedslot");
rewardedSlot.setForceSafeFrame(true);
googletag.pubads().enableAsyncRendering();
googletag.enableServices();
googletag.pubads().addEventListener('rewardedSlotReady', evt => {
	const makeVisibleFn = (e) => {
		evt.makeRewardedVisible();
		e.preventDefault();
		watchAdButton.removeEventListener('click', makeVisibleFn);
		trigger.style.display = 'none';
		original.style.display = 'block';
	};
alert("rewardedSlotReady");
	const trigger = document.getElementById('rewardedweb');
	console.log('here with trigger', trigger);
	if ( !trigger ) {
		return;
	}
	trigger.style.display = 'block';
	const original = document.getElementById('rewardedweb-original');
	original.style.display = 'none';
	const watchAdButton = document.getElementById('rewardedweb-link');
	watchAdButton.addEventListener('click', makeVisibleFn);
});
googletag.pubads().addEventListener('rewardedSlotGranted', evt => {
	// send user to new page
	const watchAdButton = document.getElementById('rewardedweb-link');
	window.location.href = watchAdButton.href;
});
googletag.display(rewardedSlot);
