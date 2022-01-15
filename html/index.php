<?php
require_once('/var/www/statbate/root/private/init.php');
logDayUsers();
showRoomList();
$topDon = cacheResult('getTopDons', [], 3600);
//$topDon = getTopDons();
$fin = cacheResult('getFinStat', [], 3600, true);
$track = trackCount();
$apiCharts = getApiChart();
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Statbate: Chaturbate Top 100</title>
		<meta name="description" content="How much do webcam models make? Now you know the answer!" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="./css/bootstrap.min.css">
		<link rel="stylesheet" href="./css/dataTables.bootstrap4.min.css">
		<link rel="stylesheet" href="./css/metricsgraphics.min.css">
		<link rel="stylesheet" href="./css/main.old.css?1027">
		<script src="./js/jquery.js"></script>
		<script src="./js/d3.min.js"></script>
		<script src="./js/metricsgraphics.min.js"></script>
		<script src="./js/popper.min.js"></script>
		<script src="./js/bootstrap.min.js"></script>
		<script src="./js/jquery.dataTables.min.js"></script>
		<script src="./js/dataTables.bootstrap4.min.js"></script>
		<script src="./js/highcharts.js"></script>
		<script src="./js/main.old.js?1039"></script>		
		<style>
			.x11 { opacity: 0.5; }
			.x11:hover { opacity: 1.0; }
			.z11 { opacity: 0.2; border-radius: 4px; }
			.z11:hover { opacity: 1.0; }
			.table-curved { border-collapse: collapse; border-spacing: 0; }
			.table-bordered { border-radius: 4px; border-collapse: inherit; }
			
			.modal-dialog {
    margin: 50px auto 0px auto;
}
		</style>
	</head>
	<body>
		<div class="content-box">			
			<nav class="navbar navbar-expand-lg navbar-expand navbar-light">
  <a class="navbar-brand" href="/">$tatbate.com</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
	  
	  
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="/"><b>Chaturbate</b></span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#">BongaCams</a>
      </li>  
      
      <li class="nav-item">
        <a class="nav-link" href="#">Stripchat</a>
      </li>  
      
      <li class="nav-item">
        <a class="nav-link" href="#">LiveJasmin</a>
      </li>  
      
       <li class="nav-item">
        <a class="nav-link" href="#">CamSoda</a>
      </li> 
      
  </div>
  <div class="collapse navbar-collapse justify-content-end">
  <ul class="navbar-nav">
    <li class="nav-item">
     <a id="trackCount" class="nav-link" href="/?list">track <?php echo $track; ?> rooms</a>
    </li>
  </ul>
</div>
  
</nav>
			<div class="content-info">
				<div class="content-text" >
					<div style="position:relative;">
						

						<div id="container"></div>
						
						<script>
							function showStat(){
								var hcData = <?php echo getCharts(); ?>;
								var data = [];
								for (var i = 0; i < hcData.length; i++) {
									//hcData[i] = hcData[i].slice(1, hcData[i].length); // remove first day
									data[i] = MG.convert.date(hcData[i], 'date');
								};
								MG.data_graphic({
									title: false,
									data: data,
									width: 760,
									height: 180,
									bottom: 32,
									right: 36,
									top: 0,
									target: '#container',
									x_accessor: 'date',
									y_accessor: 'value',
									color: ['green', '#25639a', 'brown'],
									legend: ['Girls', 'All', 'Other',],
									area: [false, true, false],
								});
							}
							showStat();
						</script>
						

					</div>
					
					<hr style="margin-top: 6px; margin-bottom: 10px;">
					
					<div class="clear"></div>	
					<div style="height: 152px;">
						<div class="wslog">
							<div class="wstext"></div>
						</div>
						<div class="rinfo">		
							<center>
								<table class="table table-curved table-bordered" style="margin-bottom: 0px; margin-top: 0px;" >
									<tr>
										<th height="28" colspan="2" style="font-weight: normal; padding: 4px 0px;">Statistics for the last month</th>
									</tr>
									<tbody>
										<tr height="32">
											<td>Total income</td>
											<td style="padding: 6px 12px;">&#36;<?php echo dotFormat($fin['total']); ?></td>
										</tr>
										<tr height="30">
											<td style="padding: 5px 0px;">Average income</td>
											<td style="padding: 5px 12px;">&#36;<?php echo round($fin['total']/$fin['count']); ?></td>
										</tr>
										<tr height="30">
											<td style="padding: 5px 0px;">Average tip</td>
											<td style="padding: 5px 12px;">&#36;<?php echo $fin['avg']; ?></td>
											</tr>
										<tr height="30">
											<td style="padding: 5px 0px;">One token</td>
											<td style="padding: 5px 12px;">&#36;0.05</td>
										</tr>
									</tbody>
								</table>
							</center>
						</div>
					</div>
					<div class="clear"></div>
					<hr style="margin-top: 10px; margin-bottom: 10px;">	
					
					
					<ul class="nav nav-tabs "> <!-- justify-content-center -->
  <li class="nav-item">
    <a class="nav-link active" href="#cams" data-toggle="tab" role="tab" aria-controls="cams" aria-selected="true">Rooms</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="#dons" data-toggle="tab" role="tab" aria-controls="dons" aria-selected="false">Donators</a>
  </li>


<li class="nav-item">
    <a class="nav-link" href="#boys" data-toggle="tab" role="tab" aria-controls="charts" aria-selected="false">Boys</a>
  </li>

 <li class="nav-item">
    <a class="nav-link" href="#trans" data-toggle="tab" role="tab" aria-controls="charts" aria-selected="false">Trans</a>
  </li>

  <li class="nav-item">
    <a class="nav-link" href="#couple" data-toggle="tab" role="tab" aria-controls="charts" aria-selected="false">Couple</a>
  </li>
  
  <li class="nav-item">
    <a class="nav-link" href="#incomeCharts" data-toggle="tab" role="tab" aria-controls="incomeCharts" aria-selected="false">Income</a>
  </li>
  
   <li class="nav-item">
    <a class="nav-link" href="#roomsCharts" data-toggle="tab" role="tab" aria-controls="roomsCharts" aria-selected="false">Streamers</a>
  </li>
  
   <li class="nav-item">
    <a class="nav-link" href="#viewersCharts" data-toggle="tab" role="tab" aria-controls="viewersCharts" aria-selected="false">Viewers</a>
  </li>
  
</ul>
<br/>


					
<div class="tab-content">
  <div role="tabpanel active" class="tab-pane fade active show" id="cams">
  
  
 
					
					
					<table id="main" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
						<thead>
							<tr>
								<th style="width:1px;"></th>
								<th>room</th>
								<th style="width:1px;">gender</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th data-toggle="tooltip" data-placement="top" title="In thousands">fans</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable('all'); ?>
						</tbody>
					</table>	
  
  </div>
  
  
  <div role="tabpanel" class="tab-pane fade" id="dons">
  
				
					
					<table id="top100dons" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
							<thead>
								<tr>
									<th></th>
									<th>donator</th>
									<th>last</th>
									<th>rooms</th>
									<th data-toggle="tooltip" data-placement="top" title="Average tip">avg</th>
									<th data-toggle="tooltip" data-placement="top" title="Spend per month">USD</th>
								</tr>
							</thead>
							<tbody>
								<?php echo $topDon; ?>
							</tbody>
						</table>
  
  
  </div>
  
  
  
  <div role="tabpanel" class="tab-pane fade" id="incomeCharts">
					<div id="pieStat"></div>
  </div>
  
  
   <div role="tabpanel" class="tab-pane fade" id="roomsCharts">
					<div id="pieRooms"></div>
  </div>
  
   <div role="tabpanel" class="tab-pane fade" id="viewersCharts">
					<div id="pieViewers"></div>
  </div>



<div role="tabpanel" class="tab-pane fade" id="couple">
  
					
	<table id="couple_table" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
						<thead>
							<tr>
								<th style="width:1px;"></th>
								<th>room</th>
								<th style="width:1px;">gender</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th data-toggle="tooltip" data-placement="top" title="In thousands">fans</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable(3); ?>
						</tbody>
					</table>	
					
  
  
  </div>

<div role="tabpanel" class="tab-pane fade" id="boys">
  
					
	<table id="boys_table" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
						<thead>
							<tr>
								<th style="width:1px;"></th>
								<th>room</th>
								<th style="width:1px;">gender</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th data-toggle="tooltip" data-placement="top" title="In thousands">fans</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable(0); ?>
						</tbody>
					</table>	
					
  
  
  </div>
  
  
    <div role="tabpanel" class="tab-pane fade" id="trans">
  
					
	<table id="trans_table" class="table table-striped table-bordered dataTable no-footer" cellspacing="0" width="100%" role="grid" aria-describedby="supportList_info" style="width: 100%;">
						<thead>
							<tr>
								<th style="width:1px;"></th>
								<th>room</th>
								<th style="width:1px;">gender</th>
								<th data-toggle="tooltip" data-placement="top" title="Use search online">last</th>
								<th data-toggle="tooltip" data-placement="top" title="In thousands">fans</th>
								<th data-toggle="tooltip" data-placement="top" title="Income per month">USD</th>
							</tr>
						</thead>
						<tbody>
							<?php echo prepareTable(2); ?>
						</tbody>
					</table>	
					
  
  
  </div>
  

</div>
					
									
				</div>
			</div>



<div style="padding-top: 12px; padding-bottom: 17px;" >
			<div style="float: right;" class="x11">
				statbate@gmail.com
			</div>
			<div style="float: left;" class="x11">
				twitter <a href="https://twitter.com/statbate" target="_blank" rel="nofollow">@statbate</a>
			</div>
		</div>


		
			
			<!--<div style="padding-top: 12px;" class="x11">
				<center><font size="2"><strong>How much do webcam models make?</strong> To answer this question, we collect data from open sources.</font></center>
			</div> -->
			
		</div>

		

<!--<div class="alert alert-success" role="alert">
	<center>
  <font color="black">

  statbate.com is under active development. Possibly unstable and bugs.</font>
  </center>
</div> -->
		
		<!--<div class="alert alert-dark" role="alert" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,0.14), 0 2px 1px -1px rgba(0,0,0,0.12), 0 1px 3px 0 rgba(0,0,0,0.20); margin-bottom: 12px; font-size: 12.2pt; color: #000000;">
			<center>test test test</center>
		</div> -->
		
		<div class="modal fade" id="donRoomModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog" style="max-width: 400px; min-height: 680px;">
				<div class="modal-content">
					<div class="modal-body">		
						<table id="donRoomTable" class="table table-striped DonTable">
							<thead>
								<tr>
									<th></th>
									<th>USD</th>
									<th data-toggle="tooltip" data-placement="right" title="Average tip">AVG</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
						<hr/>
						<div id="modelChart" style="margin-top: -25px;"></div>
						<div id="allIncome"></div>
					</div>
				</div>
			</div>
		</div>
		
		<script>		
			Highcharts.chart('pieStat', {
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false,
					type: 'pie'
				},
				credits: {
					enabled: false
				},
				title: {
					text: 'Income distribution for the current month'
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				accessibility: {
					point: {
						valueSuffix: '%'
					}
				},
				plotOptions: {
					pie: {
						colors: ["#434348", "#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"],
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
							format: '<b>{point.name}</b>: {point.percentage:.1f} %'
						}
					}
				},
				series: [{
					name: 'Income',
					colorByPoint: true,
					data: <?php echo getPieStat(); ?>
				}]
			});
			
			Highcharts.chart('pieRooms', {
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false,
					type: 'pie'
				},
				credits: {
					enabled: false
				},
				title: {
					text: 'Rooms distribution'
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				accessibility: {
					point: {
						valueSuffix: '%'
					}
				},
				plotOptions: {
					pie: {
						colors: ["#434348", "#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"],
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
							format: '<b>{point.name}</b>: {point.percentage:.1f} %'
						}
					}
				},
				series: [{
					name: 'Income',
					colorByPoint: true,
					data: <?php echo $apiCharts[0]; ?>
				}]
			});
			
			Highcharts.chart('pieViewers', {
				chart: {
					plotBackgroundColor: null,
					plotBorderWidth: null,
					plotShadow: false,
					type: 'pie'
				},
				credits: {
					enabled: false
				},
				title: {
					text: 'Viewers distribution'
				},
				tooltip: {
					pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
				},
				accessibility: {
					point: {
						valueSuffix: '%'
					}
				},
				plotOptions: {
					pie: {
						colors: ["#434348", "#7cb5ec", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"],
						allowPointSelect: true,
						cursor: 'pointer',
						dataLabels: {
							enabled: true,
							format: '<b>{point.name}</b>: {point.percentage:.1f} %'
						}
					}
				},
				series: [{
					name: 'Income',
					colorByPoint: true,
					data: <?php echo $apiCharts[1]; ?>
				}]
			});
		</script>
	</body>
</html>
