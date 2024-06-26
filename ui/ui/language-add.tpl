{include file="sections/header.tpl"}

		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="panel panel-primary panel-hovered panel-stacked mb30">
					<div class="panel-heading">{Lang::T('Add Service Plan')}</div>
						<div class="panel-body">
						<form class="form-horizontal" method="post" role="form" action="{$_url}settings/lang-post" >            
							<div class="form-group">
									<label class="col-md-2 control-label">{Lang::T('Language Name')}</label>
								<div class="col-md-6">
									<input type="text" class="form-control" id="name" name="name">
								</div>
							</div>
							<div class="form-group">
									<label class="col-md-2 control-label">{Lang::T('Folder Name')}</label>
								<div class="col-md-6">
									<input type="text" class="form-control" id="folder" name="folder">
								</div>
							</div>
							<div class="form-group">
								<label class="col-md-2 control-label">{Lang::T('Translator')}</label>
								<div class="col-md-6">
									<input type="text" class="form-control" id="translator" name="translator">
								</div>
							</div>
							<div class="form-group">
								<div class="col-lg-offset-2 col-lg-10">
									<button class="btn btn-primary" type="submit">{Lang::T('Save Changes')}</button>
									Or <a href="{$_url}settings/localisation">{Lang::T('Cancel')}</a>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

{include file="sections/footer.tpl"}
