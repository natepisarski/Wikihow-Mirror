
<div class="convo">
	<div id="msg-render-target" class="msg-container">
		<!-- mustache templates rendered here -->
	</div>

	<div class="convo-form">
		<form id="message-form" class="form">
			<div class="form-group">
				<input type="hidden" name="message[user_id]" value="<?= $currentUser->id ?>"/>
				<input id="article-id" type="hidden" name="message[article_id]" value="<?= $article->id ?>"/>
				<textarea id="message-body" name="message[body]" placeholder="Enter your message" rows="2" class="form-control"></textarea>
				<span class="help-block">Hit enter to send</span>
			</div>
		</form>
	</div>
</div>

<script type="text/handlebars" id="message-template">
	{{#messages}}
	<div class="msg {{classIf user.is_current 'currentUser'}}" data-user="{{user.id}}">
		<div class="msg-text">
			<div class="info">
				<strong class="username">{{user.username}}</strong>
				<em class="timestamp">{{timeAgo}}</em>
			</div>
			{{body}}
		</div>
		<img class="avatar img-circle" src="{{user.avatar}}"></img>
	</div>
	{{/messages}}
</script>
