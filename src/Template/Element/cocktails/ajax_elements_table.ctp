<?php if(isset($elements_list_selected)):?>
<?php foreach ($elements_list_selected as $key => $value):?>
<tr><!-- 'saved_id' => 'CocktailElements.id', 'id' => 'me.id' -->
  <input type="hidden" class="saved_id" name="saved_id[]" value="<?= $value['saved_id']??'' ?>" />
  <input type="hidden" class="element_id_selected" name="element_id_selected[]" value="<?= $value['id'] ?>" />
  <input type="hidden" class="amount_selected" name="amount_selected[]" value="<?=$value['amount']?>" />
  <input type="hidden" class="index" name="index" value="<?=$key?>" />
  <th class="table-header-md"><?=$category_list[$value['category_kbn']]?></th>
  <td class="table-data-md"><?=$value['name']?></td>
  <td class="table-data-sm"><?=$value['amount']?></td>
  <td class="table-data-md"><button type="button" class="btn btn-default btn-sm delete-elements" >削除</button></td>
</tr>
<?php endforeach;?>
<?php endif;?>