<?php
style('files_antivirus', 'settings');
script('files_antivirus', 'settings');
?>
<?php if ($_['files-antivirus.scanner-class'] === null) { ?>
<div class="section section-antivirus">
	<form id="antivirus" action="#" method="post">
		<fieldset class="personalblock">
			<h2><?php p($l->t('Antivirus Configuration'));?></h2>
			<p class="av_mode">
				<label for="av_mode"><?php p($l->t('Mode'));?></label>
				<select id="av_mode" name="avMode">
					<?php print_unescaped(html_select_options(['executable' => $l->t('Executable'), 'daemon' => $l->t('Daemon'), 'socket' => $l->t('Daemon (Socket)')], $_['avMode'])) ?>
				</select>
			</p>
		    <p class="av_socket">
				<label for="av_socket"><?php p($l->t('Socket'));?></label>
				<input type="text" id="av_socket" name="avSocket" value="<?php p($_['avSocket']); ?>" title="<?php p($l->t('Clamav Socket')); ?>">
			</p>
			<p class="av_host">
				<label for="av_host"><?php p($l->t('Host'));?></label>
				<input pattern="[a-zA-z0-9\.-]+" type="text" id="av_host" name="avHost" value="<?php p($_['avHost']); ?>" title="<?php p($l->t('Hostname or IP address of Antivirus Host'));?>">
			</p>
			<p class="av_port">
				<label for="av_port"><?php p($l->t('Port'));?></label>
				<input pattern="[1-9][0-9]{0,4}" type="text" id="av_port" name="avPort" value="<?php p($_['avPort']); ?>" title="<?php p($l->t('Port number of Antivirus Host, 1-65535'));?>">
			</p>
			<p class="av_path">
				<label for="av_path"><?php p($l->t('Path to clamscan'));?></label>
				<input type="text" id="av_path" name="avPath" value="<?php p($_['avPath']); ?>" title="<?php p($l->t('Path to clamscan executable')); ?>" />
			</p>
			<p class="av_path">
				<label for="av_cmd_options"><?php p($l->t('Extra command line options (comma-separated)'));?></label>
				<input type="text" id="av_cmd_options" name="avCmdOptions" value="<?php p($_['avCmdOptions']); ?>" />
			</p>
			<p class="av_stream_max_length">
				<label for="av_stream_max_length">
					<?php p($l->t('Stream Length'));?>
				</label>
				<input pattern="[1-9][0-9]*" type="text" id="av_stream_max_length" name="avStreamMaxLength" value="<?php p($_['avStreamMaxLength']); ?>"
					   title="<?php p($l->t('ClamAV StreamMaxLength value in bytes')); ?>"
				/>
				<label for="av_stream_max_length" class="a-left"><?php p($l->t('bytes'))?></label>
			</p>
			<p class="av_max_file_size">
				<label for="av_max_file_size"><?php p($l->t('File size limit, -1 means no limit'));?></label>
				<input pattern="([1-9][0-9]*)|(-1)" type="text" id="av_max_file_size" name="avMaxFileSize" value="<?php p($_['avMaxFileSize']); ?>"
					   title="<?php p($l->t('File size limit in bytes, -1 means no limit'));?>"
				/>
				<label for="av_max_file_size" class="a-left"><?php p($l->t('bytes'))?></label>
			</p>
			<p class="infected_action">
				<label for="av_infected_action"><?php p($l->t('When infected files were found during a background scan'));?></label>
				<select id="av_infected_action" name="avInfectedAction"><?php print_unescaped(html_select_options(['only_log' => $l->t('Only log'), 'delete' => $l->t('Delete file')], $_['avInfectedAction'])) ?></select>
			</p>
			<button id="av_submit" type="button"><?php p($l->t('Save'));?></button>
			<span id="antivirus_save_msg"></span>
		</fieldset>
	</form>
	<hr />
	<button id="antivirus-advanced"><?php p($l->t('Advanced')) ?></button>
	<div class="spoiler">
		<h3><?php p($l->t('Rules')) ?></h3>
		<div id="antivirus-buttons">
			<button id="antivirus-clear"><?php p($l->t('Clear All')) ?></button>
			<button id="antivirus-reset"><?php p($l->t('Reset to defaults')) ?></button>
		</div>
		<table id="antivirus-statuses" class="grid">
			<thead>
			<tr>
				<th></th>
				<th><?php p($l->t('Match by')) ?></th>
				<th><?php p($l->t('Scanner exit status or signature to search')) ?></th>
				<th><?php p($l->t('Description')); ?></th>
				<th><?php p($l->t('Mark as')) ?></th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
		<button id="antivirus-add" class="icon-add"><?php p($l->t('Add a rule')) ?></button>
	</div>
</div>
<?php } else { ?>
<div class="section section-antivirus">
	<form id="antivirus" action="#" method="post">
	<fieldset class="personalblock">
	<h2><?php p($l->t('Antivirus Configuration'));?></h2>
		<p><?php p($l->t('An external scanner implementation is used. Please lookup in the docs on how to configure %s', [$_['files-antivirus.scanner-class']])); ?></p>
	</fieldset>
	</form>
</div>

<?php }
