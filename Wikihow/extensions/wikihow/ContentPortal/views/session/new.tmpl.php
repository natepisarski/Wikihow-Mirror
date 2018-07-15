


<div class="row">
		
	<div class="col-md-8">
		<div class="well">
			<h2>
				Log In
				<small>Manage your assignments in the WikiHow Content Portal</small>
			</h2>

			<div class="well-body">
				
				<form class="prevent-double form-horizontal" action="<?= url('session/create') ?>" method="POST">
					
					<div class="form-group">
						<label class="control-label col-sm-3">WikiHow Username:</label>
						<div class="col-sm-9">
							<input type="text" name="user[username]" value="<?= params("user[username]") ?>" class="input-lg form-control" placeholder="Wikihow Username">
						</div>
					</div>

					<div class="form-group">
						<label class="control-label col-sm-3">Password:</label>
						<div class="col-sm-9">
							<input type="password" name="user[password]" value="" class="form-control input-lg">
						</div>
					</div>

					<hr/>

					<div class="row">
						<div class="col-md-3"></div>
						<div class="col-md-9">
							<button class="btn btn-primary btn-lg btn-block">
								<i class="fa fa-sign-in"></i>
								Log In
							</button>
						</div>
					</div>
				</form>

			</div>
		</div>
	</div>

	<div class="col-md-4">

	<div class="panel panel-default">
		<div class="panel-heading"> 
			<i class="fa fa-question-circle"></i>
			Trouble Logging In?
		</div>

		<div class="panel-body">
			<p>
				Your log in for the Content Portal is the same as your
				regular login to Wikihow.com.
			</p>
			<p>
				To reset your password, visit 
				<a href="https://www.wikihow.com/index.php?title=Special:UserLogin&returnto=&returntoquery=&type=login">Wikihow.com</a> and click the <b>Forgot Password?</b> link;
		</div>
	</div>
 

<!-- 		<div class="panel panel-default">
			<div class="panel-heading">
				Authenticate with token
			</div>

			<div class="panel-body">
				<form class="form prevent-double" action="<?= url('session/create') ?>" method="post">
					<div class="form-group">
					<label>Your User Token</label>
						<input type="hidden" name="token-form" value="true" />
						<input name="token" class="form-control" type="password"/>
					</div>
						<button type="submit" class="btn btn-primary btn-block">Enter</button>
				</form>	
			</div>

		</div> -->
	</div>

</div>
