<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>{{ config('app.name') }}</title>
	<meta content="width=device-width, initial-scale=1.0" name="viewport" />
	<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
	<meta content="light" name="color-scheme">
	<meta content="light" name="supported-color-schemes">
	<style>
		@media only screen and (max-width: 600px) {
			.inner-body {
				width: 100% !important;
			}

			.footer {
				width: 100% !important;
			}
		}

		@media only screen and (max-width: 500px) {
			.button {
				width: 100% !important;
			}
		}
	</style>
</head>

<body>

	<table cellpadding="0" cellspacing="0" class="wrapper" role="presentation" width="100%">
		<tr>
			<td align="center">
				<table cellpadding="0" cellspacing="0" class="content" role="presentation" width="100%">
					{{ $header ?? '' }}

					<!-- Email Body -->
					<tr>
						<td cellpadding="0" cellspacing="0" class="body" style="border: hidden !important;" width="100%">
							<table align="center" cellpadding="0" cellspacing="0" class="inner-body" role="presentation" width="570">
								<!-- Body content -->
								<tr>
									<td class="content-cell">
										{{ Illuminate\Mail\Markdown::parse($slot) }}

										{{ $subcopy ?? '' }}
									</td>
								</tr>
							</table>
						</td>
					</tr>

					{{ $footer ?? '' }}
				</table>
			</td>
		</tr>
	</table>
</body>

</html>
