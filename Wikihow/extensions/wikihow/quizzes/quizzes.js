// quiz functions
function processQuestion() {
	//grab a whole buncha data
	var quiz = $.parseJSON($('#full_quiz').html());
	var current_question = parseInt($('#quiz_progress .on').html()) - 1;
	var question_data = quiz[current_question];
	var user_answer = $('#the_quiz li.chosen').attr('id');
	var correct_answer = (question_data['correct'].toLowerCase().charCodeAt() - 97);
	
	//they answered, right?
	if (!user_answer) return false;
	
	//highlight the correct answer
	$('#answer_'+correct_answer).addClass('correct');
	
	$('#answer_'+correct_answer+' .encircler').pietimer({
		timerSeconds: 1,
		color: '#390'
	});
	
	//clear the response's class
	$('#quiz_response').removeClass();
	
	//were they right?
	if (correct_answer == user_answer.replace(/answer_/i,'')) {
		//correct!
		current_result = 'yay';
		response = wfMsg('quiz-response-correct');
		$('#quiz_response').addClass('correct');
	}
	else {
		//wrong
		current_result = 'nay';
		response = wfMsg('quiz-response-wrong');
		$('#the_quiz li.chosen, #quiz_response').addClass('wrong');
	}
	
	//mark the progress bar
	$prog = $('#quiz_progress .on');
	$prog.html('');
	$prog.toggleClass('on');
	$prog.addClass(current_result);
	
	//give the reason
	$('#quiz_response').html(response);
	$('#quiz_reason').html(question_data['reason']);
	$('#quiz_response_box').slideDown();
	
	//no SUBMIT button...
	$('#quiz_submit').hide();
	
	//set up NEXT button
	$('#quiz_next').unbind('click')
	.click(function() {
		getNextQuestion(current_question, quiz);
		return false;
	})
	.show();
}

function getNextQuestion(last_question, quiz) {
	var next_question = (last_question+1);
	
	//are we at the end?
	if (next_question == quiz.length) {
		getResultsPage(quiz);
		return;
	}
	
	//ad time?
	if (next_question == 2 && $('.wh_ad_interstitial').length) {
		$('.wh_ad_interstitial').show();
		//set the interstitial cookie as per interstitialCookie.js
		$.cookie('adSenseInterstitial', "adSenseInterstitialValue", {expires: 1, path: '/'});
		window.setTimeout(closeInterstitial,30000);
	}
	
	$('#quiz_response_box').slideUp();

	var next = quiz[next_question];
	
	//reset the question
	$('#quiz_question').hide().html(next['question']).fadeIn(800);
	
	//reset the answers
	$('#the_quiz ol').animate({
		'right': '+=600px'
	}, 'fast', function() {
		$(this).empty();
		$.each(next['answers'], function(key, val) {
			ltr = String.fromCharCode(key+97);
			li = '<li id="answer_'+key+'"><div class="encircler"></div>'+
				 '<p>'+ltr+'</p>'+val+'</li>';
			$('#the_quiz ol').append(li);
		});	
		addAnswerClickHandlers();
		$('#the_quiz ol').css('right','-600px')
		.animate({ 'right': '+=600px' }, 'fast');
	});
	
	//progress the progress bar
	$.each($('#quiz_progress .progress_num'), function(key, val) {
		if (key == (last_question+1)) {
			$(this).addClass('on');
			return;
		}
	});
	
	//SUBMIT, no NEXT
	$('#quiz_next').hide();
	$('#quiz_submit').css('background-color','#CCC').show();
}

function getResultsPage(quiz) {
	$('#quiz_response_box').hide();
	$('#the_quiz ol').hide();
	
	//count 'em up...
	var total = quiz.length;
	var num_correct = 0;
	$.each($('#quiz_progress .progress_num'), function(key, val) {
		if ($(this).hasClass('yay')) num_correct++;
	});
	
	var quips = $.parseJSON($('#quiz_quips').html());
	var percent = (num_correct/total);
	if (percent == 1) {
		quip = quips['perfect'];
	}
	else if (percent >= 0.6) {
		quip = quips['good'];
	}
	else if (percent >= 0.3) {
		quip = quips['okay'];
	}
	else {
		quip = quips['bad'];
	}
	
	$('#quiz_question').hide()
	.html(wfMsg('quiz-results',num_correct,total))
	.append('<div id="result_comment">'+quip+'</div>')
	.fadeIn(800);
	
	//the bubble
	getOtherQuizzes($('#quiz_response_box'));
	$('#quiz_response').html(wfMsg('take-more-quizzes')).attr('class','more_quizzes_head');
	$('#quiz_reason').html('');
	$('#quiz_response_box').slideDown();
	
	//transform the NEXT button into a DONE button
	$('#quiz_next').unbind('click')
	.html(wfMsg('quiz-done-button'))
	.click(function() {
		var ref = getReferrer();
		if (ref) {
			//send them back to whence they came
			window.location.href = ref;
		}
		else {
			//go back to the article as specified in the FOUND IN bubble
			window.location.href = $('#the_article_url').attr('href');
		}
		return false;
	});
}

function getOtherQuizzes(box) {
	var quiz_name = $('#quiz_name').html();
	var url = '/Quiz/?otherquizzesfor='+quiz_name;
	var html = '';
	
	$.getJSON(url, function(data) {
		//get each quiz
		$.each(data, function(key,quiz) {
			//format the quiz
			html += '<a class="other_quiz" href="/Quiz/'+quiz.name+'" '+
					' style="background-image:url('+quiz.image+')">'+
					'<p><span>'+wfMsg('quiz_other_super')+'</span>'+quiz.name.replace(/-/gi,' ')+'</p></a>';
		});
		html = '<div id="other_quizzes">'+html+'</div>';
		box.append(html);
	});
	return;
}

function addClickHandlers() {
	//the answers
	addAnswerClickHandlers();
	
	//the submit button
	$('#quiz_submit').click(function() {
		processQuestion();
		return false;
	});
}

function addAnswerClickHandlers() {
	$('#the_quiz li').click(function() {
		//quit if we're not choosing an answer
		if (!$('#quiz_submit').is(':visible')) return;
		//don't want anything else to be chosen
		$('#the_quiz li').removeClass('chosen');
		//choose it!
		$(this).addClass('chosen');
		//enable submit
		$('#quiz_submit').css('background-color','#01769F').css('cursor','pointer');
	});
}

function getReferrer() {
	var ref = document.referrer;
	if (ref.match(/(wikihow.com|wikiknowhow.com|wikidiy.com|wikidogs.com)/)) {
		return ref;
	}
	else {
		return '';
	}
}

$(document).ready(function() {
	//add click handlers
	addClickHandlers();
});



/* 
 * circling function 
 * based on pie timer by http://blakek.us/
*/
(function( $ ) {

    $.fn.pietimer = function( method ) {
        // Method calling logic
        if ( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.pietimer' );
        }
    };

    var methods = {
        init : function( options ) {
            var state = {
                timer: null,
                timerSeconds: 1,
                callback: function () {},
                timerCurrent: 0,
                fill: false,
                color: '#390'
            };

            state = $.extend(state, options);

            return this.each(function() {

                var $this = $(this);
                var data = $this.data('pietimer');
                if ( ! data ) {
                    $this.addClass('pietimer');
                    $this.css({fontSize: '58px'});
                    $this.data('pietimer', state);
                    if (state.fill) {
                        $this.addClass('fill');
                    }
                    $this.pietimer('start');
                }
            });
        },

        stopWatch : function() {
            var data = $(this).data('pietimer');
            if ( data ) {
                var seconds = (data.timerFinish-(new Date().getTime()))/1000;
                if (seconds <= 0) {
                    clearInterval(data.timer);
                    $(this).pietimer('drawTimer', 100);
                    data.callback();
                } else {
                    var percent = 100-((seconds/(data.timerSeconds))*100);
                    $(this).pietimer('drawTimer', percent);
                }
            }
        },

        drawTimer : function (percent) {
            $this = $(this);
            var data = $this.data('pietimer');
            if (data) {
                $this.html('<div class="slice'+(percent > 50?' gt50"':'"')+'><div class="pie"></div>'+(percent > 50?'<div class="pie fill"></div>':'')+'</div>');
                var deg = 360/100*percent;
                $this.find('.slice .pie').css({
                    '-moz-transform':'rotate('+deg+'deg)',
                    '-webkit-transform':'rotate('+deg+'deg)',
                    '-o-transform':'rotate('+deg+'deg)',
                    'transform':'rotate('+deg+'deg)'
                });
                if ($this.hasClass('fill')) {
                    $this.find('.slice .pie').css({backgroundColor: data.color});
                }
                else {
                    $this.find('.slice .pie').css({borderColor: data.color});
                }
            }
        },
        
        start : function () {
            var data = $(this).data('pietimer');
            if (data) {
                data.timerFinish = new Date().getTime()+(data.timerSeconds*1000);
                $(this).pietimer('drawTimer', 0);
                data.timer = setInterval("$this.pietimer('stopWatch')", 50);
            }
        },

        reset : function () {
            var data = $(this).data('pietimer');
            if (data) {
                clearInterval(data.timer);
                $(this).pietimer('drawTimer', 0);
            }
        }

    };
})(jQuery);
