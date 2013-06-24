			</div>
			<div id="box-bottom"></div>
		</div>
	</div>
	<div id="footer">
		Powered by <a href="http://www.opengateway.net">OpenGateway.net</a> v<?=$this->config->item('opengateway_version');?>.  Copyright &copy; 2009-<?=date('Y');?>, Electric Function, Inc. <?
		
			if (defined("_LICENSENUMBER")) {
				echo 'License Number: ' . _LICENSENUMBER;
			}
			
			?>
	</div>
<div class="hidden" id="base_url"><?=base_url();?></div>
<div class="hidden" id="current_url"><?=current_url();?></div>
</body>
</html>