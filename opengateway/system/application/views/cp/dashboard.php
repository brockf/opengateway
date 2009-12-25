<?=$this->load->view('cp/header');?>
<h1>Latest activity</h1>
<form method="get" action="dashboard/">
<div class="pagination">
	<a href="#">&laquo; First</a>&nbsp;&nbsp;<a href="#">&lt;</a>&nbsp;<a href="#">1</a>&nbsp;<a href="#">2</a>&nbsp;<b>3</b>&nbsp;<a href="#">4</a>&nbsp;<a href="#">5</a>&nbsp;<a href="#">&gt;</a>&nbsp;&nbsp;<a href="#">Last &raquo;</a>
	<div class="apply_filters"><input type="submit" name="apply_filters" value="Filter Dataset" /></div>
</div>
<table class="dataset" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<td style="width:8%"></td>
			<td style="width:12%">ID #</td>
			<td style="width:15%">test</td>
			<td style="width:25%">Transaction #</td>
			<td style="width:40%">Name</td>
		</tr>
	</thead>
	<tbody>
		<tr class="filters">
			<td></td>
			<td><input type="text" class="text id" name="test_filter" /></td>
			<td></td>
			<td><input type="text" class="text" name="test_filter" /></td>
			<td><input type="text" class="text" name="test_filter" /></td>
		</tr>
		<tr>
			<td>1</td>
			<td>54</td>
			<td><a href="blahblah">blah</a></td>
			<td>30483948934</td>
			<td>Brock Ferguson</td>
		</tr>
		<tr>
			<td>1</td>
			<td>54</td>
			<td><a href="blahblah">blah</a></td>
			<td>30483948934</td>
			<td>Brock Ferguson</td>
		</tr>
		<tr>
			<td>1</td>
			<td>54</td>
			<td><a href="blahblah">blah</a></td>
			<td>30483948934</td>
			<td>Brock Ferguson</td>
		</tr>
	</tbody>
</table>
<div class="pagination">
	<a href="#">&laquo; First</a>&nbsp;&nbsp;<a href="#">&lt;</a>&nbsp;<a href="#">1</a>&nbsp;<a href="#">2</a>&nbsp;<b>3</b>&nbsp;<a href="#">4</a>&nbsp;<a href="#">5</a>&nbsp;<a href="#">&gt;</a>&nbsp;&nbsp;<a href="#">Last &raquo;</a>
</div>
</form>
<?=$this->load->view('cp/footer');?>