<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>S  O  O  N</title>
	<link rel="icon" href="http://stmonday.io/wp-content/uploads/2021/11/cropped-StM_Logotype_Favicon_512px_Oct21-32x32.png" sizes="32x32">
	<style>
		main {
			max-width: 80%;
			margin-left: auto;
			margin-right: auto;
		}
		.bttn {
			background: #fff url('<?php echo get_stylesheet_directory_uri() ?>/assets/images/StM_Lookbook_Bttn.svg') left center/contain no-repeat !important;
			height: 25vw;
			width: 25vw;
			display: block;
			margin: 1.5vw auto 0;
			max-width: 203px;
			max-height: 203px;
		}
		.holding {
		  width:  100%;
		  margin-left: auto;
		  margin-right: auto;
		  max-width: 800px;
		}
		.marque {
		  margin-left: auto;
		  margin-right: auto;
		  display: block;
		  width: 100%;
		  margin: 2vw auto 0;
		}
		@media only screen and (min-width: 700px) {
			.holding {
				width: 80%;
			}
			.bttn {
				height: 20vw;
				width: 20vw;
			}
		}
		@media only screen and (min-width: 1015px) {
			.bttn {
				margin-top: 15.2px;
			}
		}

	</style>
</head>
<body>
	<main>
		<div class='holding'>
			<img class='marque' src='<?php echo get_stylesheet_directory_uri() ?>/assets/images/StM_Marque.svg' width='640'>
			<a href='<?php echo site_url() ?>/lookbook/' class='bttn'></a>
		</div>
	</main>
</body>
</html>