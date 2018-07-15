
<html>
<head>
	<title>Knowledge Box Filtering Tool</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?= styles() ?>
</head>
<body>

	<div class="modal fade" id="help-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">Using the Knowledge Box Filter</h4>
				</div>
				<div class="modal-body">

					<h4>Finding an Article</h4>
					<p>
						To begin using this tool, you must first find an article. 
						To find an article enter in the title or the id for the article in the top bar.
						<span class="bg-warning">This can either be an existing article, or one not yet written.</span> As long as it has submissions, 
						this tool will work for it.
					</p>
					<p>
						If you work on the same article often, you can bookmark the URL once you find your article. 
					</p>

					<h4>Saving Submissions for later Use</h4>
					<p>
						Knowledge Box submissions are shown on the left. They are ordered by longest first. 
						The green and red count indicators signify votes up or down the submission has received in the 
						Knowledge Box Guardian tool.
					</p>
					<p>
						You can either save a submission for use later, or remove it. <span class="bg-warning">If you remove it, you will 
						not see this submission again for one week.</span> If you save it, you will see it in the right pane
						until you remove it. 
					</p>

					<h4>Using a Saved Submission</h4>
					<p>
						Once you have items in your "Saved Que" that you would like to use. Click the "Copy" button
						and the contents will be placed in your clipboard, and a window preloaded with the editor 
						for the current article will open. You can then paste in the submission, and use it in the article.
					</p>
					<p>
						<span class="bg-danger">
						However, if the chosen article does not yet exist, the button will not appear as there is no edit 
						URL to navigate to.
						</span>
					</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-dismiss="modal">OK, Got it</button>
				</div>
			</div>
		</div>
	</div>
	
	<div class="container-fluid">
		<div id="app">
			<div id="submission-container" class="app-container"></div>
			<div id="que-container" class="app-container"></div>
		</div>
	</div>
	
	<nav class="navbar navbar-default navbar-fixed-top">
		
		<div class="container-fluid">
			<div class="navbar-header">
				<a class="navbar-brand" href="/">
					<img src="/skins/WikiHow/images/wh-sm.png"/>
				</a>
			</div>
			
			<form class="navbar-form navbar-left" role="search">
				<div class="form-group">
					<input id="article" type="text" name="article" value="<?= $_GET['article'] ?>" class="form-control" placeholder="Search by url">
				</div>
				<button id="search" type="submit" class="btn btn-primary">Search for Article</button>
				<button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#help-modal">
				  <i class="fa fa-question"></i>
				</button>
			</form>
			
			<div id="toggle-form" class="navbar-form navbar-right">
				<div class="btn-group" id="toggles" data-toggle="buttons">
					<label class="btn btn-primary active pane-toggle" data-target="submission-container">
						<input type="checkbox" autocomplete="off" checked> Submissions
					</label>
					<label class="btn btn-primary active pane-toggle" data-target="que-container">
						<input type="checkbox" autocomplete="off" checked> Saved
					</label>
				</div>
			</div>

		</div>
	</nav>
	
	
	<div id="kbc-errors" style="display:none;">
		{{#errors}}
		<div class="alert animated fadeIn alert-{{style}}">
			{{message}}
		</div>
		{{/errors}} </div>
	
	<!-- Mustache template -->
	<div style="display:none" id="kbc-index">
		<h3>{{title}}</h3>	
		
		<div class="animated fadeIn">
			{{#submissions}}
				<div class="sub well well-sm animated" data-article-id="{{kbc_aid}}" data-kbc-id="{{kbc_id}}">
					<div class="sub-content sub-only truncated {{toggleClass kbc_content 500}}"><span class="inner">{{truncate kbc_content 500}}</span><br/><a class="hint" href="#">show more</a></div>
					<div class="sub-content que-only full {{toggleClass kbc_content 500}}"><span class="inner">{{kbc_content}}</span><br/><a class="hint" href="#">show less</a></div>
							
					<span class="stats">
						{{^if (isZero kbc_up_votes)}}
						<span class="badge badge-success">
							{{blankForZero kbc_up_votes}}
						</span>
						{{/if}}
						{{^if (isZero kbc_down_votes)}}
						<span class="badge badge-danger">
							{{blankForZero kbc_down_votes}}
						</span> 
						{{/if}}
						
						<span>
							{{^if (isEmpty kbc_name)}}
							-- {{kbc_name}} | 
							{{/if}}
							{{^if (isEmpty kbc_email)}}
							<a href="mailto:{{user_email}}">{{kbc_email}}</a>
							{{/if}}
						</span>
					</span>
						
						<div class="hover-menu">
							<div class="btn-group sub-only pull-right">
								<a href="#" class="remove btn btn-danger btn-xs">
									<i class="fa fa-times"></i> Remove
								</a>
						
								<a href="#" class="save btn btn-primary btn-xs">
									<i class="fa fa-star"></i> Save
								</a>	
							</div>
							
							<div class="btn-group que-only pull-right">
								<a href="#" class="remove btn btn-danger btn-xs">
									<i class="fa fa-times"></i> Remove
								</a>
								{{#if ../article.url}}
								<a href="#" data-clipboard-text="{{kbc_content}}" class="btn btn-default btn-xs btn-primary edit">
									<i class="fa fa-external-link"></i>
									Copy
								</a>	
								{{/if}}
							</div>
							
					</div>
				</div>
			{{/submissions}}
		</div>
		
		<!-- button bar at bottom of page to get more -->
		<!-- <div class="well well-lg text-center action-bar sub-only">			
			<div class="btn-group">
				<button class="btn btn-default restore" disabled>
					<i class="fa fa-undo"></i>
					Restore Removed
				</button>
				<button class="btn btn-primary" id="get-more">
					<i class="fa fa-refresh"></i>
					Clear &amp; Load More
				</button>
			</div>
		</div> -->
		
	</div>
	<?= scripts() ?>
	<script>
		WH.KbApp.initialize("<?= $_GET['article'] ?>");
	</script>
</body>
</html>
