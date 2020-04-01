function recordTTI(time) {
	logJSTime(time, 'tti');
}
ttiPolyfill.getFirstConsistentlyInteractive().then(recordTTI);
