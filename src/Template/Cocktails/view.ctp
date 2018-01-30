<!-- 検索結果表示 -->
<div class="title__wrapper">
    <h1><?= $cocktail['name']?></h1>
    <ul>
        <li>
            <a href="/cocktails/<?=$cocktail['id']?>/edit">編集する</a>
        </li>
    </ul>
</div>
<div class="cocktail__wrapper">
    <table class="detail-table">
        <tr>
            <th class="table-header-md">グラスタイプ</th>
            <td class="table-data-md"><?= $glass_list[$cocktail['glass']]?></td>
        </tr>
        <tr>
            <th class="table-header-md">強さ</th>
            <td class="table-data-md"><?= $percentage_list[$cocktail['percentage']]?></td>
        </tr>
        <tr>
            <th class="table-header-md">色</th>
            <td class="table-data-md"><?= $cocktail['color']?></td>
        </tr>
        <tr>
            <th class="table-header-md">テイスト</th>
            <td class="table-data-md"><?= $taste_list[$cocktail['taste']]?></td>
        </tr>
    </table>
</div>
<div class="cocktailElements__wrapper">
    <h2>入れるもの</h2>
    <table class="detail-elements-table">
    <?php foreach ($cocktail_elements as $element): ?>
        <tr>
            <th class="table-header-md"><?= $category_list[$element['category_kbn']]?></th>
            <td class="table-data-md"><?= $element['name']?></td>
            <td class="table-data-sm"><?= $element['amount']?></td>
        </tr>
    <?php endforeach; ?>
    </table>
</div>
<div class="cocktail__wrapper">
    <h2>手順</h2>
    <p><?= $cocktail['processes']?></p>
</div>
