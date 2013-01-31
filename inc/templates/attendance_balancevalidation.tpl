<html>
<head>
<title>{$core->settings[systemtitle]} | {$lang->validatebalances}</title>
</head>
{$headerinc}
</head>
<body>
{$header}
<tr> {$menu}
	<td class="contentContainer"><h3>{$lang->validatebalances}</h3>
		<form action="index.php?module=attendance/balancesvalidations&amp;action=preview" method="post">
			<div style="width:15%; display:inline-block; margin: 5px; font-weight:bold;">{$lang->affiliate}</div>
			<div style="width:80%; display:inline-block; margin: 5px;">{$affid_field}</div>
			<div style="width:15%; display:inline-block; margin: 5px; font-weight:bold;">{$lang->period}</div>
			<div style="width:80%; display:inline-block; margin: 5px;">{$periods_list}</div>
			<div style="width:15%; display:inline-block; margin: 5px;">{$lang->prevperiod}</div>
			<div style="width:80%; display:inline-block; margin: 5px;">{$prevperiods_list}</div>
			<div style="width:15%; display:inline-block; margin: 5px; font-weight:bold;">{$lang->leavetype}</div>
			<div style="width:80%; display:inline-block; margin: 5px;">{$types_list}</div>
			<hr />
			<input type="submit" value="{$lang->previewbalances}" class="button"> <input type="reset" value="$lang->reset" class="button">
		</form></td>
</tr>
{$footer}
</body>
</html>