(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WikiGame = {

		//leaf stuff
		floatWidth: 10,
		floatHeight: 50,
		minLeafX: 0,
		maxLeafX: 450,
		startHeight: 0,
		duration: 500,
		yDelta: 2,
		leafHeight: 0,
		intersectionDelta: 15,
		yIncrease: .5,

		//initialValues
		yDeltaInit: 2,

		//koala stuff
		koalaStepSize: 0,
		koalaWidth: 65,

		//visual objects and info
		activeLeaf: null,
		leaf1: null,
		leaf2: null,
		specialLeaf: null,
		containerWidth: 500,
		containerHeight: 546,

		//game stuff
		scoreDeltaSmall: 1,
		scoreDeltaLarge: 5,
		messages: ["wikiHow exists in 16 languages", "wikiHow is built on a free and open-source platform", "At least 4 babies have been born in an emergency situation using a wikiHow article.", "wikiHow is on twitter, join us <a href='https://twitter.com/wikiHow/timelines/672182923873783809' target='_blank'>here</a>.", "Learn about wikiHow <a href='https://www.wikihow.com/wikiHow:Tour' target='_blank'>here</a>"],
		messageCount: 0,
		articleInfo: [],
		articleInfoCount: 0,
		numInfos: 0,
		timerInterval: 0,
		isPaused: false,
		tipHeadline: "",
		whHeadline: "Did you know....",
		highScore: 0,

		init: function () {
			$("#wg_container").show();
			this.tipHeadline = "Here's a tip about How to " + mw.config.get("wgTitle") + "...";
			this.maxLeafX = this.containerWidth - this.floatWidth;
			this.leafHeight = $(".wg_leaf").height();
			this.containerWidth = $("#wg_container").width();
			this.containerHeight = $("#wg_container").height();
			this.initArticleInfo();
			this.leaf1 = new WikiLeaf($("#wg_leaf_0"), false);
			this.leaf2 = new WikiLeaf($("#wg_leaf_1"), false);
			this.specialLeaf = new WikiLeaf($("#wg_leaf_special"), true);
			$(".wg_bubble").hide();
			$("#wg_intro").show();
			$(document).on("click", ".wg_start", function(e){
				e.preventDefault();
				$("#wg_popup").hide();
				WH.WikiGame.restartGame();
				WH.WikiGame.initializeListeners()
			});
		},

		resetTimer: function() {
			$("#wg_time").html("60");
			this.startTimer();
		},

		startTimer: function() {
			this.timerInterval = setInterval($.proxy(this.updateTimer, this), 1000);
		},

		setInitialValuesForGameStart: function() {
			this.yDelta = this.yDeltaInit;
			this.koalaStepSize = 10;
			this.messageCount = 0;
			this.articleInfoCount = 0;
		},

		updateTimer: function() {
			var currentTime = parseInt($("#wg_time").html())-1;
			$("#wg_time").html(currentTime);
			if(currentTime <= 0) {
				clearTimeout(this.timerInterval);
				this.endGame();
			}
		},

		endGame: function() {
			$("#wg_popup").show();
			$(".wg_bubble").hide();
			$("#wg_endgame").show();
			clearTimeout(this.timerInterval);
			this.stopAllLeaves();
			var score = parseInt($("#wg_score").html());
			this.highScore = (this.highScore < score)?score:this.highScore;
			$("#wg_end_score span").html(score);
			$("#wg_high_score span").html(this.highScore);
		},

		//gather some info from the article to show in the popups
		initArticleInfo: function() {
			$("#tips ul li").each(function(){
				WH.WikiGame.articleInfo.push($(this).html());
			});
			$(".steps_list_2 .whb").each(function(){
				WH.WikiGame.articleInfo.push($(this).text());
			});
		},

		initializeListeners: function() {
			$(document).on("keypress", function(e){
				WH.WikiGame.moveKoala(e);
			});

			$(document).on("click", "#wg_ok", function(e){
				e.preventDefault();
				$("#wg_popup").hide();
				WH.WikiGame.resumeGame();
			});

			$(document).on("click", ".wg_again", function(e){
				e.preventDefault();
				$("#wg_popup").hide();
				WH.WikiGame.restartGame();
			});
		},

		//give the user some info about wH
		//most info is about current article
		//25% is random wH facts
		giveInfo: function() {
			WH.WikiGame.numInfos++;
			var message = "", header = "";
			if(WH.WikiGame.numInfos % 4 == 0 && WH.WikiGame.messageCount < WH.WikiGame.messages.length) {
				header = WH.WikiGame.whHeadline;
				message = WH.WikiGame.messages[this.messageCount];
				this.messageCount++;
			} else {
				header = WH.WikiGame.tipHeadline;
				message = WH.WikiGame.articleInfo[this.articleInfoCount];
				this.articleInfoCount++;
			}
			$("#wg_fact p").html(message);
			$("#wg_fact .wg_bubble_headline").html(header);
			$("#wg_popup").show();
			$(".wg_bubble").hide();
			$("#wg_fact").show();
		},

		restartLeaf: function(wikiLeaf) {
			wikiLeaf.restartLeaf();
		},

		//test whether the two objects are overlapping visually
		overlap: function(leaf, koala) {
			var pos = $( leaf ).position();
			var leafPos = [ [ pos.left, pos.left + $(leaf).width() ], [ pos.top, pos.top + $(leaf).height() ] ];
			var pos2 = $( koala ).position();
			var koalaPos = [ [ pos2.left, pos2.left + $(koala).width() ], [ pos2.top, pos2.top + $(koala).height() ] ];

			var r1, r2;
			r1 = leafPos[0][0] < koalaPos[0][0] ? leafPos[0] : koalaPos[0]; //left object
			r2 = leafPos[0][0] < koalaPos[0][0] ? koalaPos[0] : leafPos[0]; //right object
			var l1, l2;
			l1 = leafPos[1][0] < koalaPos[1][0] ? leafPos[1] : koalaPos[1]; //top object
			l2 = leafPos[1][0] < koalaPos[1][0] ? koalaPos[1] : leafPos[1]; //bottom object
			return (r1[1] > r2[0] + WH.WikiGame.intersectionDelta /*|| r1[0] === r2[0]*/) && (l1[1] > l2[0] + WH.WikiGame.intersectionDelta  /*|| l1[0] === l2[0]*/);
		},

		moveKoala: function(e) {
			if( !this.isPaused ) {
				var code = e.keyCode || e.which;
				var position = $(".wg_koala").position();
				if (code == 106) {
					if( position.left > WH.WikiGame.koalaStepSize ) {
						//j = move left
						$(".wg_koala").css("left", position.left - WH.WikiGame.koalaStepSize);
					} else {
						$(".wg_koala").css("left")
					}
				} else if (code == 107 && position.left + WH.WikiGame.koalaWidth + WH.WikiGame.koalaStepSize < WH.WikiGame.containerWidth) {
					//k = move right
					$(".wg_koala").css("left", position.left + WH.WikiGame.koalaStepSize);
				}
			}
		},

		//start a new leaf from the top of the screen. Takes the current leaf
		//as a parameter so we don't release the same one immediate after
		releaseLeaf: function(leaf) {
			if(leaf != this.specialLeaf && Math.random() > .7) {
				this.specialLeaf.restartLeaf();
			}else if(leaf == this.leaf1) {
				this.leaf2.restartLeaf();
			} else {
				this.leaf1.restartLeaf();
			}
		},

		pauseGame: function() {
			this.isPaused = true;
			this.pauseLeaves();
			clearTimeout(this.timerInterval);
		},

		resumeGame: function() {
			this.isPaused = false;
			this.startTimer();
			this.restartLeaves();
		},

		restartGame: function() {
			this.yDelta = 2;
			this.koalaStepSize = 10;
			this.isPaused = false;
			this.setInitialValuesForGameStart();
			this.leaf2.moveOffscreen();
			this.specialLeaf.moveOffscreen();
			this.leaf1.restartLeaf();
			this.resetTimer();
			WH.WikiGame.shuffle(this.messages);
			WH.WikiGame.shuffle(this.articleInfo);
			$("#wg_score").html("0");
		},

		stopAllLeaves: function() {
			this.leaf1.stopAnimation();
			this.leaf2.stopAnimation();
			this.specialLeaf.stopAnimation();
		},

		//pause all leaves when an info box is being show
		//for now we only have 1 special leaf that triggers the info box
		//so we don't need to pause it, but might need to revisit this logic later
		pauseLeaves: function() {
			this.leaf1.pauseAnimation();
			this.leaf2.pauseAnimation();
		},

		//the only time we restart the leaves is after an info box
		//which is triggered by catching a special leaf, so no need to restart that one
		//but we might need to revisit this is the future if we have more special leaves
		restartLeaves: function() {
			this.leaf1.restartAnimation();
			this.leaf2.restartAnimation();
		},

		shuffle: function(array) {
			var currentIndex = array.length, temporaryValue, randomIndex;

			// While there remain elements to shuffle...
			while (0 !== currentIndex) {

				// Pick a remaining element...
				randomIndex = Math.floor(Math.random() * currentIndex);
				currentIndex -= 1;

				// And swap it with the current element.
				temporaryValue = array[currentIndex];
				array[currentIndex] = array[randomIndex];
				array[randomIndex] = temporaryValue;
			}
		}
	}

	function WikiLeaf(obj, isSpecial) {
		this.$leafElem = obj;
		obj[0].wikiLeaf = this;
		this.setInitialValues();
		this.width = this.$leafElem.width();
		this.isSpecial = isSpecial;
		this.isPaused = false;
		this.isAnimating = false;
	}

	//get a leaf moving. If it was paused, then no need to set up it's placement
	//if it wasn't paused, then initialize everything.
	//then start the animation
	WikiLeaf.prototype.restartLeaf = function() {
		if( !this.isPaused ) {
			this.setInitialValues();
		}
		this.startAnimation();
	};

	WikiLeaf.prototype.moveOffscreen = function() {
		this.$leafElem.css("top", "-50px");
	};

	WikiLeaf.prototype.setInitialValues = function() {
		this.yDelta = WH.WikiGame.yDelta;
		this.startX = this.getStartX();
		this.startY = -50;
		this.floatWidth = WH.WikiGame.floatWidth;
		this.floatHeight = WH.WikiGame.floatHeight;
		this.$leafElem.css({"left": this.startX, "top": this.startY});
		this.newLeafY = Math.random()*WH.WikiGame.containerHeight*.3 + WH.WikiGame.containerHeight*.6; //randomly pick when another leaf will start falling
	};

	//figure out where the leaf should be to start on the x axis
	//must be on screen, and must not intersect with the koala
	//if the koala doesn't move
	WikiLeaf.prototype.getStartX = function() {
		var startX = 100;
		var koalaPosition = $( ".wg_koala" ).position();
		var koalaLeft = koalaPosition.left;
		var koalaRight = koalaPosition.left + $( ".wg_koala" ).width();
		var safetyCheck = 0;
		do {
			startX = Math.floor(Math.random()*(WH.WikiGame.maxLeafX-WH.WikiGame.minLeafX) + WH.WikiGame.minLeafX);
			safetyCheck++;
		} while( safetyCheck < 5 && !(startX > koalaRight || (startX + this.width + this.floatWidth < koalaLeft)) );
		return startX;
	};

	WikiLeaf.prototype.startAnimation = function() {
		var position = this.$leafElem.position();
		var finalPos = (position.left == this.startX) ? (this.startX + this.floatWidth) : this.startX;

		this.$leafElem.animate(
			{left: finalPos},
			{
				duration: WH.WikiGame.duration,
				step: $.proxy(this.yMovement, this),
				done: $.proxy(this.startAnimation, this),
			}
		);
		this.isAnimating = true;
		this.paused = false;
	};

	//we only want to restart animation ever if it was paused
	//restarting only happens after an info box
	WikiLeaf.prototype.restartAnimation = function() {
		if(this.paused) {
			this.startAnimation();
			this.paused = false;
		}
	};

	WikiLeaf.prototype.pauseAnimation = function() {
		if(this.isAnimating) {
			this.paused = true;
			this.$leafElem.stop();
		}
	};

	WikiLeaf.prototype.stopAnimation = function() {
		this.isAnimating = false;
		this.$leafElem.stop();
	};

	//the jquery animation handles the x-axis movement
	//this handles the y-axis and also checks for any
	//intersection with a koala
	WikiLeaf.prototype.yMovement = function(a, b) {
		var delta = Math.random()*this.yDelta;
		var startTop = this.startY + delta;
		this.startY = startTop;
		var newY = startTop + this.floatHeight*Math.sin(Math.PI*Math.abs(b.end - a)/this.floatWidth)/6;
		$(b.elem).css("top", newY);
		if(WH.WikiGame.overlap(b.elem, $(".wg_koala"))) {
			//It hit a koala!!
			$(b.elem).stop();
			WH.WikiGame.activeLeaf = this;
			if(Math.random() > .25) { //75% of the time, make the leaves and the koala move faster
				WH.WikiGame.yDelta += WH.WikiGame.yIncrease;
				WH.WikiGame.koalaStepSize++;
			}
			if(this.isSpecial) { //special leaf? Show some info
				$("#wg_score").html(parseInt($("#wg_score").html()) + WH.WikiGame.scoreDeltaLarge);
				WH.WikiGame.pauseGame();
				WH.WikiGame.giveInfo();
			} else {
				$("#wg_score").html(parseInt($("#wg_score").html()) + WH.WikiGame.scoreDeltaSmall);
			}
			this.moveOffscreen();
			this.isAnimating = false;
			if( !this.hasSpawnedLeaf() ) {
				this.spawnLeaf();
				if(this.isSpecial) {
					WH.WikiGame.pauseLeaves(); //we just spawned one, but we actually want it to start paused
				}
			}
		} else if(newY > WH.WikiGame.containerHeight) {
			//has it gone off screen, stop the animation
			$(b.elem).stop();
		}
		if(!this.hasSpawnedLeaf() && newY >= this.newLeafY) {
			this.spawnLeaf();
		}
	};

	WikiLeaf.prototype.hasSpawnedLeaf = function() {
		return this.newLeafY == -1;
	};

	WikiLeaf.prototype.spawnLeaf = function() {
		this.newLeafY = -1;
		WH.WikiGame.releaseLeaf(this);
	};

	//WH.WikiGame.init(); //opti will be doing this
	//tell opti this is a page with the game
	window['optimizely'] = window['optimizely'] || [];
	window['optimizely'].push({
		type: "page",
		pageName: "526710254_koala_game_test_desktop"
	});
}($, mw));