<div><a href="<?= $this->Url->build('/cocktails/create') ?>" >カクテルを登録する</a></div>

<!-- 検索フォーム -->
<form action="<?= $this->Url->build('/cocktails/search') ?>" method="get">
	<div class="cocktailSearchForm__block">
		<div class="form-group">
			<div class="col-input-1">
				<input type="text" id="name-search-input" name="name" value="<?php if(isset($params['name'])){ echo $params['name'];} ?>" placeholder="カクテルの名前を入力..." />
			</div>
		</div>
		<div class="form-group">
			<div class="col-label-1">グラス</div>
			<div class="col-input-1">
			<?php foreach ($glass_list as $key => $value):?>
				<input type="checkbox" name="glass[]" value="<?= $key?>"
				<?php if(isset($params['glass']) && in_array($key, $params['glass'])): ?>checked="checked"<?php endif; ?> /><?= $value?>
			<?php endforeach; ?>
			</div>
		</div>
		<div class="form-group">
			<div class="col-label-1">強さ</div>
			<div class="col-input-1">
			<?php foreach ($percentage_list as $key => $value):?>
				<input type="checkbox" name="percentage[]" value="<?= $key?>"
				<?php if(isset($params['percentage']) && in_array($key, $params['percentage'])): ?>checked="checked"<?php endif; ?> /><?= $value?>
			<?php endforeach; ?>
			</div>
		</div>
		<div class="form-group">
			<div class="col-label-1">味</div>
			<div class="col-input-1">
			<?php foreach ($taste_list as $key => $value):?>
				<input type="checkbox" name="taste[]" value="<?= $key?>"
				<?php if(isset($params['taste']) && in_array($key, $params['taste'])): ?>checked="checked"<?php endif; ?> /><?= $value?>
			<?php endforeach; ?>
			</div>
		</div>
	</div>
	<input type="submit" value="検索" />
</form>

<!-- 検索結果表示 -->
<?php if(isset($results)): ?>
    <div class="results_col">
    <?= $this->element('messages', ['messages' => $messages, 'errors' => $errors]);?>
    <?php foreach ($results as $row): ?>
    		<?= $this->element('cocktails/cocktail', ['results' => $row]);?>
    <?php endforeach; ?>
    </div>
<?php endif;?>