{include file="sections/header.tpl"}

					<div class="row">
						<div class="col-sm-12">
							<div class="panel panel-hovered mb20 panel-primary">
                <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>{Lang::T('Bandwidth Plans')}</span>
                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#tutorialModal" style="margin-left: auto;">
                        {Lang::T('Need Help?')}
                    </button>
                </div>
								<div class="panel-body">
									<div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
										<div class="col-md-8">
											<form id="site-search" method="post" action="{$_url}bandwidth/list/">
											<div class="input-group">
												<div class="input-group-addon">
													<span class="fa fa-search"></span>
												</div>
												<input type="text" name="name" class="form-control" placeholder="{Lang::T('Search by Name')}...">
												<div class="input-group-btn">
													<button class="btn btn-success" type="submit">{Lang::T('Search')}</button>
												</div>
											</div>
											</form>
										</div>
										<div class="col-md-4">
											<a href="{$_url}bandwidth/add" class="btn btn-primary btn-block"><i class="ion ion-android-add"> </i> {Lang::T('New Bandwidth')}</a>
										</div>&nbsp;
									</div>
									<div class="table-responsive">
                                        <table class="table table-bordered table-condensed table-striped table_mobile">
                                            <thead>
                                                <tr>
                                                    <th>{Lang::T('Bandwidth Name')}</th>
                                                    <th>{Lang::T('Rate')}</th>
                                                    <th>{Lang::T('Burst')}</th>
                                                    <th>{Lang::T('Manage')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            {foreach $d as $ds}
                                                <tr>
                                                    <td>{$ds['name_bw']}</td>
                                                    <td>{$ds['rate_down']} {$ds['rate_down_unit']} / {$ds['rate_up']} {$ds['rate_up_unit']}</td>
                                                    <td>{$ds['burst']}</td>
                                                    <td>
                                                        <a href="{$_url}bandwidth/edit/{$ds['id']}" class="btn btn-sm btn-warning">{Lang::T('Edit')}</a>
                                                        <a href="{$_url}bandwidth/delete/{$ds['id']}" id="{$ds['id']}" class="btn btn-danger btn-sm" onclick="return confirm('{Lang::T('Delete')}?')" ><i class="glyphicon glyphicon-trash"></i></a>
                                                    </td>
                                                </tr>
                                            {/foreach}
                                            </tbody>
                                        </table>
                                    </div>
						{$paginator['contents']}
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade" id="tutorialModal" tabindex="-1" role="dialog" aria-labelledby="tutorialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tutorialModalLabel">Tutorial Video</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/S2SZtktBQSI?si=gcGk8YYVfOXqCru9" allowfullscreen></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{include file="sections/footer.tpl"}